<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectSearchRepository
{
    public function search(string $keyword, int $page, int $size): LengthAwarePaginator;
}
