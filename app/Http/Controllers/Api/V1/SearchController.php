<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\LoanStatus;
use App\Models\Concerns\HasUuid;

class SearchController extends Controller
{
    /**
     * SEARCH BOOKS
     */
    public function books(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $query = Book::query()->with(['authors', 'genres']);

        // 🔥 GLOBAL SEARCH (for dropdowns / autocomplete)
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhere('synopsis', 'like', "%{$search}%")
                  ->orWhereHas('authors', function ($a) use ($search) {
                      $a->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // 🔎 FILTERS (optional advanced filters)
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

            $query->whereHas('genres', function ($q) use ($genreIds) {
                $q->whereIn('genres.id', $genreIds);
            });
        }

        if ($request->filled('isbn')) {
            $query->where('isbn', $request->string('isbn')->toString());
        }

        if ($request->filled('synopsis')) {
            $query->where('synopsis', 'like', "%{$request->string('synopsis')->toString()}%");
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

    /**
     * SEARCH USERS
     */
    public function users(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 10);

        $search = $request->string('search')->toString();

        $query = User::query()
        ->where('is_active', true)
        ->where(function ($q) {
            $q->whereNull('role')
            ->orWhere('role', 'reader');
        });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereRaw("first_name || ' ' || last_name LIKE ?", ["%{$search}%"])
                ->orWhere('email', 'like', "%{$search}%");
            });
        } else {
            return ApiResponse::success([]);
        }

        $users = $query
            ->orderBy('first_name')
            ->limit($perPage)
            ->get();

        return ApiResponse::success($users);
    }

    public function bookCopies(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        $query = \App\Models\BookCopy::query()
            ->with('book')
            ->whereDoesntHave('loans', function ($q) {
                $q->where('status', LoanStatus::Active);
            });

        if ($search) {
            $query->whereHas('book', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $copies = $query->limit($perPage)->get();

        return ApiResponse::success(
            $copies->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->book->title . ' #' . $c->id,
            ])
        );
    }

   public function bookCopiesByBookId(string $bookId): JsonResponse
    {
        $copies = \App\Models\BookCopy::query()
            ->with('book')
            ->where('book_id', $bookId)
            ->whereDoesntHave('loans', fn ($q) => $q->where('status', LoanStatus::Active))
            ->get();

        return ApiResponse::success(
            $copies->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->book?->title . ' #' . $c->id,
            ])
        );
    }

}
