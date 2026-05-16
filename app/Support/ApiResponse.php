<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => [],
        ], $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, mixed $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'errors' => [],
        ]);
    }

    public static function error(string $code, string $message, int $status = 422, array $extra = []): JsonResponse
    {
        $payload = [
            'data' => null,
            'meta' => (object) [],
            'errors' => [[
                'code' => $code,
                'message' => $message,
            ]],
        ];

        if ($extra !== []) {
            $payload['errors'][0]['details'] = $extra;
        }

        return response()->json($payload, $status);
    }
}
