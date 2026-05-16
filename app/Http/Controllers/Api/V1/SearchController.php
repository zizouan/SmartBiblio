<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function books(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $query = Book::query()->with(['authors', 'genres']);

        if ($request->filled('title')) {
            $query->where('title', 'like', '%'.$request->string('title')->toString().'%');
        }

        if ($request->filled('author')) {
            $author = $request->string('author')->toString();
            $query->whereHas('authors', function ($q) use ($author) {
                $q->whereRaw("first_name || ' ' || last_name LIKE ?", ["%{$author}%"])
                    ->orWhere('first_name', 'like', "%{$author}%")
                    ->orWhere('last_name', 'like', "%{$author}%");
            });
        }

        if ($request->filled('genre_ids')) {
            $genreIds = array_filter(explode(',', $request->string('genre_ids')->toString()));
            $query->whereHas('genres', fn ($q) => $q->whereIn('genres.id', $genreIds));
        }

        if ($request->filled('isbn')) {
            $query->where('isbn', $request->string('isbn')->toString());
        }

        if ($request->filled('synopsis')) {
            $query->where('synopsis', 'like', '%'.$request->string('synopsis')->toString().'%');
        }

        if ($request->filled('language')) {
            $query->where('language', $request->string('language')->toString());
        }

        if ($request->boolean('available_only')) {
            $query->where('available_copies', '>', 0);
        }

        if ($request->filled('published_year_from')) {
            $query->where('published_year', '>=', $request->integer('published_year_from'));
        }

        if ($request->filled('published_year_to')) {
            $query->where('published_year', '<=', $request->integer('published_year_to'));
        }

        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', (float) $request->input('min_rating'));
        }

        $sort = $request->string('sort')->toString();
        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'popularity' => $query->orderByRaw('(total_copies - available_copies) DESC'),
            default => $query->orderByDesc('created_at'),
        };

        $books = $query->paginate($perPage);

        return ApiResponse::paginated($books, $books->items());
    }
}
