<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoanRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_quota_limit_blocks_new_loan(): void
    {
        [$reader, $librarian] = $this->users();

        for ($i = 0; $i < 4; $i++) {
            $book = Book::query()->create(['title' => 'Book '.$i, 'total_copies' => 1, 'available_copies' => 1]);
            $copy = BookCopy::query()->create(['book_id' => $book->id, 'qr_code' => 'COPY-'.Str::uuid()]);
            if ($i < 3) {
                Loan::query()->create([
                    'user_id' => $reader->id,
                    'book_copy_id' => $copy->id,
                    'loan_date' => now()->toDateString(),
                    'due_date' => now()->addDays(14)->toDateString(),
                    'status' => 'active',
                ]);
                $book->decrement('available_copies');
            } else {
                $targetCopy = $copy;
            }
        }

        $token = $this->login($librarian);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/loans', [
                'user_id' => $reader->id,
                'book_copy_id' => $targetCopy->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('errors.0.code', 'LOAN_QUOTA_REACHED');
    }

    public function test_unavailable_book_creates_reservation(): void
    {
        [$reader] = $this->users();
        $book = Book::query()->create(['title' => 'Busy', 'total_copies' => 1, 'available_copies' => 0]);
        BookCopy::query()->create(['book_id' => $book->id, 'qr_code' => 'COPY-UNAVAILABLE']);

        $token = $this->login($reader);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/loans/scan/book', [
                'user_id' => $reader->id,
                'book_copy_qr' => 'COPY-UNAVAILABLE',
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.available', false);
    }

    public function test_loan_can_only_be_renewed_once(): void
    {
        [$reader] = $this->users();
        $book = Book::query()->create(['title' => 'Renewable', 'total_copies' => 1, 'available_copies' => 1]);
        $copy = BookCopy::query()->create(['book_id' => $book->id, 'qr_code' => 'COPY-RENEW']);

        $loan = Loan::query()->create([
            'user_id' => $reader->id,
            'book_copy_id' => $copy->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'active',
            'renewal_count' => 0,
        ]);

        $token = $this->login($reader);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/loans/'.$loan->id.'/renew')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/loans/'.$loan->id.'/renew')
            ->assertStatus(409)
            ->assertJsonPath('errors.0.code', 'LOAN_RENEWAL_LIMIT');
    }

    private function users(): array
    {
        $reader = User::factory()->create(['role' => 'reader']);
        $librarian = User::factory()->create(['role' => 'librarian']);

        return [$reader, $librarian];
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
