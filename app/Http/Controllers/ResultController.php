<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\CbcGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function __construct(private readonly CbcGradingService $gradingService) {}

    public function grid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);

        $exam = Exam::with('academicYear')->findOrFail($validated['exam_id']);

        /** @var \App\Models\SchoolClass $class */
        $class = SchoolClass::with(['students' => fn ($q) => $q->orderBy('name')])->findOrFail($validated['class_id']);

        $subjects = Subject::where('class_id', $class->id)
            ->orderBy('display_order')
            ->get(['id', 'name', 'display_order']);

        $students = $class->students;

        // Detect opener and midterm exams within the same academic year as the requested exam.
        $examsInYear = Exam::where('academic_year_id', $exam->academic_year_id)->get();

        $openerExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'open'));
        $midtermExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'mid'));

        // If both opener and midterm exist, build rows for both exams so marks
        // can be viewed and edited together per student.
        if ($openerExam && $midtermExam) {
            $results = Result::whereIn('exam_id', [$openerExam->id, $midtermExam->id])
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy(fn (Result $result): string => $result->student_id.'-'.$result->exam_id);

            $rows = $students->flatMap(function (Student $student) use ($subjects, $results, $openerExam, $midtermExam) {
                $studentRows = [];

                foreach ([$openerExam, $midtermExam] as $examModel) {
                    $key = $student->id.'-'.$examModel->id;
                    $studentResults = $results->get($key, collect());

                    $scores = [];
                    foreach ($subjects as $subject) {
                        /** @var \App\Models\Result|null $result */
                        $result = $studentResults->firstWhere('subject_id', $subject->id);
                        $scores[$subject->id] = $result?->raw_score;
                    }

                    $studentRows[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'exam_id' => $examModel->id,
                        'exam_label' => $examModel->name,
                        'scores' => $scores,
                    ];
                }

                return $studentRows;
            })->values();
        } else {
            // Single exam grid (current behaviour).
            $results = Result::where('exam_id', $validated['exam_id'])
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy('student_id');

            $rows = $students->map(function (Student $student) use ($subjects, $results, $exam) {
                $studentResults = $results->get($student->id, collect());

                $scores = [];
                foreach ($subjects as $subject) {
                    /** @var \App\Models\Result|null $result */
                    $result = $studentResults->firstWhere('subject_id', $subject->id);
                    $scores[$subject->id] = $result?->raw_score;
                }

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'exam_id' => $exam->id,
                    'exam_label' => $exam->name,
                    'scores' => $scores,
                ];
            })->values();
        }

        return response()->json([
            'class' => [
                'id' => $class->id,
                'name' => $class->display_name,
            ],
            'subjects' => $subjects,
            'students' => $rows,
        ]);
    }

    public function updateGrid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'rows' => ['required', 'array'],
            'rows.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'rows.*.scores' => ['required', 'array'],
            'rows.*.exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);

        $classId = $validated['class_id'];
        $examId = $validated['exam_id'];

        foreach ($validated['rows'] as $row) {
            $student = Student::where('id', $row['student_id'])
                ->where('class_id', $classId)
                ->first();

            if (! $student) {
                continue;
            }

            $rowExamId = isset($row['exam_id']) ? (int) $row['exam_id'] : $examId;

            foreach ($row['scores'] as $subjectId => $score) {
                if ($score === null || $score === '') {
                    continue;
                }

                $numericScore = (int) $score;

                $graded = $this->gradingService->grade($numericScore);

                Result::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'exam_id' => $rowExamId,
                        'subject_id' => (int) $subjectId,
                    ],
                    [
                        'raw_score' => $numericScore,
                        'cbc_sublevel' => $graded['sublevel'],
                        'cbc_grade' => $graded['grade'],
                    ]
                );
            }
        }

        return response()->json(['message' => 'Results updated successfully.']);
    }

    public function destroyForClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);

        $studentIds = Student::where('class_id', $validated['class_id'])->pluck('id');

        $deleted = Result::where('exam_id', $validated['exam_id'])
            ->whereIn('student_id', $studentIds)
            ->delete();

        return response()->json(['message' => "Deleted {$deleted} result(s) for this exam and class."]);
    }
}
