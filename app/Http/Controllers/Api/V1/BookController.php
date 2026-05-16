<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreBookRequest;
use App\Http\Requests\Catalog\UpdateBookRequest;
use App\Models\Book;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $query = Book::query()->with(['authors', 'genres']);

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where('title', 'like', "%{$q}%");
        }

        $books = $query->orderByDesc('created_at')->paginate($perPage);

        return ApiResponse::paginated($books, $books->items());
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $authorIds = $payload['author_ids'] ?? [];
        $genreIds = $payload['genre_ids'] ?? [];

        unset($payload['author_ids'], $payload['genre_ids']);

        $book = Book::query()->create($payload);
        $book->authors()->sync($authorIds);
        $book->genres()->sync($genreIds);

        return ApiResponse::success($book->fresh(['authors', 'genres']), 201);
    }

    public function show(string $book): JsonResponse
    {
        $model = Book::query()->with(['authors', 'genres', 'copies'])->findOrFail($book);

        return ApiResponse::success($model);
    }

    public function update(UpdateBookRequest $request, string $book): JsonResponse
    {
        $model = Book::query()->findOrFail($book);
        $payload = $request->validated();

        if (array_key_exists('author_ids', $payload)) {
            $model->authors()->sync($payload['author_ids'] ?? []);
            unset($payload['author_ids']);
        }

        if (array_key_exists('genre_ids', $payload)) {
            $model->genres()->sync($payload['genre_ids'] ?? []);
            unset($payload['genre_ids']);
        }

        $model->update($payload);

        return ApiResponse::success($model->fresh(['authors', 'genres', 'copies']));
    }

    public function destroy(string $book): JsonResponse
    {
        Book::query()->findOrFail($book)->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
