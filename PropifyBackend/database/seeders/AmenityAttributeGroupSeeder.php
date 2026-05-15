<?php

namespace Database\Seeders;

use App\Models\AttributeGroup;
use Illuminate\Database\Seeder;

final class AmenityAttributeGroupSeeder extends Seeder
{
    public function run(): void
    {
        AttributeGroup::updateOrCreate(
            ['code' => 'amenities'],
            [
                'name' => 'Tiện ích',
                'input_type' => 'checkbox',
                'order_index' => 1,
            ],
        );
    }
}
