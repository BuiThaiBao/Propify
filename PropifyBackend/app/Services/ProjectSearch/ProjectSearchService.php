<?php

namespace App\Services\ProjectSearch;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectSearchService
{
    public function search(string $keyword, int $page, int $size): LengthAwarePaginator;
}
