<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreBookCopyRequest;
use App\Http\Requests\Catalog\UpdateBookCopyRequest;
use App\Models\BookCopy;
use App\Services\Shared\QrCodeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookCopyController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCodeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $copies = BookCopy::query()->with('book')->orderByDesc('created_at')->paginate($perPage);

        return ApiResponse::paginated($copies, $copies->items());
    }

    public function store(StoreBookCopyRequest $request): JsonResponse
    {
        $payload = $request->validated();

        if (empty($payload['qr_code'])) {
            $payload['qr_code'] = $this->qrCodeService->copyCode();
        }

        $copy = BookCopy::query()->create($payload);
        $copy->book->increment('total_copies');
        $copy->book->increment('available_copies');

        return ApiResponse::success($copy->fresh('book'), 201);
    }

    public function show(string $book_copy): JsonResponse
    {
        $copy = BookCopy::query()->with(['book', 'loans'])->findOrFail($book_copy);

        return ApiResponse::success($copy);
    }

    public function update(UpdateBookCopyRequest $request, string $book_copy): JsonResponse
    {
        $copy = BookCopy::query()->findOrFail($book_copy);
        $copy->update($request->validated());

        return ApiResponse::success($copy->fresh('book'));
    }

    public function destroy(string $book_copy): JsonResponse
    {
        $copy = BookCopy::query()->with('book')->findOrFail($book_copy);
        $book = $copy->book;
        $copy->delete();

        $book->decrement('total_copies');
        $book->decrement('available_copies');

        return ApiResponse::success(['deleted' => true]);
    }
}
