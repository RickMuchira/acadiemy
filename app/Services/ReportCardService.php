<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class ReportCardService
{
    public function __construct(private readonly CbcGradingService $gradingService) {}

    /**
     * Build the view data for a student's report card.
     * Each exam (Opener, Midterm) gets its own page with separate subjects.
     *
     * @return array<string, mixed>
     */
    public function buildReportData(int $studentId, int $examId, bool $showDeviation = true): array
    {
        $exam = Exam::with('academicYear')->findOrFail($examId);
        $settings = SchoolSetting::first();

        // Detect opener and midterm exams in the same academic year.
        $examsInYear = Exam::where('academic_year_id', $exam->academic_year_id)->get();

        $openerExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'open'));
        $midtermExam = $examsInYear->first(fn (Exam $e): bool => str_contains(strtolower($e->name), 'mid'));

        $student = Student::with([
            'schoolClass',
            'results' => function ($q) use ($openerExam, $midtermExam, $exam): void {
                $examIds = collect();

                if ($openerExam && $midtermExam) {
                    $examIds = collect([$openerExam->id, $midtermExam->id]);
                } else {
                    $examIds = collect([$exam->id]);
                }

                $q->whereIn('exam_id', $examIds)->with('subject');
            },
        ])->findOrFail($studentId);

        $descriptions = [
            'EE' => 'Exceeding Expectation',
            'ME' => 'Meeting Expectation',
            'AE' => 'Approaching Expectation',
            'BE' => 'Below Expectation',
        ];

        // When both opener and midterm exist, build a single comparison page.
        if ($openerExam && $midtermExam) {
            $allResults = $student->results->whereIn('exam_id', [$openerExam->id, $midtermExam->id])->values();

            $groupedBySubject = $allResults->groupBy('subject_id');

            $rows = $groupedBySubject->map(function ($resultsForSubject) use ($openerExam, $midtermExam, $descriptions) {
                $openerResult = $resultsForSubject->firstWhere('exam_id', $openerExam->id);
                $midtermResult = $resultsForSubject->firstWhere('exam_id', $midtermExam->id);

                $subjectName = $openerResult?->subject?->name
                    ?? $midtermResult?->subject?->name
                    ?? '—';

                $gradeSource = $midtermResult ?? $openerResult;
                $grade = $gradeSource?->cbc_grade ?? '';
                $sublevel = $gradeSource?->cbc_sublevel ?? '';

                return [
                    'subject' => $subjectName,
                    'opener_score' => $openerResult?->raw_score,
                    'midterm_score' => $midtermResult?->raw_score,
                    'sublevel' => $sublevel,
                    'grade' => $grade,
                    'description' => $grade !== '' ? ($descriptions[$grade] ?? '—') : '—',
                ];
            })->values();

            $openerScores = $rows->pluck('opener_score')->filter()->values();
            $midtermScores = $rows->pluck('midterm_score')->filter()->values();

            $openerTotal = $openerScores->sum();
            $midtermTotal = $midtermScores->sum();

            $openerCount = $openerScores->count();
            $midtermCount = $midtermScores->count();

            $openerAverage = $openerCount > 0 ? round($openerTotal / $openerCount, 2) : 0;
            $midtermAverage = $midtermCount > 0 ? round($midtermTotal / $midtermCount, 2) : 0;

            // Use midterm average as the overall performance indicator when available.
            $overallAverage = $midtermCount > 0 ? $midtermAverage : $openerAverage;
            $averageSublevel = ($openerCount > 0 || $midtermCount > 0)
                ? $this->gradingService->getSubLevel((int) round($overallAverage))
                : null;
            $averageDesc = $averageSublevel ? ($descriptions[substr($averageSublevel, 0, 2)] ?? null) : null;

            $pages = [[
                'exam' => $exam,
                'examName' => $openerExam->name.' & '.$midtermExam->name,
                'rows' => $rows,
                'total' => $midtermTotal ?: $openerTotal,
                'average' => $overallAverage,
                'averageSublevel' => $averageSublevel,
                'averageDescription' => $averageDesc,
                'opener_total' => $openerTotal,
                'midterm_total' => $midtermTotal,
                'opener_average' => $openerAverage,
                'midterm_average' => $midtermAverage,
            ]];
        } else {
            // Fallback: only one exam available; treat it as opener and leave midterm empty.
            $singleExamResults = $student->results->where('exam_id', $exam->id)->values();

            $rows = $singleExamResults->map(function ($result) use ($descriptions) {
                $grade = $result->cbc_grade ?? '';
                $sublevel = $result->cbc_sublevel ?? '';

                return [
                    'subject' => $result->subject?->name ?? '—',
                    'opener_score' => $result->raw_score,
                    'midterm_score' => null,
                    'sublevel' => $sublevel,
                    'grade' => $grade,
                    'description' => $grade !== '' ? ($descriptions[$grade] ?? '—') : '—',
                ];
            });

            $openerScores = $rows->pluck('opener_score')->filter()->values();
            $openerTotal = $openerScores->sum();
            $openerCount = $openerScores->count();
            $openerAverage = $openerCount > 0 ? round($openerTotal / $openerCount, 2) : 0;

            $averageSublevel = $openerCount > 0 ? $this->gradingService->getSubLevel((int) round($openerAverage)) : null;
            $averageDesc = $averageSublevel ? ($descriptions[substr($averageSublevel, 0, 2)] ?? null) : null;

            $pages = [[
                'exam' => $exam,
                'examName' => $exam->name,
                'rows' => $rows,
                'total' => $openerTotal,
                'average' => $openerAverage,
                'averageSublevel' => $averageSublevel,
                'averageDescription' => $averageDesc,
                'opener_total' => $openerTotal,
                'midterm_total' => null,
                'opener_average' => $openerAverage,
                'midterm_average' => null,
            ]];
        }

        $logoRelativePath = $settings?->logo_path ? ltrim($settings->logo_path, '/') : null;

        return [
            'student' => $student,
            'settings' => $settings,
            'pages' => $pages,
            'gradingScale' => $this->gradingService->getGradingScale(),
            'logoPath' => $logoRelativePath ? public_path('storage/'.$logoRelativePath) : null,
            'logoUrl' => $logoRelativePath ? asset('storage/'.$logoRelativePath) : null,
            'showDeviation' => $showDeviation,
        ];
    }

    /**
     * Generate a PDF for a single student.
     */
    public function generatePdf(int $studentId, int $examId, bool $showDeviation = true): \Barryvdh\DomPDF\PDF
    {
        $data = $this->buildReportData($studentId, $examId, $showDeviation);

        return Pdf::loadView('report-card', $data)
            ->setPaper('a4', 'portrait');
    }

    /**
     * Generate a ZIP archive containing PDFs for all students in a class for an exam.
     *
     * @return array{path: string, filename: string}
     */
    public function generateBulkZip(int $classId, int $examId, bool $showDeviation = true): array
    {
        $schoolClass = SchoolClass::with('students')->findOrFail($classId);
        $exam = Exam::with('academicYear')->findOrFail($examId);

        $zipFilename = 'report-cards-'.str($schoolClass->display_name)->slug().'-'.str($exam->name)->slug().'.zip';
        $zipPath = storage_path('app/private/temp/'.$zipFilename);

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($schoolClass->students as $student) {
            $pdf = $this->generatePdf($student->id, $examId, $showDeviation);
            $pdfFilename = str($student->name)->slug().'-report-card.pdf';
            $zip->addFromString($pdfFilename, $pdf->output());
        }

        $zip->close();

        return ['path' => $zipPath, 'filename' => $zipFilename];
    }
}
