<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->tokenResponse(JWTAuth::fromUser($user), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = JWTAuth::attempt($request->validated());

        if (! $token) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return $this->tokenResponse($token);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(): JsonResponse
    {
        // Stateless JWT: no server-side session/blacklist to clear, the
        // client is responsible for discarding the token.
        return response()->json(['message' => 'Logged out.']);
    }

    private function tokenResponse(string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], $status);
    }
}
