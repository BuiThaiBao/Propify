<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Helpers\ApiResponse;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\User;
use App\Services\Notification\InAppNotificationService;
use App\Services\User\Commands\LockUserCommand;
use App\Services\User\Commands\UnlockUserCommand;
use App\Services\User\Search\UserSearchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $query = User::query()
            ->where('role', '!=', UserRole::Admin)
            ->withCount('listings as posts_count')
            ->withExists(['listings as has_broker_listing' => function ($query) {
                $query->whereHas('property', function ($q) {
                    $q->where('poster_type', 'BROKER');
                });
            }]);

        // Use Strategy Pattern to search & filter user records
        $searchContext = new UserSearchContext;
        $paginator = $searchContext->applyStrategies($query, $request)
            ->latest('created_at')
            ->paginate($perPage);

        return ApiResponse::success(
            data: AdminUserResource::collection($paginator->items()),
            message: 'Lấy danh sách tài khoản thành công.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }

    /**
     * Change user status (Lock/Unlock)
     */
    public function changeStatus(
        Request $request,
        int $id,
        InAppNotificationService $notificationService
    ): JsonResponse {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $user = User::query()->where('role', '!=', UserRole::Admin)->find($id);

        if ($user === null) {
            return ApiResponse::notFound('Không tìm thấy tài khoản.');
        }

        $request->validate([
            'status' => ['required', 'string', 'in:active,locked'],
            'reason_code' => ['nullable', 'max:20'],
            'reason_text' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatusInput = $request->input('status');
        $oldStatus = $user->status;
        $newStatus = $newStatusInput === 'locked' ? UserStatus::Banned : UserStatus::Active;

        if ($oldStatus === $newStatus) {
            return ApiResponse::success(
                data: new AdminUserResource($user),
                message: 'Trạng thái tài khoản không thay đổi.'
            );
        }

        // Build lock reason label map
        $reasonLabels = [
            1 => 'Đăng tin giả mạo hoặc sai sự thật',
            2 => 'Lừa đảo, chiếm đoạt tài sản',
            3 => 'Đăng nội dung vi phạm quy định hệ thống',
            4 => 'Spam hoặc đăng tin trùng lặp nhiều lần',
            5 => 'Có hành vi quấy rối, gây ảnh hưởng đến người dùng khác',
            6 => 'Lý do khác',
        ];

        $reasonCode = $request->input('reason_code');
        $reasonText = $request->input('reason_text');
        $reasonLabel = $reasonCode ? ($reasonLabels[$reasonCode] ?? null) : null;

        $commandData = [
            'reason_code' => $reasonCode,
            'reason_text' => $reasonText,
            'reason_label' => $reasonLabel,
        ];

        // Execute status change utilizing the Command Pattern
        if ($newStatus === UserStatus::Banned) {
            $command = new LockUserCommand(
                user: $user,
                actor: $request->user(),
                data: $commandData,
                notificationService: $notificationService
            );
        } else {
            $command = new UnlockUserCommand(
                user: $user,
                actor: $request->user(),
                data: $commandData,
                notificationService: $notificationService
            );
        }

        $user = $command->execute();

        // Count posts & check has_broker_listing for AdminUserResource response mapping
        $user->loadCount('listings as posts_count');
        $user->has_broker_listing = $user->listings()->whereHas('property', function ($query) {
            $query->where('poster_type', 'BROKER');
        })->exists();

        return ApiResponse::success(
            data: new AdminUserResource($user),
            message: 'Cập nhật trạng thái tài khoản thành công.'
        );
    }

    /**
     * Display the specified user detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $user = User::query()
            ->where('role', '!=', UserRole::Admin)
            ->withCount('listings as posts_count')
            ->withExists(['listings as has_broker_listing' => function ($query) {
                $query->whereHas('property', function ($q) {
                    $q->where('poster_type', 'BROKER');
                });
            }])
            ->find($id);

        if ($user === null) {
            return ApiResponse::notFound('Không tìm thấy tài khoản.');
        }

        $statusCounts = Listing::query()
            ->where('owner_id', $id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return ApiResponse::success(
            data: array_merge((new AdminUserResource($user))->toArray($request), [
                'activePostsCount' => (int) ($statusCounts['ACTIVE'] ?? 0),
                'pendingPostsCount' => (int) ($statusCounts['PENDING'] ?? 0),
                'rejectedPostsCount' => (int) ($statusCounts['REJECTED'] ?? 0),
                'lockedPostsCount' => (int) ($statusCounts['LOCKED'] ?? 0),
            ]),
            message: 'Lấy thông tin tài khoản thành công.'
        );
    }

    /**
     * Display listings of a specified user.
     */
    public function listings(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $paginator = Listing::query()
            ->with([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointmentSlots',
                'appointments',
                'owner',
                'package',
            ])
            ->where('owner_id', $id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return ApiResponse::success(
            data: ListingResource::collection($paginator->items()),
            message: 'Lấy danh sách tin đăng của người dùng thành công.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
