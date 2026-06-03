<?php

namespace App\Services\ProjectSearch\Impl;

use App\Repositories\ProjectSearchRepository;
use App\Services\ProjectSearch\ProjectSearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProjectSearchServiceImpl implements ProjectSearchService
{
    public function __construct(
        private readonly ProjectSearchRepository $projectSearchRepository,
    ) {}

    public function search(string $keyword, int $page, int $size): LengthAwarePaginator
    {
        return $this->projectSearchRepository->search($keyword, $page, $size);
    }
}
