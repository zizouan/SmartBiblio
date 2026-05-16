<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Auth\AuthTokenService;
use App\Services\Shared\QrCodeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $authTokenService,
        private readonly QrCodeService $qrCodeService,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'email' => $request->string('email')->lower()->toString(),
            'password_hash' => Hash::make($request->string('password')->toString()),
            'first_name' => $request->string('first_name')->toString(),
            'last_name' => $request->string('last_name')->toString(),
            'role' => 'reader',
            'qr_code' => $this->qrCodeService->memberCode(),
        ]);

        $tokens = $this->authTokenService->issueFor($user);

        return ApiResponse::success([
            'user' => $this->formatUser($user),
            'tokens' => $tokens,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->lower()->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password_hash)) {
            return ApiResponse::error('AUTH_INVALID_CREDENTIALS', 'Invalid email or password.', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error('AUTH_USER_INACTIVE', 'User account is inactive.', 403);
        }

        $tokens = $this->authTokenService->issueFor($user);

        return ApiResponse::success([
            'user' => $this->formatUser($user),
            'tokens' => $tokens,
        ]);
    }

    public function refresh(RefreshRequest $request): JsonResponse
    {
        $tokens = $this->authTokenService->refresh($request->string('refresh_token')->toString());

        return ApiResponse::success(['tokens' => $tokens]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $payload = $request->attributes->get('auth_payload');

        $this->authTokenService->logout(
            $user,
            $request->input('refresh_token'),
            is_array($payload) ? $payload : null,
        );

        return ApiResponse::success(['message' => 'Logged out successfully.']);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => $request->string('email')->toString()]);

        return ApiResponse::success(['message' => 'Password reset link sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password_hash' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error('AUTH_PASSWORD_RESET_FAILED', __($status), 422);
        }

        return ApiResponse::success(['message' => 'Password reset successful.']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(['user' => $this->formatUser($user)]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role?->value ?? $user->role,
            'qr_code' => $user->qr_code,
            'is_active' => $user->is_active,
            'suspension_until' => $user->suspension_until?->toISOString(),
        ];
    }
}
