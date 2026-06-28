<?php

declare(strict_types=1);

namespace App\Services\Media;

interface FileStorageAdapter
{
    public function upload(string $path, string $contents, string $mimeType): bool;

    public function getPublicUrl(string $path): string;
}
