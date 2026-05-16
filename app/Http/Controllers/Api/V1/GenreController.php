<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreGenreRequest;
use App\Http\Requests\Catalog\UpdateGenreRequest;
use App\Models\Genre;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $genres = Genre::query()->orderBy('name')->paginate($perPage);

        return ApiResponse::paginated($genres, $genres->items());
    }

    public function store(StoreGenreRequest $request): JsonResponse
    {
        $genre = Genre::query()->create($request->validated());

        return ApiResponse::success($genre, 201);
    }

    public function show(string $genre): JsonResponse
    {
        $model = Genre::query()->with('books')->findOrFail($genre);

        return ApiResponse::success($model);
    }

    public function update(UpdateGenreRequest $request, string $genre): JsonResponse
    {
        $model = Genre::query()->findOrFail($genre);
        $model->update($request->validated());

        return ApiResponse::success($model->fresh());
    }

    public function destroy(string $genre): JsonResponse
    {
        Genre::query()->findOrFail($genre)->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
