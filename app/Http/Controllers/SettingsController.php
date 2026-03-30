<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(SchoolSetting::first());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'motto' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'head_teacher_name' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = SchoolSetting::first();
        $settings->update($validated);

        return response()->json($settings->fresh());
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ]);

        $settings = SchoolSetting::first();

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');

        $settings->update(['logo_path' => $path]);

        return response()->json(['logo_path' => $path, 'logo_url' => asset('storage/'.$path)]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
