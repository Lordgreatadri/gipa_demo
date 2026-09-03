<?php

namespace App\Services;

use App\Models\ApiTokenSession;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class JwtTokenService
{
    public function issue(User $user, Request $request): array
    {
        $now = now();
        $accessExpiry = $now->copy()->addMinutes(config('iomp.api_tokens.access_ttl_minutes'));
        $refreshExpiry = $now->copy()->addDays(config('iomp.api_tokens.refresh_ttl_days'));
        $jti = (string) Str::uuid();
        $refreshToken = Str::random(96);

        ApiTokenSession::create([
            'user_id' => $user->id,
            'access_jti_hash' => hash('sha256', $jti),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'access_expires_at' => $accessExpiry,
            'refresh_expires_at' => $refreshExpiry,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        return [
            'token_type' => 'Bearer',
            'access_token' => JWT::encode([
                'iss' => config('app.url'),
                'aud' => 'iomp-api',
                'sub' => (string) $user->uuid,
                'jti' => $jti,
                'iat' => $now->timestamp,
                'nbf' => $now->timestamp,
                'exp' => $accessExpiry->timestamp,
                'account_type' => $user->account_type,
            ], $this->key(), 'HS256'),
            'expires_in' => $now->diffInSeconds($accessExpiry),
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $refreshExpiry->toIso8601String(),
        ];
    }

    public function refresh(string $refreshToken, Request $request): array
    {
        return DB::transaction(function () use ($refreshToken, $request): array {
            $session = ApiTokenSession::query()
                ->where('refresh_token_hash', hash('sha256', $refreshToken))
                ->lockForUpdate()
                ->first();

            if (! $session || $session->revoked_at || $session->refresh_expires_at->isPast() || ! $session->user->isActive()) {
                throw ValidationException::withMessages(['refresh_token' => 'The refresh token is invalid or expired.']);
            }

            $session->update(['revoked_at' => now(), 'last_used_at' => now()]);

            return $this->issue($session->user, $request);
        }, 3);
    }

    public function authenticate(string $token): ApiTokenSession
    {
        $claims = JWT::decode($token, new Key($this->key(), 'HS256'));
        if (($claims->iss ?? null) !== config('app.url') || ($claims->aud ?? null) !== 'iomp-api' || ! isset($claims->jti, $claims->sub)) {
            throw new RuntimeException('Invalid token claims.');
        }

        $session = ApiTokenSession::query()
            ->where('access_jti_hash', hash('sha256', $claims->jti))
            ->whereNull('revoked_at')
            ->where('access_expires_at', '>', now())
            ->with('user')
            ->firstOrFail();

        if ($session->user->uuid !== $claims->sub || ! $session->user->isActive()) {
            throw new RuntimeException('Inactive token subject.');
        }

        return $session;
    }

    public function revoke(ApiTokenSession $session): void
    {
        $session->update(['revoked_at' => now(), 'last_used_at' => now()]);
    }

    private function key(): string
    {
        $key = config('iomp.api_tokens.signing_key') ?: config('app.key');
        if (! is_string($key) || strlen($key) < 32) {
            throw new RuntimeException('A JWT signing key of at least 32 characters is required.');
        }

        return hash('sha256', $key, true);
    }
}
