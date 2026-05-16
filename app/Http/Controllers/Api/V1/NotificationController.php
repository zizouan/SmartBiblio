<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 20);

        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::paginated($notifications, $notifications->items());
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $model = Notification::query()
            ->where('id', $notification)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $model->update(['is_read' => true]);

        return ApiResponse::success($model->fresh());
    }
}
