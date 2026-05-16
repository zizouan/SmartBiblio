<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class AuthTokenService
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function issueFor(User $user): array
    {
        $access = $this->jwtService->issueAccessToken($user->id);
        $refresh = $this->createRefreshToken($user);

        return [
            'access_token' => $access['token'],
            'expires_in' => $access['expires_in'],
            'refresh_token' => $refresh['plain'],
            'refresh_expires_in' => $refresh['expires_in'],
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $hash = hash('sha256', $refreshToken);
        $stored = RefreshToken::query()->where('token_hash', $hash)->first();

        if (! $stored || $stored->revoked_at || $stored->expires_at->isPast()) {
            throw new ApiException('AUTH_INVALID_REFRESH', 'Refresh token is invalid or expired.', 401);
        }

        $user = $stored->user;

        if (! $user || ! $user->is_active) {
            throw new ApiException('AUTH_USER_INACTIVE', 'User is inactive.', 403);
        }

        $stored->forceFill([
            'revoked_at' => now(),
            'last_used_at' => now(),
        ])->save();

        return $this->issueFor($user);
    }

    public function logout(User $user, ?string $refreshToken, ?array $accessPayload = null): void
    {
        if ($refreshToken) {
            $hash = hash('sha256', $refreshToken);
            RefreshToken::query()->where('user_id', $user->id)->where('token_hash', $hash)->update(['revoked_at' => now()]);
        }

        if ($accessPayload && isset($accessPayload['jti'], $accessPayload['exp'])) {
            $this->jwtService->revokeAccessToken(
                (string) $accessPayload['jti'],
                CarbonImmutable::createFromTimestamp((int) $accessPayload['exp']),
            );
        }
    }

    private function createRefreshToken(User $user): array
    {
        $ttlDays = (int) config('library.auth.refresh_token_ttl_days', 7);
        $plain = Str::random(80);
        $expiresAt = now()->addDays($ttlDays);

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
        ]);

        return [
            'plain' => $plain,
            'expires_in' => $ttlDays * 24 * 60 * 60,
        ];
    }
}
