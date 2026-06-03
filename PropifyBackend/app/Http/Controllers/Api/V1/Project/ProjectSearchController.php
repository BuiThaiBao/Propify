<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Helpers\ApiResponse;
use App\Http\Resources\ProjectSearchResource;
use App\Services\ProjectSearch\ProjectSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProjectSearchController
{
    public function __construct(
        private readonly ProjectSearchService $projectSearchService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $size = (int) ($validated['size'] ?? 10);

        $paginator = $this->projectSearchService->search(
            keyword: $validated['keyword'],
            page: $page,
            size: $size,
        );

        return ApiResponse::success(
            data: ProjectSearchResource::collection($paginator->items()),
            message: 'Tim kiem du an thanh cong.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
