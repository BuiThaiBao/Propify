<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    | Credentials và preset dùng cho upload ảnh (Signed Upload — Option B)
    */

    'cloud_name'            => env('CLOUDINARY_CLOUD_NAME'),
    'api_key'               => env('CLOUDINARY_API_KEY'),
    'api_secret'            => env('CLOUDINARY_API_SECRET'),
    'upload_preset_avatar'  => env('CLOUDINARY_UPLOAD_PRESET_AVATAR', 'propify_avatars'),
    'upload_preset_listing' => env('CLOUDINARY_UPLOAD_PRESET_LISTING', 'propify_listings'),
];
