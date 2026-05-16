<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\AuthTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_and_refresh_tokens(): void
    {
        $user = User::factory()->create();

        /** @var AuthTokenService $service */
        $service = app(AuthTokenService::class);

        $issued = $service->issueFor($user);

        $this->assertArrayHasKey('access_token', $issued);
        $this->assertArrayHasKey('refresh_token', $issued);

        $refreshed = $service->refresh($issued['refresh_token']);

        $this->assertArrayHasKey('access_token', $refreshed);
        $this->assertNotSame($issued['access_token'], $refreshed['access_token']);
    }
}
