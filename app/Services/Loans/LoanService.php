<?php

namespace App\Services\Loans;

use App\Enums\LoanStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ApiException;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

class LoanService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function createLoan(User $reader, BookCopy $copy, ?User $createdBy = null): Loan
    {
        $this->assertReaderEligible($reader);
        $this->assertCopyAvailable($copy);
        $this->assertQuota($reader);

        return $this->db->transaction(function () use ($reader, $copy, $createdBy) {
            $loan = Loan::query()->create([
                'user_id' => $reader->id,
                'book_copy_id' => $copy->id,
                'loan_date' => now()->toDateString(),
                'due_date' => now()->addDays((int) config('library.loans.default_duration_days', 14))->toDateString(),
                'status' => LoanStatus::Active->value,
                'renewal_count' => 0,
                'created_by' => $createdBy?->id,
            ]);

            $copy->book->decrement('available_copies');

            Notification::query()->create([
                'user_id' => $reader->id,
                'type' => 'loan_created',
                'payload' => [
                    'loan_id' => $loan->id,
                    'due_date' => $loan->due_date?->toDateString(),
                    'book_copy_id' => $copy->id,
                ],
            ]);

            return $loan->fresh(['bookCopy.book', 'user']);
        });
    }

    public function returnLoan(Loan $loan): Loan
    {
        if (($loan->status?->value ?? $loan->status) !== LoanStatus::Active->value) {
            throw new ApiException('LOAN_NOT_ACTIVE', 'Only active loans can be returned.', 409);
        }

        return $this->db->transaction(function () use ($loan) {
            $loan->forceFill([
                'status' => LoanStatus::Returned->value,
                'return_date' => now()->toDateString(),
            ])->save();

            $loan->bookCopy->book->increment('available_copies');

            $book = $loan->bookCopy->book;
            $reservation = Reservation::query()
                ->where('book_id', $book->id)
                ->where('status', ReservationStatus::Pending->value)
                ->orderBy('requested_at')
                ->first();

            if ($reservation) {
                $reservation->forceFill([
                    'status' => ReservationStatus::Fulfilled->value,
                    'expires_at' => now()->addDays(2),
                ])->save();

                Notification::query()->create([
                    'user_id' => $reservation->user_id,
                    'type' => 'reservation_available',
                    'payload' => ['book_id' => $book->id, 'reservation_id' => $reservation->id],
                ]);
            }

            return $loan->fresh(['bookCopy.book', 'user']);
        });
    }

    public function renewLoan(Loan $loan): Loan
    {
        if (($loan->status?->value ?? $loan->status) !== LoanStatus::Active->value) {
            throw new ApiException('LOAN_NOT_ACTIVE', 'Only active loans can be renewed.', 409);
        }

        if ($loan->renewal_count >= 1) {
            throw new ApiException('LOAN_RENEWAL_LIMIT', 'Loan renewal limit reached.', 409);
        }

        $reservedByOther = Reservation::query()
            ->where('book_id', $loan->bookCopy->book_id)
            ->where('status', ReservationStatus::Pending->value)
            ->where('user_id', '!=', $loan->user_id)
            ->exists();

        if ($reservedByOther) {
            throw new ApiException('LOAN_RESERVED_BY_OTHER', 'Cannot renew when reserved by another user.', 409);
        }

        $loan->forceFill([
            'renewal_count' => $loan->renewal_count + 1,
            'due_date' => $loan->due_date->copy()->addDays((int) config('library.loans.default_duration_days', 14)),
        ])->save();

        return $loan->fresh(['bookCopy.book', 'user']);
    }

    public function enqueueReservation(User $reader, Book $book): Reservation
    {
        return Reservation::query()->create([
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'status' => ReservationStatus::Pending->value,
            'requested_at' => now(),
        ]);
    }

    private function assertReaderEligible(User $reader): void
    {
        if (! $reader->is_active) {
            throw new ApiException('MEMBER_INACTIVE', 'Member account is inactive.', 403);
        }

        if ($reader->suspension_until && $reader->suspension_until->isFuture()) {
            throw new ApiException('MEMBER_SUSPENDED', 'Member is currently suspended.', 403);
        }
    }

    private function assertCopyAvailable(BookCopy $copy): void
    {
        $active = Loan::query()
            ->where('book_copy_id', $copy->id)
            ->where('status', LoanStatus::Active->value)
            ->exists();

        if ($active) {
            throw new ApiException('BOOK_COPY_UNAVAILABLE', 'Book copy is currently unavailable.', 409);
        }

        if ($copy->book->available_copies <= 0) {
            throw new ApiException('BOOK_UNAVAILABLE', 'Book is currently unavailable.', 409);
        }
    }

    private function assertQuota(User $reader): void
    {
        $activeCount = Loan::query()
            ->where('user_id', $reader->id)
            ->where('status', LoanStatus::Active->value)
            ->count();

        $quota = (int) config('library.loans.max_simultaneous', 3);

        if ($activeCount >= $quota) {
            throw new ApiException('LOAN_QUOTA_REACHED', 'Loan quota reached for this reader.', 409, ['quota' => $quota]);
        }
    }
}
