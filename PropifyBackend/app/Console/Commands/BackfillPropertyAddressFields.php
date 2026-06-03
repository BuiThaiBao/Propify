<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

final class BackfillPropertyAddressFields extends Command
{
    protected $signature = 'properties:backfill-address-fields {--chunk=200 : Number of properties processed per chunk}';

    protected $description = 'Backfill province and ward names for properties and rebuild search_text';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));

        $provinceMap = $this->fetchProvinceMap();
        $wardMap = $this->fetchWardMap(array_keys($provinceMap));

        $updated = 0;
        $skipped = 0;

        Property::query()
            ->select(['id', 'province_code', 'province', 'ward_code', 'ward', 'street_code', 'project_name', 'address_detail'])
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $properties) use (&$updated, &$skipped, $provinceMap, $wardMap): void {
                foreach ($properties as $property) {
                    $provinceCode = $this->normalizeCode($property->province_code);
                    $wardCode = $this->normalizeCode($property->ward_code);
                    $provinceName = $provinceMap[$provinceCode] ?? $this->normalizeExistingValue($property->province);
                    $wardName = $wardMap[$provinceCode.':'.$wardCode] ?? $this->normalizeExistingValue($property->ward);

                    if ($provinceName === null && $wardName === null) {
                        $skipped++;
                        continue;
                    }

                    $property->province = $provinceName;
                    $property->ward = $wardName;
                    $property->save();
                    $updated++;
                }
            });

        $this->info(sprintf('Updated %d properties, skipped %d properties.', $updated, $skipped));

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function fetchProvinceMap(): array
    {
        $response = Http::timeout(30)->retry(2, 500)->get('https://provinces.open-api.vn/api/v2/p/');

        $response->throw();

        $map = [];

        foreach ($response->json() ?? [] as $item) {
            $code = $this->normalizeCode($item['code'] ?? null);
            $name = $this->normalizeExistingValue($item['name'] ?? null);

            if ($code === '' || $name === null) {
                continue;
            }

            $map[$code] = $name;
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $provinceCodes
     * @return array<string, string>
     */
    private function fetchWardMap(array $provinceCodes): array
    {
        $map = [];

        foreach ($provinceCodes as $provinceCode) {
            $code = $this->normalizeCode($provinceCode);

            if ($code === '') {
                continue;
            }

            $response = Http::timeout(30)->retry(2, 500)->get('https://provinces.open-api.vn/api/v2/w/', [
                'province' => $code,
            ]);

            $response->throw();

            foreach ($response->json() ?? [] as $item) {
                $wardCode = $this->normalizeCode($item['code'] ?? null);
                $wardName = $this->normalizeExistingValue($item['name'] ?? null);

                if ($wardCode === '' || $wardName === null) {
                    continue;
                }

                $map[$code.':'.$wardCode] = $wardName;
            }
        }

        return $map;
    }

    private function normalizeCode(null|string|int $value): string
    {
        return trim((string) $value);
    }

    private function normalizeExistingValue(null|string|int $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
