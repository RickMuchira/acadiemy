<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/home', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/upload', fn () => Inertia::render('Upload'))->name('upload');
    Route::get('/classes', fn () => Inertia::render('Classes'))->name('classes');
    Route::get('/exams', fn () => Inertia::render('Exams'))->name('exams');
    Route::get('/report-cards', fn () => Inertia::render('ReportCards'))->name('report-cards');
    Route::get('/report-cards/{studentId}', fn (int $studentId) => Inertia::render('ReportCardPreview', ['studentId' => $studentId]))->name('report-cards.preview');
    Route::get('/results-grid', function () {
        $classId = (int) request()->query('class_id');
        $examId = (int) request()->query('exam_id');

        return Inertia::render('ResultsGrid', [
            'classId' => $classId,
            'examId' => $examId,
        ]);
    })->name('results-grid');
    Route::get('/school-settings', fn () => Inertia::render('SchoolSettings'))->name('school-settings');
});

require __DIR__.'/settings.php';
