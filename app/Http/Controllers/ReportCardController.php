<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Student;
use App\Services\ReportCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportCardController extends Controller
{
    public function __construct(private readonly ReportCardService $reportCardService) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);

        $classId = $request->integer('class_id');
        $examId = $request->integer('exam_id');

        $exam = Exam::with('academicYear')->findOrFail($examId);

        $examsInYear = Exam::where('academic_year_id', $exam->academic_year_id)->get();

        $openerExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'open'));
        $midtermExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'mid'));

        // When both opener and midterm exist, always return richer status info so the
        // frontend can describe which exam's marks are missing.
        if ($openerExam && $midtermExam) {
            $students = Student::where('class_id', $classId)
                ->orderBy('name')
                ->withCount([
                    'results as opener_results_count' => function ($query) use ($openerExam): void {
                        $query->where('exam_id', $openerExam->id);
                    },
                    'results as midterm_results_count' => function ($query) use ($midtermExam): void {
                        $query->where('exam_id', $midtermExam->id);
                    },
                ])
                ->get()
                ->map(function (Student $student) {
                    $openerCount = (int) ($student->opener_results_count ?? 0);
                    $midtermCount = (int) ($student->midterm_results_count ?? 0);
                    $total = $openerCount + $midtermCount;

                    $status = 'No results';
                    if ($openerCount > 0 && $midtermCount > 0) {
                        $status = 'Has opener & midterm';
                    } elseif ($openerCount > 0 && $midtermCount === 0) {
                        $status = 'Missing all midterm marks';
                    } elseif ($openerCount === 0 && $midtermCount > 0) {
                        $status = 'Missing all opener marks';
                    }

                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'has_results' => $total,
                        'opener_results' => $openerCount,
                        'midterm_results' => $midtermCount,
                        'status' => $status,
                    ];
                })
                ->values();

            return response()->json($students);
        }

        // Default behaviour: single exam context
        $students = Student::where('class_id', $classId)
            ->orderBy('name')
            ->withCount(['results as has_results' => function ($query) use ($examId): void {
                $query->where('exam_id', $examId);
            }])
            ->get();

        return response()->json($students);
    }

    public function pdf(Request $request): Response
    {
        $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);

        $showDeviation = $request->query('show_deviation', '1') !== '0';

        $pdf = $this->reportCardService->generatePdf(
            $request->integer('student_id'),
            $request->integer('exam_id'),
            $showDeviation
        );

        $student = Student::find($request->integer('student_id'));

        return $pdf->download(str($student->name)->slug().'-report-card.pdf');
    }

    public function bulkPdf(Request $request): BinaryFileResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);

        $showDeviation = $request->query('show_deviation', '1') !== '0';

        $result = $this->reportCardService->generateBulkZip(
            $request->integer('class_id'),
            $request->integer('exam_id'),
            $showDeviation
        );

        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);
    }

    public function preview(Request $request, int $studentId): JsonResponse
    {
        $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);

        $data = $this->reportCardService->buildReportData($studentId, $request->integer('exam_id'));

        return response()->json([
            'student' => $data['student'],
            'settings' => $data['settings'],
            'pages' => $data['pages'],
            'gradingScale' => $data['gradingScale'],
            'logoUrl' => $data['logoUrl'],
        ]);
    }
}
