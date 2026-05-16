<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Http\Requests\Members\UpdateMemberStatusRequest;
use App\Models\Loan;
use App\Models\User;
use App\Services\Shared\QrCodeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCodeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $members = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::paginated($members, $members->items());
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $member = User::query()->create([
            'email' => strtolower($data['email']),
            'password_hash' => Hash::make($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => $data['role'] ?? UserRole::Reader->value,
            'is_active' => $data['is_active'] ?? true,
            'suspension_until' => $data['suspension_until'] ?? null,
            'qr_code' => $this->qrCodeService->memberCode(),
        ]);

        return ApiResponse::success($member, 201);
    }

    public function show(string $member): JsonResponse
    {
        $model = User::query()->findOrFail($member);
        Gate::authorize('view', $model);

        return ApiResponse::success($model);
    }

    public function update(UpdateMemberRequest $request, string $member): JsonResponse
    {
        $model = User::query()->findOrFail($member);
        Gate::authorize('update', $model);

        $payload = $request->validated();

        if (! empty($payload['password'])) {
            $payload['password_hash'] = Hash::make($payload['password']);
            unset($payload['password']);
        }

        if (isset($payload['email'])) {
            $payload['email'] = strtolower($payload['email']);
        }

        $model->update($payload);

        return ApiResponse::success($model->fresh());
    }

    public function destroy(string $member): JsonResponse
    {
        $model = User::query()->findOrFail($member);
        Gate::authorize('update', $model);

        $model->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    public function profile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $loansCount = Loan::query()->where('user_id', $user->id)->count();
        $activeLoans = Loan::query()->where('user_id', $user->id)->where('status', 'active')->count();

        return ApiResponse::success([
            'user' => $user,
            'stats' => [
                'total_loans' => $loansCount,
                'active_loans' => $activeLoans,
            ],
        ]);
    }

    public function history(string $member): JsonResponse
    {
        $user = User::query()->findOrFail($member);
        Gate::authorize('view', $user);

        $history = Loan::query()->with('bookCopy.book')->where('user_id', $user->id)->orderByDesc('loan_date')->get();

        return ApiResponse::success($history);
    }

    public function qr(string $member): JsonResponse
    {
        $user = User::query()->findOrFail($member);
        Gate::authorize('view', $user);

        return ApiResponse::success([
            'member_id' => $user->id,
            'qr_code' => $user->qr_code,
        ]);
    }

    public function updateStatus(UpdateMemberStatusRequest $request, string $member): JsonResponse
    {
        $user = User::query()->findOrFail($member);
        Gate::authorize('update', $user);

        $user->update($request->validated());

        return ApiResponse::success($user->fresh());
    }
}
