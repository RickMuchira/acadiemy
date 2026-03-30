<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = SchoolClass::withCount('students')
            ->orderByRaw('COALESCE(sort_order, 9999)')
            ->orderByRaw("CASE level
                WHEN 'Grade6' THEN 1
                WHEN 'Grade5' THEN 2
                WHEN 'Grade4' THEN 3
                WHEN 'Grade3' THEN 4
                WHEN 'Grade2' THEN 5
                WHEN 'Grade1' THEN 6
                WHEN 'PP2' THEN 7
                WHEN 'PP1' THEN 8
                WHEN 'PG' THEN 9
                ELSE 10
            END")
            ->orderBy('stream')
            ->get()
            ->map(fn ($class) => array_merge($class->toArray(), [
                'display_name' => $class->display_name,
            ]));

        return response()->json($classes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateClassRequest($request);

        $class = SchoolClass::create($validated);

        return response()->json(array_merge($class->toArray(), [
            'display_name' => $class->display_name,
            'students_count' => 0,
        ]), 201);
    }

    public function update(Request $request, SchoolClass $class): JsonResponse
    {
        $validated = $this->validateClassRequest($request, $class->id);

        $class->update($validated);

        return response()->json(array_merge($class->fresh()->toArray(), [
            'display_name' => $class->display_name,
        ]));
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        $class->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classes' => ['required', 'array'],
            'classes.*.id' => ['required', 'integer', 'exists:school_classes,id'],
            'classes.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $map = collect($data['classes'])->keyBy('id');

        $classes = SchoolClass::whereIn('id', $map->keys())->get();

        foreach ($classes as $class) {
            $class->sort_order = $map[$class->id]['sort_order'];
            $class->save();
        }

        return response()->json(['message' => 'Class order updated.']);
    }

    /**
     * Generate a PDF marks sheet for the whole class showing Opener and Midterm scores.
     */
    public function marksReport(Request $request, SchoolClass $class): Response
    {
        $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'both' => ['sometimes', 'boolean'],
        ]);

        $exam = Exam::with('academicYear')->findOrFail($request->integer('exam_id'));
        $includeBoth = $request->boolean('both', false);

        $examsInYear = Exam::where('academic_year_id', $exam->academic_year_id)->get();
        $openerExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'open'));
        $midtermExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'mid'));

        // When user selects a single exam, show only that exam.
        if (! $includeBoth) {
            if ($exam->id === ($openerExam?->id)) {
                $midtermExam = null;
            } elseif ($exam->id === ($midtermExam?->id)) {
                $openerExam = null;
            } else {
                $openerExam = $exam;
                $midtermExam = null;
            }
        }

        $students = Student::where('class_id', $class->id)->orderBy('name')->get();

        $subjects = Subject::where('class_id', $class->id)
            ->orderBy('display_order')
            ->get(['id', 'name']);

        $examIds = collect([$openerExam?->id, $midtermExam?->id])->filter()->values();

        $allResults = Result::whereIn('exam_id', $examIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        $rows = $students->map(function (Student $student) use ($subjects, $allResults, $openerExam, $midtermExam) {
            $studentResults = $allResults->where('student_id', $student->id);

            $subjectRows = $subjects->map(function (Subject $subject) use ($studentResults, $openerExam, $midtermExam) {
                $openerResult = $openerExam
                    ? $studentResults->where('exam_id', $openerExam->id)->where('subject_id', $subject->id)->first()
                    : null;
                $midtermResult = $midtermExam
                    ? $studentResults->where('exam_id', $midtermExam->id)->where('subject_id', $subject->id)->first()
                    : null;

                $opener = $openerResult?->raw_score;
                $midterm = $midtermResult?->raw_score;
                $deviation = (is_numeric($opener) && is_numeric($midterm)) ? ($midterm - $opener) : null;

                return [
                    'subject' => $subject->name,
                    'subject_id' => $subject->id,
                    'opener' => $opener,
                    'midterm' => $midterm,
                    'deviation' => $deviation,
                    'grade' => ($midtermResult ?? $openerResult)?->cbc_sublevel ?? '—',
                ];
            })->values()->all();

            $openerTotal = collect($subjectRows)->sum(fn ($r) => $r['opener'] ?? 0);
            $midtermTotal = collect($subjectRows)->sum(fn ($r) => $r['midterm'] ?? 0);
            $openerCount = collect($subjectRows)->filter(fn ($r) => isset($r['opener']) && $r['opener'] !== null)->count();
            $midtermCount = collect($subjectRows)->filter(fn ($r) => isset($r['midterm']) && $r['midterm'] !== null)->count();

            $openerAvg = $openerCount > 0 ? round($openerTotal / $openerCount, 1) : null;
            $midtermAvg = $midtermCount > 0 ? round($midtermTotal / $midtermCount, 1) : null;

            return [
                'name' => $student->name,
                'subjects' => $subjectRows,
                'opener_total' => $openerTotal,
                'midterm_total' => $midtermTotal,
                'opener_average' => $openerAvg,
                'midterm_average' => $midtermAvg,
                'deviation_total' => ($openerTotal || $midtermTotal) ? ($midtermTotal - $openerTotal) : null,
            ];
        })->values()->all();

        // Keep only subjects that have at least one result for the active exam(s).
        $hasResult = $subjects->map(function (Subject $subject, int $i) use ($rows, $openerExam, $midtermExam) {
            $hasOpener = $openerExam && collect($rows)->contains(fn ($r) => isset($r['subjects'][$i]['opener']) && $r['subjects'][$i]['opener'] !== null);
            $hasMidterm = $midtermExam && collect($rows)->contains(fn ($r) => isset($r['subjects'][$i]['midterm']) && $r['subjects'][$i]['midterm'] !== null);

            return $hasOpener || $hasMidterm;
        })->all();

        $subjectsWithResults = $subjects->filter(fn ($_, int $i) => $hasResult[$i] ?? false)->values();
        $rows = collect($rows)->map(function (array $row) use ($hasResult) {
            $filteredSubjects = collect($row['subjects'])->filter(fn ($_, int $i) => $hasResult[$i] ?? false)->values()->all();
            $openerTotal = collect($filteredSubjects)->sum(fn ($r) => $r['opener'] ?? 0);
            $midtermTotal = collect($filteredSubjects)->sum(fn ($r) => $r['midterm'] ?? 0);
            $openerCount = collect($filteredSubjects)->filter(fn ($r) => isset($r['opener']) && $r['opener'] !== null)->count();
            $midtermCount = collect($filteredSubjects)->filter(fn ($r) => isset($r['midterm']) && $r['midterm'] !== null)->count();
            $openerAvg = $openerCount > 0 ? round($openerTotal / $openerCount, 1) : null;
            $midtermAvg = $midtermCount > 0 ? round($midtermTotal / $midtermCount, 1) : null;

            return [
                'name' => $row['name'],
                'subjects' => $filteredSubjects,
                'opener_total' => $openerTotal,
                'midterm_total' => $midtermTotal,
                'opener_average' => $openerAvg,
                'midterm_average' => $midtermAvg,
                'deviation_total' => ($openerTotal || $midtermTotal) ? ($midtermTotal - $openerTotal) : null,
            ];
        })->values()->all();

        $settings = SchoolSetting::first();
        $logoPath = $settings?->logo_path
            ? public_path('storage/'.ltrim($settings->logo_path, '/'))
            : null;

        $singleExamLabel = ($openerExam && ! $midtermExam) ? $openerExam->name : (($midtermExam && ! $openerExam) ? $midtermExam->name : null);

        $data = [
            'class' => $class,
            'subjects' => $subjectsWithResults,
            'rows' => $rows,
            'openerExam' => $openerExam,
            'midtermExam' => $midtermExam,
            'singleExamLabel' => $singleExamLabel,
            'settings' => $settings,
            'logoPath' => $logoPath,
        ];

        $pdf = Pdf::loadView('class-marks-report', $data)->setPaper('a4', 'landscape');

        $filename = str($class->display_name)->slug().'-marks-report.pdf';

        return $pdf->download($filename);
    }

    /** @return array<string, mixed> */
    private function validateClassRequest(Request $request, ?int $ignoreId = null): array
    {
        $isStreamed = in_array($request->input('level'), SchoolClass::STREAMED_LEVELS, true);

        $rules = [
            'level' => ['required', 'string', Rule::in(array_merge(SchoolClass::STREAMED_LEVELS, SchoolClass::NON_STREAMED_LEVELS))],
            'class_teacher_name' => ['required', 'string', 'max:255'],
        ];

        if ($isStreamed) {
            $rules['stream'] = [
                'required',
                Rule::in(['GREEN', 'RED']),
                Rule::unique('school_classes')->where('level', $request->input('level'))->ignore($ignoreId),
            ];
        } else {
            $rules['stream'] = ['nullable'];
        }

        $data = $request->validate($rules);

        if (! $isStreamed) {
            $data['stream'] = null;
        }

        return $data;
    }
}
