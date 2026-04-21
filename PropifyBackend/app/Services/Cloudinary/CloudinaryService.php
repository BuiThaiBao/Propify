<?php

namespace App\Services\Cloudinary;

interface CloudinaryService
{
    /**
     * Tạo signature để frontend upload ảnh trực tiếp lên Cloudinary (Signed Upload).
     *
     * @param  string  $folder     Folder đích trên Cloudinary (vd: propify/avatars)
     * @param  string  $uploadType 'avatar' | 'listing'
     * @return array{
     *     signature: string,
     *     api_key: string,
     *     cloud_name: string,
     *     timestamp: int,
     *     folder: string,
     *     upload_preset: string
     * }
     */
    public function generateSignature(string $folder, string $uploadType): array;
}
