<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreAuthorRequest;
use App\Http\Requests\Catalog\UpdateAuthorRequest;
use App\Models\Author;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $authors = Author::query()->orderBy('last_name')->paginate($perPage);

        return ApiResponse::paginated($authors, $authors->items());
    }

    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $author = Author::query()->create($request->validated());

        return ApiResponse::success($author, 201);
    }

    public function show(string $author): JsonResponse
    {
        $model = Author::query()->with('books')->findOrFail($author);

        return ApiResponse::success($model);
    }

    public function update(UpdateAuthorRequest $request, string $author): JsonResponse
    {
        $model = Author::query()->findOrFail($author);
        $model->update($request->validated());

        return ApiResponse::success($model->fresh());
    }

    public function destroy(string $author): JsonResponse
    {
        Author::query()->findOrFail($author)->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
