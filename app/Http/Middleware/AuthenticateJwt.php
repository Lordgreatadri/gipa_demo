<?php

namespace App\Http\Middleware;

use App\Services\JwtTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateJwt
{
    public function __construct(private readonly JwtTokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        try {
            $session = $this->tokens->authenticate($token);
        } catch (Throwable) {
            return response()->json(['message' => 'The access token is invalid or expired.'], 401);
        }

        $request->setUserResolver(fn () => $session->user);
        $request->attributes->set('api_token_session', $session);

        return $next($request);
    }
}
