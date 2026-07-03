<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Database\Seeder;

final class AmenityAttributeGroupSeeder extends Seeder
{
    public function run(): void
    {
        $group = AttributeGroup::updateOrCreate(
            ['code' => 'amenities'],
            [
                'name' => 'Tiện ích',
                'input_type' => 'checkbox',
                'order_index' => 1,
            ],
        );

        $amenities = ['Sân chơi', 'Bể bơi', 'Sân vườn', 'Thang máy', 'Wifi', 'Khu để xe'];

        foreach ($amenities as $index => $name) {
            Attribute::updateOrCreate(
                ['group_id' => $group->id, 'name' => $name],
                ['order_index' => $index + 1],
            );
        }
    }
}

