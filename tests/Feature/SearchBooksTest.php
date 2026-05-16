<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SearchBooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_books(): void
    {
        $reader = User::factory()->create(['role' => 'reader']);

        $fiction = Genre::query()->create(['name' => 'Fiction', 'slug' => 'fiction']);

        $book1 = Book::query()->create([
            'title' => 'Laravel Patterns',
            'language' => 'en',
            'available_copies' => 3,
            'total_copies' => 3,
        ]);

        $book2 = Book::query()->create([
            'title' => 'Roman Moderne',
            'language' => 'fr',
            'available_copies' => 0,
            'total_copies' => 1,
        ]);

        $book1->genres()->sync([$fiction->id]);
        $book2->genres()->sync([$fiction->id]);

        $token = $this->login($reader);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/search/books?title=Laravel&language=en&available_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Patterns');
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
