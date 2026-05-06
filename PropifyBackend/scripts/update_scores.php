<?php
// Cập nhật content_score cho tất cả listings hiện có

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Listing;

$count = 0;

Listing::with(['property', 'images'])->chunk(50, function ($listings) use (&$count) {
    foreach ($listings as $listing) {
        $score = 0;

        // Title rõ ràng
        if (!empty($listing->title) && mb_strlen($listing->title) >= 10) {
            $score += 10;
        }

        // Có description
        if (!empty($listing->description) && mb_strlen($listing->description) >= 20) {
            $score += 10;
        }

        // Có ảnh
        if ($listing->images->count() > 0) {
            $score += 20;
        }

        // Có video
        if ($listing->has_video) {
            $score += 10;
        }

        // Thông tin đầy đủ
        $p = $listing->property;
        if ($p && $p->price > 0 && $p->area > 0 && (!empty($p->address_detail) || !empty($p->project_name))) {
            $score += 20;
        }

        // Verified
        if ($listing->is_verified) {
            $score += 25;
        }

        $listing->update(['score' => min($score, 100)]);
        $count++;
    }
});

echo "✅ Updated content_score for {$count} listings\n";
