<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_cannot_create_book_but_librarian_can(): void
    {
        $reader = User::factory()->create(['role' => 'reader']);
        $librarian = User::factory()->create(['role' => 'librarian']);

        $readerToken = $this->login($reader);
        $this->withHeader('Authorization', 'Bearer '.$readerToken)
            ->postJson('/api/v1/books', ['title' => 'Forbidden Book'])
            ->assertStatus(403);

        $libToken = $this->login($librarian);
        $this->withHeader('Authorization', 'Bearer '.$libToken)
            ->postJson('/api/v1/books', ['title' => 'Allowed Book', 'total_copies' => 1, 'available_copies' => 1])
            ->assertCreated();
    }

    private function login(User $user): string
    {
        $user->update(['password_hash' => Hash::make('Password123')]);

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->json('data.tokens.access_token');
    }
}
