<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(SchoolClass $class): JsonResponse
    {
        return response()->json($class->subjects()->get());
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'display_order' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        $subject->update($validated);

        return response()->json($subject->fresh());
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subjects' => ['required', 'array'],
            'subjects.*.id' => ['required', 'integer', 'exists:subjects,id'],
            'subjects.*.display_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['subjects'] as $item) {
            Subject::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return response()->json(['message' => 'Reordered successfully']);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $subject->delete();

        return response()->json(null, 204);
    }

    /**
     * Merge source subject into target. All results move to target, source is deleted.
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_id' => ['required', 'integer', 'exists:subjects,id'],
            'target_id' => ['required', 'integer', 'exists:subjects,id', 'different:source_id'],
        ]);

        $source = Subject::findOrFail($validated['source_id']);
        $target = Subject::findOrFail($validated['target_id']);

        if ($source->class_id !== $target->class_id) {
            return response()->json(['message' => 'Subjects must belong to the same class.'], 422);
        }

        Result::where('subject_id', $source->id)->update(['subject_id' => $target->id]);
        $source->delete();

        return response()->json(['message' => "Merged '{$source->name}' into '{$target->name}'.", 'subjects' => Subject::where('class_id', $target->class_id)->orderBy('display_order')->get()]);
    }
}
