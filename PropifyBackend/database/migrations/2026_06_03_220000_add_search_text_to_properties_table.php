<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->text('search_text')->nullable()->after('address_detail');
        });

        DB::table('properties')
            ->select(['id', 'project_name', 'province', 'ward', 'street_code', 'address_detail'])
            ->orderBy('id')
            ->chunkById(200, function ($properties): void {
                foreach ($properties as $property) {
                    $searchText = $this->buildSearchText([
                        $property->project_name,
                        $property->province,
                        $property->ward,
                        $property->street_code,
                        $property->address_detail,
                    ]);

                    DB::table('properties')
                        ->where('id', $property->id)
                        ->update(['search_text' => $searchText]);
                }
            });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE properties ADD FULLTEXT INDEX ft_properties_search_text (search_text)');
        }
    }

    public function down(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE properties DROP INDEX ft_properties_search_text');
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn('search_text');
        });
    }

    private function buildSearchText(array $parts): string
    {
        $normalizedParts = array_values(array_filter(array_map(function ($value): string {
            $normalized = trim((string) $value);

            if ($normalized === '') {
                return '';
            }

            $normalized = Str::ascii(mb_strtolower($normalized, 'UTF-8'));

            return preg_replace('/\s+/u', ' ', $normalized) ?: $normalized;
        }, $parts)));

        return implode(' ', $normalizedParts);
    }
};
