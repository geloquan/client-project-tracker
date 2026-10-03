<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonResponse;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): LaravelJsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $request->session()->regenerate();

        return JsonResponse::success($request->user(), 'Signed in successfully.');
    }

    public function profile(Request $request): LaravelJsonResponse
    {
        return JsonResponse::success($request->user(), 'Profile retrieved successfully.');
    }

    public function updateProfile(Request $request): LaravelJsonResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($attributes);

        return JsonResponse::success($request->user()->fresh(), 'Name updated successfully.');
    }

    public function changePassword(Request $request): LaravelJsonResponse
    {
        $attributes = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($attributes['password']),
        ]);

        return JsonResponse::success(null, 'Password changed successfully.');
    }

    public function logout(Request $request): LaravelJsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return JsonResponse::success(null, 'Signed out successfully.');
    }
}
