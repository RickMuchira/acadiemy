<?php

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

// ── Academic Years ────────────────────────────────────────────────────────────

test('admin can list academic years', function (): void {
    AcademicYear::create(['year' => '2025']);

    $this->getJson('/api/academic-years')
        ->assertOk()
        ->assertJsonCount(1);
});

test('admin can create an academic year', function (): void {
    $this->postJson('/api/academic-years', ['year' => '2026'])
        ->assertCreated()
        ->assertJsonFragment(['year' => '2026']);
});

test('duplicate academic year is rejected', function (): void {
    AcademicYear::create(['year' => '2025']);

    $this->postJson('/api/academic-years', ['year' => '2025'])
        ->assertUnprocessable();
});

// ── Exams ─────────────────────────────────────────────────────────────────────

test('admin can create an exam', function (): void {
    $year = AcademicYear::create(['year' => '2025']);

    $this->postJson('/api/exams', ['academic_year_id' => $year->id, 'name' => 'Opening Exam'])
        ->assertCreated()
        ->assertJsonFragment(['name' => 'Opening Exam']);
});

// ── Classes ───────────────────────────────────────────────────────────────────

test('admin can create a streamed class', function (): void {
    $this->postJson('/api/classes', [
        'level' => 'PP1',
        'stream' => 'GREEN',
        'class_teacher_name' => 'Mrs. Wambua',
    ])
        ->assertCreated()
        ->assertJsonFragment(['display_name' => 'PP1 GREEN']);
});

test('admin can create a non-streamed class', function (): void {
    $this->postJson('/api/classes', [
        'level' => 'Grade4',
        'stream' => null,
        'class_teacher_name' => 'Mr. Omondi',
    ])
        ->assertCreated()
        ->assertJsonFragment(['display_name' => 'Grade 4']);
});

test('stream is required for streamed level', function (): void {
    $this->postJson('/api/classes', [
        'level' => 'PP2',
        'class_teacher_name' => 'Mrs. Kamau',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['stream']);
});

test('duplicate class is rejected', function (): void {
    SchoolClass::create(['level' => 'PP1', 'stream' => 'GREEN', 'class_teacher_name' => 'Test']);

    $this->postJson('/api/classes', [
        'level' => 'PP1',
        'stream' => 'GREEN',
        'class_teacher_name' => 'Another',
    ])->assertUnprocessable();
});

// ── Subjects ──────────────────────────────────────────────────────────────────

test('admin can list subjects for a class', function (): void {
    $class = SchoolClass::create(['level' => 'Grade4', 'stream' => null, 'class_teacher_name' => '']);
    Subject::create(['class_id' => $class->id, 'name' => 'Mathematics', 'display_order' => 0]);

    $this->getJson("/api/classes/{$class->id}/subjects")
        ->assertOk()
        ->assertJsonFragment(['name' => 'Mathematics']);
});

test('admin can reorder subjects', function (): void {
    $class = SchoolClass::create(['level' => 'Grade4', 'stream' => null, 'class_teacher_name' => '']);
    $subA = Subject::create(['class_id' => $class->id, 'name' => 'Maths', 'display_order' => 0]);
    $subB = Subject::create(['class_id' => $class->id, 'name' => 'English', 'display_order' => 1]);

    $this->putJson('/api/subjects/reorder', [
        'subjects' => [
            ['id' => $subA->id, 'display_order' => 1],
            ['id' => $subB->id, 'display_order' => 0],
        ],
    ])->assertOk();

    expect(Subject::find($subA->id)->display_order)->toBe(1)
        ->and(Subject::find($subB->id)->display_order)->toBe(0);
});

test('admin can merge subjects', function (): void {
    $class = SchoolClass::create(['level' => 'Grade4', 'stream' => null, 'class_teacher_name' => '']);
    $math = Subject::create(['class_id' => $class->id, 'name' => 'Math', 'display_order' => 0]);
    $mathematics = Subject::create(['class_id' => $class->id, 'name' => 'Mathematics', 'display_order' => 1]);
    $student = Student::create(['name' => 'Jane', 'class_id' => $class->id]);
    $exam = Exam::create(['academic_year_id' => AcademicYear::create(['year' => '2025'])->id, 'name' => 'Opener']);

    Result::create([
        'student_id' => $student->id,
        'exam_id' => $exam->id,
        'subject_id' => $math->id,
        'raw_score' => 85,
        'cbc_sublevel' => 'ME1',
        'cbc_grade' => 'ME',
    ]);

    $this->postJson('/api/subjects/merge', ['source_id' => $math->id, 'target_id' => $mathematics->id])
        ->assertOk()
        ->assertJsonFragment(['name' => 'Mathematics']);

    expect(Subject::find($math->id))->toBeNull()
        ->and(Result::where('subject_id', $mathematics->id)->count())->toBe(1);
});

// ── Settings ──────────────────────────────────────────────────────────────────

test('admin can view settings', function (): void {
    SchoolSetting::create([
        'school_name' => 'Circle Spring Academy',
        'motto' => 'My Character, My Destiny',
        'location' => 'Nairobi, Kenya',
    ]);

    $this->getJson('/api/settings')
        ->assertOk()
        ->assertJsonFragment(['school_name' => 'Circle Spring Academy']);
});

test('admin can update settings', function (): void {
    SchoolSetting::create([
        'school_name' => 'Old Name',
        'motto' => 'Old Motto',
        'location' => 'Old Location',
    ]);

    $this->postJson('/api/settings', [
        'school_name' => 'New Name',
        'motto' => 'New Motto',
        'location' => 'New Location',
    ])->assertOk()->assertJsonFragment(['school_name' => 'New Name']);
});

// ── Report Cards ──────────────────────────────────────────────────────────────

test('admin can list students for a report card', function (): void {
    $class = SchoolClass::create(['level' => 'Grade4', 'stream' => null, 'class_teacher_name' => '']);
    $year = AcademicYear::create(['year' => '2025']);
    $exam = Exam::create(['academic_year_id' => $year->id, 'name' => 'Midterm']);
    Student::create(['name' => 'Alice', 'class_id' => $class->id]);

    $this->getJson("/api/report-cards?class_id={$class->id}&exam_id={$exam->id}")
        ->assertOk()
        ->assertJsonFragment(['name' => 'Alice']);
});

test('admin can merge duplicate students', function (): void {
    $class = SchoolClass::create(['level' => 'Grade4', 'stream' => null, 'class_teacher_name' => '']);
    $year = AcademicYear::create(['year' => '2025']);
    $exam = Exam::create(['academic_year_id' => $year->id, 'name' => 'Opener']);

    $glenBaraka = Student::create(['name' => 'Glen Dlton Baraka', 'class_id' => $class->id]);
    $glenDalton = Student::create(['name' => 'Glen Dalton', 'class_id' => $class->id]);

    Result::create([
        'student_id' => $glenBaraka->id,
        'exam_id' => $exam->id,
        'subject_id' => Subject::create(['class_id' => $class->id, 'name' => 'Math', 'display_order' => 0])->id,
        'raw_score' => 80,
        'cbc_sublevel' => 'ME1',
        'cbc_grade' => 'ME',
    ]);

    $this->postJson('/api/students/merge', [
        'source_id' => $glenBaraka->id,
        'target_id' => $glenDalton->id,
        'keep_name' => 'target',
    ])->assertOk();

    expect(Student::find($glenBaraka->id))->toBeNull()
        ->and(Result::where('student_id', $glenDalton->id)->count())->toBe(1)
        ->and(Student::find($glenDalton->id)->name)->toBe('Glen Dalton');
});
