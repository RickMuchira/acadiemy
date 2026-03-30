<?php

namespace App\Http\Controllers;

use App\Services\ExcelImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private readonly ExcelImportService $importService) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls,ods'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'preview' => ['required', 'boolean'],
        ]);

        $path = $request->file('file')->store('uploads/marks', 'local');
        $fullPath = storage_path('app/private/'.$path);

        if ($request->boolean('preview')) {
            $previewData = $this->importService->preview($fullPath, $request->integer('class_id'));

            return response()->json([
                'preview' => true,
                'path' => $path,
                'students' => $previewData['students'],
                'subjects' => $previewData['subjects'],
            ]);
        }

        $this->importService->commit($fullPath, $request->integer('exam_id'), $request->integer('class_id'));

        return response()->json(['message' => 'Marks imported successfully.']);
    }

    public function commit(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
        ]);

        $fullPath = storage_path('app/private/'.$request->input('path'));

        $this->importService->commit($fullPath, $request->integer('exam_id'), $request->integer('class_id'));

        return response()->json(['message' => 'Marks imported successfully.']);
    }
}
