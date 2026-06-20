<?php

namespace App\Services\Listing\Filter\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * Chiến lược tìm kiếm theo một trường (tiêu đề / người đăng / địa chỉ).
 * Áp điều kiện tìm vào nhóm query con do repository mở sẵn cho từ khóa.
 */
interface SearchFieldStrategy
{
    public function apply(Builder $query, string $normalizedKeyword): void;
}
