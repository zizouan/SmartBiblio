<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\RevokedAccessToken;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class JwtService
{
    public function issueAccessToken(string $userId): array
    {
        $ttl = (int) config('library.auth.access_token_ttl_minutes', 15);
        $now = CarbonImmutable::now();
        $exp = $now->addMinutes($ttl);

        $payload = [
            'iss' => config('app.url', 'biblio-smart'),
            'sub' => $userId,
            'iat' => $now->timestamp,
            'exp' => $exp->timestamp,
            'jti' => (string) Str::uuid(),
            'typ' => 'access',
        ];

        return [
            'token' => $this->encode($payload),
            'expires_in' => $ttl * 60,
            'jti' => $payload['jti'],
            'exp' => $exp,
        ];
    }

    public function decodeAndValidate(string $jwt): array
    {
        [$header, $payload, $signature] = explode('.', $jwt) + [null, null, null];

        if (! $header || ! $payload || ! $signature) {
            throw new ApiException('AUTH_INVALID_TOKEN', 'Token format is invalid.', 401);
        }

        $expected = $this->sign("{$header}.{$payload}");

        if (! hash_equals($expected, $signature)) {
            throw new ApiException('AUTH_INVALID_TOKEN', 'Token signature is invalid.', 401);
        }

        $decoded = json_decode($this->base64UrlDecode($payload), true);

        if (! is_array($decoded) || ! isset($decoded['sub'], $decoded['exp'], $decoded['jti'])) {
            throw new ApiException('AUTH_INVALID_TOKEN', 'Token payload is invalid.', 401);
        }

        if (CarbonImmutable::now()->timestamp >= (int) $decoded['exp']) {
            throw new ApiException('AUTH_TOKEN_EXPIRED', 'Access token has expired.', 401);
        }

        $revoked = RevokedAccessToken::query()->where('jti', $decoded['jti'])->exists();

        if ($revoked) {
            throw new ApiException('AUTH_TOKEN_REVOKED', 'Access token has been revoked.', 401);
        }

        return $decoded;
    }

    public function revokeAccessToken(string $jti, CarbonImmutable $expiresAt): void
    {
        RevokedAccessToken::query()->updateOrCreate(
            ['jti' => $jti],
            ['expires_at' => $expiresAt],
        );
    }

    private function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign("{$encodedHeader}.{$encodedPayload}");

        return "{$encodedHeader}.{$encodedPayload}.{$signature}";
    }

    private function sign(string $data): string
    {
        $key = config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                $key = $decoded;
            }
        }

        return $this->base64UrlEncode(hash_hmac('sha256', $data, (string) $key, true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding < 4) {
            $data .= str_repeat('=', $padding);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
