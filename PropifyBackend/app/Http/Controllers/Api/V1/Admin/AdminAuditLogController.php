<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Helpers\ApiResponse;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminAuditLogController
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $paginator = AuditLog::query()
            ->with('actor:id,full_name,email')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('actor_id'), fn ($query) => $query->where('actor_id', $request->integer('actor_id')))
            ->when($request->filled('auditable_type'), fn ($query) => $query->where('auditable_type', $request->string('auditable_type')))
            ->when($request->filled('auditable_id'), fn ($query) => $query->where('auditable_id', $request->integer('auditable_id')))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->string('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->string('to_date')))
            ->latest('created_at')
            ->paginate($perPage);

        return ApiResponse::success(
            data: AuditLogResource::collection($paginator->items()),
            message: 'Lấy danh sách audit logs thành công.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
