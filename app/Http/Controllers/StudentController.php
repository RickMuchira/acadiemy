<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::with('schoolClass');

        if ($request->has('class_id')) {
            $query->where('class_id', $request->integer('class_id'));
        }

        return response()->json($query->orderBy('name')->get());
    }

    /**
     * Merge source student into target. All results move to target, source is deleted.
     * Use when same person was imported with different names (e.g. Opener vs Midterm).
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_id' => ['required', 'integer', 'exists:students,id'],
            'target_id' => ['required', 'integer', 'exists:students,id', 'different:source_id'],
            'keep_name' => ['nullable', 'string', 'in:source,target'],
        ]);

        /** @var Student $source */
        /** @var Student $target */
        $source = Student::findOrFail($validated['source_id']);
        $target = Student::findOrFail($validated['target_id']);

        if ($source->class_id !== $target->class_id) {
            return response()->json(['message' => 'Students must be in the same class.'], 422);
        }

        DB::transaction(function () use ($source, $target, $validated): void {
            $conflictingSourceResultIds = Result::query()
                ->where('student_id', $source->id)
                ->whereExists(function (Builder $query) use ($target): void {
                    $query->from('results as existing_results')
                        ->whereColumn('existing_results.exam_id', 'results.exam_id')
                        ->whereColumn('existing_results.subject_id', 'results.subject_id')
                        ->where('existing_results.student_id', $target->id);
                })
                ->pluck('id');

            if ($conflictingSourceResultIds->isNotEmpty()) {
                Result::whereIn('id', $conflictingSourceResultIds)->delete();
            }

            Result::where('student_id', $source->id)->update(['student_id' => $target->id]);

            if (($validated['keep_name'] ?? 'target') === 'source') {
                $target->update(['name' => $source->name]);
            }

            $source->delete();
        });

        return response()->json([
            'message' => "Merged records. Kept '{$target->fresh()->name}'.",
            'students' => Student::where('class_id', $target->class_id)->orderBy('name')->get(),
        ]);
    }
}
