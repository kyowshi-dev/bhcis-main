<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $path = database_path('data/philippine_locations.json');

        if (! is_file($path) || ! is_readable($path)) {
            $this->command?->warn("Philippine locations file not found or not readable: {$path}");

            return;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! isset($decoded['provinces'], $decoded['municipalities'])) {
            $this->command?->warn("Philippine locations file is not valid: {$path}");

            return;
        }

        $provinces = $this->insertProvinces($decoded['provinces']);
        $inserted = $this->insertMunicipalities($decoded['municipalities'], $provinces);

        $this->command?->info('Locations import finished: '.count($provinces)." provinces, {$inserted} cities/municipalities.");
    }

    /**
     * @param  array<int, array{psgc: string, name: string}>  $rows
     * @return array<string, int> PSGC code to primary key
     */
    private function insertProvinces(array $rows): array
    {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table('provinces')->insertOrIgnore(array_map(fn (array $row) => [
                'psgc_code' => $row['psgc'],
                'name' => $row['name'],
            ], $chunk));
        }

        return DB::table('provinces')->pluck('id', 'psgc_code')->all();
    }

    /**
     * @param  array<int, array{psgc: string, name: string, province: string, isCapital: bool}>  $rows
     * @param  array<string, int>  $provinceIds
     */
    private function insertMunicipalities(array $rows, array $provinceIds): int
    {
        $inserted = 0;

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $mapped = [];

            foreach ($chunk as $row) {
                $provinceId = $provinceIds[$row['province']] ?? null;

                if ($provinceId === null) {
                    continue;
                }

                $mapped[] = [
                    'psgc_code' => $row['psgc'],
                    'province_id' => $provinceId,
                    'name' => $row['name'],
                    'is_capital' => $row['isCapital'] ? 1 : 0,
                ];
            }

            if ($mapped !== []) {
                $inserted += DB::table('municipalities')->insertOrIgnore($mapped);
            }
        }

        return $inserted;
    }
}
