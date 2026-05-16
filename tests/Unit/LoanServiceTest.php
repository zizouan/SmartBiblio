<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use App\Services\Loans\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returning_loan_restores_availability(): void
    {
        $user = User::factory()->create();
        $book = Book::query()->create(['title' => 'Service Test', 'total_copies' => 1, 'available_copies' => 0]);
        $copy = BookCopy::query()->create(['book_id' => $book->id, 'qr_code' => 'COPY-SERVICE']);

        $loan = Loan::query()->create([
            'user_id' => $user->id,
            'book_copy_id' => $copy->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'active',
        ]);

        /** @var LoanService $service */
        $service = app(LoanService::class);
        $service->returnLoan($loan);

        $this->assertSame(1, $book->fresh()->available_copies);
        $this->assertSame('returned', $loan->fresh()->status->value);
    }
}
