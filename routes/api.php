<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'web'])->group(function (): void {
    // Dashboard analytics
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
    Route::get('/dashboard/analytics-pdf', [DashboardController::class, 'analyticsPdf']);

    // Academic Years
    Route::get('/academic-years', [AcademicYearController::class, 'index']);
    Route::post('/academic-years', [AcademicYearController::class, 'store']);

    // Exams
    Route::get('/exams', [ExamController::class, 'index']);
    Route::post('/exams', [ExamController::class, 'store']);
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy']);

    // Classes
    Route::get('/classes', [ClassController::class, 'index']);
    Route::post('/classes', [ClassController::class, 'store']);
    Route::put('/classes/{class}', [ClassController::class, 'update']);
    Route::delete('/classes/{class}', [ClassController::class, 'destroy']);
    Route::put('/classes/reorder', [ClassController::class, 'reorder']);
    Route::get('/classes/{class}/marks-report', [ClassController::class, 'marksReport']);

    // Subjects
    Route::get('/classes/{class}/subjects', [SubjectController::class, 'index']);
    Route::put('/subjects/reorder', [SubjectController::class, 'reorder']);
    Route::post('/subjects/merge', [SubjectController::class, 'merge']);
    Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);

    // Students
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students/merge', [StudentController::class, 'merge']);

    // Upload
    Route::post('/upload-marks', [UploadController::class, 'upload']);
    Route::post('/upload-marks/commit', [UploadController::class, 'commit']);

    // Report Cards
    Route::get('/report-cards', [ReportCardController::class, 'index']);
    Route::get('/report-cards/bulk-pdf', [ReportCardController::class, 'bulkPdf']);
    Route::get('/report-card/pdf', [ReportCardController::class, 'pdf']);
    Route::get('/report-card/preview/{studentId}', [ReportCardController::class, 'preview']);

    // Results grid editor
    Route::get('/results/grid', [ResultController::class, 'grid']);
    Route::put('/results/grid', [ResultController::class, 'updateGrid']);
    Route::delete('/results', [ResultController::class, 'destroyForClass']);

    // Settings
    Route::get('/settings', [SettingsController::class, 'show']);
    Route::post('/settings', [SettingsController::class, 'update']);
    Route::post('/settings/logo', [SettingsController::class, 'uploadLogo']);
    Route::post('/settings/password', [SettingsController::class, 'changePassword']);
});
