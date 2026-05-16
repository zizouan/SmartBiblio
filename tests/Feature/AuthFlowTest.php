<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_me_refresh_logout_flow(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'email' => 'reader@example.com',
            'first_name' => 'Read',
            'last_name' => 'Er',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $register->assertCreated()->assertJsonPath('data.user.email', 'reader@example.com');

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'reader@example.com',
            'password' => 'Password123',
        ]);

        $login->assertOk();

        $access = $login->json('data.tokens.access_token');
        $refresh = $login->json('data.tokens.refresh_token');

        $this->withHeader('Authorization', 'Bearer '.$access)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'reader@example.com');

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refresh])
            ->assertOk()
            ->assertJsonPath('data.tokens.access_token', fn ($v) => is_string($v) && $v !== '');

        $this->withHeader('Authorization', 'Bearer '.$access)
            ->postJson('/api/v1/auth/logout', ['refresh_token' => $refresh])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$access)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_forgot_and_reset_password_flow(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'NewPassword123',
        ])->assertOk();
    }
}
