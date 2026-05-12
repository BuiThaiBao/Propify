<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackagePricing;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name'        => 'Cơ bản',
                'slug'        => 'basic',
                'price'       => 0, // Not applicable
                'priority'    => 1,
                'multiplier'  => 1.0,
                'daily_quota' => 100,
                'decay_rate'  => 0.05,
                'badge'       => null,
                'color'       => null,
                'is_active'   => true,
            ],
            [
                'name'        => 'Silver',
                'slug'        => 'silver',
                'price'       => 3000,
                'priority'    => 2,
                'multiplier'  => 1.2,
                'daily_quota' => 200,
                'decay_rate'  => 0.02,
                'badge'       => 'Bạc',
                'color'       => '#C0C0C0',
                'is_active'   => true,
            ],
            [
                'name'        => 'Gold',
                'slug'        => 'gold',
                'price'       => 5000,
                'priority'    => 3,
                'multiplier'  => 1.5,
                'daily_quota' => 500,
                'decay_rate'  => 0.01,
                'badge'       => 'Vàng',
                'color'       => '#FFD700',
                'is_active'   => true,
            ],
        ];

        $durations = [3, 7, 10, 15, 30];

        foreach ($packages as $pkg) {
            $package = Package::updateOrCreate(
                ['slug' => $pkg['slug']],
                $pkg
            );

            // Generate pricings based on price
            if ($package->price > 0) {
                foreach ($durations as $days) {
                    PackagePricing::updateOrCreate(
                        [
                            'package_id' => $package->id,
                            'duration_days' => $days
                        ],
                        [
                            'price' => $package->price * $days,
                            'label' => $days . ' ngày',
                            'is_active' => true
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Seeded 3 packages: Cơ bản, Silver, Gold with per-day pricing.');
    }
}
