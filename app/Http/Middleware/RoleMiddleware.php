<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new ApiException('AUTH_REQUIRED', 'Authentication is required.', 401);
        }

        $currentRole = $user->role?->value ?? $user->role;

        if (! in_array($currentRole, $roles, true)) {
            throw new ApiException('FORBIDDEN_ROLE', 'You do not have permission for this resource.', 403);
        }

        return $next($request);
    }
}
