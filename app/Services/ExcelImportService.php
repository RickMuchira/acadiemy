<?php

namespace App\Services;

use App\Imports\MarksImport;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class ExcelImportService
{
    public function __construct(private readonly CbcGradingService $gradingService) {}

    /**
     * Parse an uploaded file and return preview data without saving.
     *
     * @return array{students: array<int, array{name: string, scores: array<string, int>}>, subjects: string[]}
     */
    public function preview(string $filePath, int $classId): array
    {
        $import = new MarksImport;
        Excel::import($import, $filePath, null, $this->detectReader($filePath));

        $rows = $import->getData();

        return $this->buildPreview($rows, $classId);
    }

    /**
     * Commit the parsed file data to the database.
     */
    public function commit(string $filePath, int $examId, int $classId): void
    {
        $import = new MarksImport;
        Excel::import($import, $filePath, null, $this->detectReader($filePath));

        $rows = $import->getData();
        $preview = $this->buildPreview($rows, $classId);

        $this->saveToDatabase($preview, $examId, $classId);
    }

    /**
     * Detect the true spreadsheet format by inspecting the file contents,
     * regardless of the file extension. Handles ODS files incorrectly saved
     * with an .xlsx extension (common from LibreOffice on Linux).
     */
    private function detectReader(string $filePath): string
    {
        // All XLSX and ODS files are ZIP archives starting with PK header
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 4);
        fclose($handle);

        if ($header === "PK\x03\x04") {
            // It's a ZIP — check if it's ODS by looking for the mimetype entry
            $zip = new ZipArchive();
            if ($zip->open($filePath) === true) {
                $mimetype = $zip->getFromName('mimetype');
                $zip->close();

                if ($mimetype !== false && str_contains($mimetype, 'opendocument.spreadsheet')) {
                    return ExcelFormat::ODS;
                }
            }

            return ExcelFormat::XLSX;
        }

        // Fall back to CSV for plain text files
        return ExcelFormat::CSV;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{students: array<int, array{name: string, scores: array<string, int>}>, subjects: string[]}
     */
    private function buildPreview(Collection $rows, int $classId): array
    {
        $skippedColumns = ['student name', 'student_name', 'name', 'total', 'average', 'avg'];
        $subjects = [];
        $students = [];

        foreach ($rows as $row) {
            $row = collect($row)->mapWithKeys(
                fn ($value, $key) => [strtolower(trim((string) $key)) => $value]
            );

            // Find the column that represents the student name.
            $studentColumn = null;
            foreach ($row->keys() as $columnKey) {
                $k = (string) $columnKey;
                if ($k === 'student name' || $k === 'student_name' || $k === 'name' || str_contains($k, 'student')) {
                    $studentColumn = $k;
                    break;
                }
            }

            if ($studentColumn === null) {
                continue;
            }

            $studentName = trim((string) ($row[$studentColumn] ?? ''));

            if (empty($studentName) || strtolower($studentName) === 'class average') {
                continue;
            }

            $scores = [];
            foreach ($row as $column => $value) {
                if (in_array($column, $skippedColumns, true)) {
                    continue;
                }

                // Skip empty column names (can happen with ODS trailing cell padding)
                if (empty(trim($column))) {
                    continue;
                }

                // Treat missing or empty values as null — do not force zero
                // This handles ODS rows where trailing empty cells are omitted entirely
                if ($value === null || $value === '') {
                    continue;
                }

                $subjectName = ucfirst($column);
                $scores[$subjectName] = (int) $value;

                if (! in_array($subjectName, $subjects, true)) {
                    $subjects[] = $subjectName;
                }
            }

            $students[] = [
                'name' => $studentName,
                'scores' => $scores,
            ];
        }

        return compact('students', 'subjects');
    }

    /**
     * @param  array{students: array<int, array{name: string, scores: array<string, int>}>, subjects: string[]}  $preview
     */
    private function saveToDatabase(array $preview, int $examId, int $classId): void
    {
        // Preload existing students for this class and index them by normalized name
        $existingStudents = Student::where('class_id', $classId)->get();
        $studentCache = [];

        foreach ($existingStudents as $existingStudent) {
            $normalized = $this->normalizeStudentName($existingStudent->name);
            $studentCache[$normalized] = $existingStudent;
        }

        $subjectMap = [];
        $maxOrder = Subject::where('class_id', $classId)->max('display_order') ?? -1;

        foreach ($preview['subjects'] as $subjectName) {
            $subject = Subject::firstOrCreate(
                ['class_id' => $classId, 'name' => $subjectName],
                ['display_order' => ++$maxOrder]
            );
            $subjectMap[$subjectName] = $subject->id;
        }

        foreach ($preview['students'] as $studentData) {
            $student = $this->findOrCreateStudentForImport($classId, $studentData['name'], $studentCache);

            foreach ($studentData['scores'] as $subjectName => $score) {
                $subjectId = $subjectMap[$subjectName] ?? null;

                if ($subjectId === null) {
                    continue;
                }

                $graded = $this->gradingService->grade($score);

                Result::updateOrCreate(
                    ['student_id' => $student->id, 'exam_id' => $examId, 'subject_id' => $subjectId],
                    [
                        'raw_score' => $score,
                        'cbc_sublevel' => $graded['sublevel'],
                        'cbc_grade' => $graded['grade'],
                    ]
                );
            }
        }
    }

    /**
     * Normalize a student name for comparison: trim, collapse spaces, lowercase.
     */
    private function normalizeStudentName(string $name): string
    {
        $normalized = trim($name);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return mb_strtolower($normalized ?? '');
    }

    /**
     * Find an existing student by fuzzy-matching their name, or create a new one.
     *
     * @param  array<string, \App\Models\Student>  $studentCache  (normalized name => Student)
     */
    private function findOrCreateStudentForImport(int $classId, string $rawName, array &$studentCache): Student
    {
        $normalized = $this->normalizeStudentName($rawName);

        // Exact normalized match in cache
        if (isset($studentCache[$normalized])) {
            return $studentCache[$normalized];
        }

        // Fuzzy match against existing normalized names using Levenshtein distance
        $closestKey = null;
        $closestDistance = null;

        foreach ($studentCache as $key => $student) {
            $distance = levenshtein($normalized, $key);

            if ($closestDistance === null || $distance < $closestDistance) {
                $closestDistance = $distance;
                $closestKey = $key;
            }
        }

        // If a close-enough match exists (small edit distance), reuse that student
        if ($closestKey !== null && $closestDistance !== null && $closestDistance <= 2) {
            return $studentCache[$closestKey];
        }

        // Otherwise, create a new student record and cache it by normalized name
        $student = Student::firstOrCreate(
            ['class_id' => $classId, 'name' => $rawName]
        );

        $studentCache[$normalized] = $student;

        return $student;
    }
}
