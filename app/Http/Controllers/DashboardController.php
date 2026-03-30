<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    /**
     * Build analytics data for dashboard and PDF report.
     *
     * @return array<string, mixed>
     */
    public function analytics(): JsonResponse
    {
        $data = $this->buildAnalyticsData();

        return response()->json($data);
    }

    /**
     * Generate PDF report of academic analytics.
     * Optional query param: include (comma-separated) e.g. summary,school_averages,class_performance,years_exams
     * If omitted, all sections are included.
     */
    public function analyticsPdf(Request $request): Response
    {
        $data = $this->buildAnalyticsData();
        $data['logoPath'] = $this->getLogoPath();
        $data['generatedAt'] = now()->format('F j, Y \a\t g:i A');

        $includeParam = $request->query('include');
        $allowed = ['summary', 'school_averages', 'class_performance', 'years_exams', 'footer'];
        if ($includeParam !== null && $includeParam !== '') {
            $requested = array_map('trim', explode(',', (string) $includeParam));
            $data['sections'] = array_fill_keys($allowed, false);
            foreach ($requested as $key) {
                if (in_array($key, $allowed, true)) {
                    $data['sections'][$key] = true;
                }
            }
        } else {
            $data['sections'] = array_fill_keys($allowed, true);
        }

        $data['showDeviation'] = $request->query('show_deviation', '1') !== '0';

        $pdf = Pdf::loadView('dashboard-analytics-report', $data)->setPaper('a4', 'portrait');

        return $pdf->download('academic-analytics-report.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAnalyticsData(): array
    {
        $totalStudents = Student::count();
        $totalClasses = SchoolClass::count();
        $academicYears = AcademicYear::with('exams')->orderByDesc('year')->get();
        $totalExams = Exam::count();

        $yearsWithExams = $academicYears->map(function ($year) {
            $exams = $year->exams;
            $opener = $exams->first(fn (Exam $e) => str_contains(strtolower($e->name), 'open'));
            $midterm = $exams->first(fn (Exam $e) => str_contains(strtolower($e->name), 'mid'));

            return [
                'year' => $year->year,
                'exam_count' => $exams->count(),
                'has_opener' => (bool) $opener,
                'has_midterm' => (bool) $midterm,
            ];
        });

        $classSummaries = SchoolClass::with(['subjects', 'students'])
            ->withCount('students')
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
            ->map(function (SchoolClass $class) {
                $studentIds = $class->students->pluck('id');
                $examsInYear = Exam::whereHas('academicYear')->get();
                $openerExam = $examsInYear->first(fn (Exam $e) => str_contains(strtolower($e->name), 'open'));
                $midtermExam = $examsInYear->first(fn (Exam $e) => str_contains(strtolower($e->name), 'mid'));

                $openerAvg = null;
                $midtermAvg = null;
                $studentsWithOpener = 0;
                $studentsWithMidterm = 0;

                if ($studentIds->isNotEmpty() && ($openerExam || $midtermExam)) {
                    if ($openerExam) {
                        $openerResults = Result::where('exam_id', $openerExam->id)->whereIn('student_id', $studentIds)->get();
                        $openerScores = $openerResults->pluck('raw_score')->filter();
                        $openerAvg = $openerScores->isNotEmpty() ? round($openerScores->avg(), 1) : null;
                        $studentsWithOpener = $openerResults->pluck('student_id')->unique()->count();
                    }
                    if ($midtermExam) {
                        $midtermResults = Result::where('exam_id', $midtermExam->id)->whereIn('student_id', $studentIds)->get();
                        $midtermScores = $midtermResults->pluck('raw_score')->filter();
                        $midtermAvg = $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 1) : null;
                        $studentsWithMidterm = $midtermResults->pluck('student_id')->unique()->count();
                    }
                }

                return [
                    'class_name' => $class->display_name,
                    'student_count' => $class->students_count,
                    'opener_avg' => $openerAvg,
                    'midterm_avg' => $midtermAvg,
                    'students_with_opener' => $studentsWithOpener,
                    'students_with_midterm' => $studentsWithMidterm,
                    'missing_opener' => $class->students_count - $studentsWithOpener,
                    'missing_midterm' => $class->students_count - $studentsWithMidterm,
                ];
            });

        $overallOpenerAvg = null;
        $overallMidtermAvg = null;
        $openerExam = Exam::whereHas('academicYear')->get()->first(fn (Exam $e) => str_contains(strtolower($e->name), 'open'));
        $midtermExam = Exam::whereHas('academicYear')->get()->first(fn (Exam $e) => str_contains(strtolower($e->name), 'mid'));
        if ($openerExam) {
            $overallOpenerAvg = $openerExam->results()->avg('raw_score');
            $overallOpenerAvg = $overallOpenerAvg !== null ? round((float) $overallOpenerAvg, 1) : null;
        }
        if ($midtermExam) {
            $overallMidtermAvg = $midtermExam->results()->avg('raw_score');
            $overallMidtermAvg = $overallMidtermAvg !== null ? round((float) $overallMidtermAvg, 1) : null;
        }

        // Subject averages per class
        $subjectAverages = [];
        foreach (SchoolClass::with(['subjects', 'students'])
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
            ->get() as $class) {
            if ($class->students->isEmpty() || $class->subjects->isEmpty()) {
                continue;
            }

            foreach ($class->subjects as $subject) {
                $openerAvgSubject = null;
                $midtermAvgSubject = null;

                if ($openerExam) {
                    $openerAvgSubject = $openerExam->results()
                        ->where('subject_id', $subject->id)
                        ->whereIn('student_id', $class->students->pluck('id'))
                        ->avg('raw_score');
                    $openerAvgSubject = $openerAvgSubject !== null ? round((float) $openerAvgSubject, 1) : null;
                }

                if ($midtermExam) {
                    $midtermAvgSubject = $midtermExam->results()
                        ->where('subject_id', $subject->id)
                        ->whereIn('student_id', $class->students->pluck('id'))
                        ->avg('raw_score');
                    $midtermAvgSubject = $midtermAvgSubject !== null ? round((float) $midtermAvgSubject, 1) : null;
                }

                if ($openerAvgSubject === null && $midtermAvgSubject === null) {
                    continue;
                }

                $subjectAverages[] = [
                    'class_name' => $class->display_name,
                    'subject_name' => $subject->name,
                    'opener_avg' => $openerAvgSubject,
                    'midterm_avg' => $midtermAvgSubject,
                ];
            }
        }

        $settings = SchoolSetting::first();

        return [
            'summary' => [
                'total_students' => $totalStudents,
                'total_classes' => $totalClasses,
                'total_exams' => $totalExams,
                'total_academic_years' => $academicYears->count(),
                'overall_opener_avg' => $overallOpenerAvg,
                'overall_midterm_avg' => $overallMidtermAvg,
            ],
            'years_with_exams' => $yearsWithExams,
            'class_summaries' => $classSummaries,
            'subject_averages' => $subjectAverages,
            'settings' => $settings,
        ];
    }

    private function getLogoPath(): ?string
    {
        $settings = SchoolSetting::first();
        $path = $settings?->logo_path ? ltrim($settings->logo_path, '/') : null;

        return $path ? public_path('storage/'.$path) : null;
    }
}
