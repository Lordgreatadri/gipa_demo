<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::query()->where('email', Str::lower(trim($credentials['email'])))->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->isActive()) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are invalid.']);
        }

        return response()->json($tokens->issue($user, $request));
    }

    public function refresh(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $data = $request->validate(['refresh_token' => ['required', 'string', 'size:96']]);

        return response()->json($tokens->refresh($data['refresh_token'], $request));
    }

    public function logout(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $tokens->revoke($request->attributes->get('api_token_session'));

        return response()->json(['message' => 'Token session revoked.']);
    }
}
