<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name'        => 'Cơ bản',
                'slug'        => 'basic',
                'price'       => 0,
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
                'price'       => 200000,
                'priority'    => 2,
                'multiplier'  => 1.2,
                'daily_quota' => 200,
                'decay_rate'  => 0.02,
                'badge'       => 'HOT',
                'color'       => '#C0C0C0',
                'is_active'   => true,
            ],
            [
                'name'        => 'Gold',
                'slug'        => 'gold',
                'price'       => 500000,
                'priority'    => 3,
                'multiplier'  => 1.5,
                'daily_quota' => 500,
                'decay_rate'  => 0.01,
                'badge'       => 'VIP',
                'color'       => '#FFD700',
                'is_active'   => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::updateOrCreate(
                ['slug' => $pkg['slug']],
                $pkg
            );
        }

        $this->command->info('✅ Seeded 3 packages: Cơ bản, Silver, Gold');
    }
}
