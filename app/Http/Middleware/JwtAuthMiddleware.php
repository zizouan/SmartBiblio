<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            throw new ApiException('AUTH_MISSING_TOKEN', 'Bearer token is required.', 401);
        }

        $token = substr($header, 7);
        $payload = $this->jwtService->decodeAndValidate($token);

        $user = User::query()->find($payload['sub']);

        if (! $user) {
            throw new ApiException('AUTH_USER_NOT_FOUND', 'Authenticated user not found.', 401);
        }

        Auth::setUser($user);
        $request->attributes->set('auth_payload', $payload);

        return $next($request);
    }
}
