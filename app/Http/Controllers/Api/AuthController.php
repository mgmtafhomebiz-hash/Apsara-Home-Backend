<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'middle_name'           => 'nullable|string|max:255',
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'username'              => 'required|string|unique:users,username|max:255',
            'phone'                 => 'nullable|string|max:20',
            'birth_date'            => 'nullable|date',
            'referred_by'           => 'nullable|string|max:255',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'username'    => $validated['username'],
            'phone'       => $validated['phone'] ?? null,
            'birth_date'  => $validated['birth_date'] ?? null,
            'referred_by' => $validated['referred_by'] ?? null,
            'password'    => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Registration successful.'], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
