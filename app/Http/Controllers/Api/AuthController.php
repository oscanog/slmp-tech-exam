<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($validated)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::query()->where('email', $validated['email'])->firstOrFail();
        $tokenResult = $user->createToken('slmp-api-token', ['resources:read', 'resources:write']);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
            'expires_at' => optional($tokenResult->token->expires_at)->toISOString(),
            'scopes' => ['resources:read', 'resources:write'],
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->token()?->revoke();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): User
    {
        return $request->user();
    }
}
