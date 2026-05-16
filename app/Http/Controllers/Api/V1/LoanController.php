<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loans\ScanBookRequest;
use App\Http\Requests\Loans\ScanMemberRequest;
use App\Http\Requests\Loans\StoreLoanRequest;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use App\Services\Loans\LoanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private readonly LoanService $loanService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $query = Loan::query()->with(['user', 'bookCopy.book']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id')->toString());
        }

        if ($request->filled('from_date')) {
            $query->whereDate('loan_date', '>=', $request->date('from_date')?->toDateString());
        }

        if ($request->filled('to_date')) {
            $query->whereDate('loan_date', '<=', $request->date('to_date')?->toDateString());
        }

        $loans = $query->orderByDesc('loan_date')->paginate($perPage);

        return ApiResponse::paginated($loans, $loans->items());
    }

    public function scanMember(ScanMemberRequest $request): JsonResponse
    {
        $user = User::query()->where('qr_code', $request->string('member_qr')->toString())->firstOrFail();

        $activeLoans = Loan::query()->where('user_id', $user->id)->where('status', 'active')->count();
        $quota = (int) config('library.loans.max_simultaneous', 3);

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'is_active' => $user->is_active,
                'suspension_until' => $user->suspension_until?->toISOString(),
            ],
            'quota' => [
                'max' => $quota,
                'active' => $activeLoans,
                'remaining' => max($quota - $activeLoans, 0),
            ],
        ]);
    }

    public function scanBook(ScanBookRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->string('user_id')->toString());
        $copy = BookCopy::query()->with('book')->where('qr_code', $request->string('book_copy_qr')->toString())->firstOrFail();
        $actor = $request->user();

        if (($actor->role?->value ?? $actor->role) === 'reader' && $actor->id !== $user->id) {
            return ApiResponse::error('FORBIDDEN_ROLE', 'Readers can only borrow for themselves.', 403);
        }

        if ($copy->book->available_copies <= 0) {
            $reservation = $this->loanService->enqueueReservation($user, $copy->book);

            return ApiResponse::success([
                'available' => false,
                'reservation_id' => $reservation->id,
                'message' => 'Book unavailable; reservation created.',
            ], 202);
        }

        $loan = $this->loanService->createLoan($user, $copy, $request->user());

        return ApiResponse::success($loan, 201);
    }

    public function store(StoreLoanRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->string('user_id')->toString());
        $copy = BookCopy::query()->with('book')->findOrFail($request->string('book_copy_id')->toString());
        $actor = $request->user();

        if (($actor->role?->value ?? $actor->role) === 'reader' && $actor->id !== $user->id) {
            return ApiResponse::error('FORBIDDEN_ROLE', 'Readers can only borrow for themselves.', 403);
        }

        if ($copy->book->available_copies <= 0) {
            $reservation = $this->loanService->enqueueReservation($user, $copy->book);

            return ApiResponse::success([
                'available' => false,
                'reservation_id' => $reservation->id,
                'message' => 'Book unavailable; reservation created.',
            ], 202);
        }

        $loan = $this->loanService->createLoan($user, $copy, $request->user());

        return ApiResponse::success($loan, 201);
    }

    public function returnLoan(string $loan): JsonResponse
    {
        $model = Loan::query()->with('bookCopy.book')->findOrFail($loan);
        $result = $this->loanService->returnLoan($model);

        return ApiResponse::success($result);
    }

    public function renew(string $loan): JsonResponse
    {
        $model = Loan::query()->with('bookCopy.book')->findOrFail($loan);
        $result = $this->loanService->renewLoan($model);

        return ApiResponse::success($result);
    }
}
