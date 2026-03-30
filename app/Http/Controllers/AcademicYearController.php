<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(AcademicYear::orderByDesc('year')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'string', 'max:10', 'unique:academic_years,year'],
        ]);

        $academicYear = AcademicYear::create($validated);

        return response()->json($academicYear, 201);
    }
}
