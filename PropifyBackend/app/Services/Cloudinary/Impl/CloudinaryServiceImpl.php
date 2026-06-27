<?php

namespace App\Services\Cloudinary\Impl;

use App\Services\Cloudinary\CloudinaryService;
use Illuminate\Support\Facades\Log;

final class CloudinaryServiceImpl implements CloudinaryService
{
    private string $cloudName;

    private string $apiKey;

    private string $apiSecret;

    private string $presetAvatar;

    private string $presetListing;

    public function __construct()
    {
        $this->cloudName = config('cloudinary.cloud_name');
        $this->apiKey = config('cloudinary.api_key');
        $this->apiSecret = config('cloudinary.api_secret');
        $this->presetAvatar = config('cloudinary.upload_preset_avatar');
        $this->presetListing = config('cloudinary.upload_preset_listing');
    }

    public function generateSignature(string $folder, string $uploadType): array
    {
        $timestamp = time();

        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        ksort($paramsToSign);

        $stringToSign = collect($paramsToSign)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->join('&');

        $signature = sha1($stringToSign.$this->apiSecret);

        Log::info('Cloudinary signature generated', [
            'folder' => $folder,
            'upload_type' => $uploadType,
            'timestamp' => $timestamp,
        ]);

        return [
            'signature' => $signature,
            'api_key' => $this->apiKey,
            'cloud_name' => $this->cloudName,
            'timestamp' => $timestamp,
            'folder' => $folder,
            'resource_type' => 'auto',
        ];
    }
}
