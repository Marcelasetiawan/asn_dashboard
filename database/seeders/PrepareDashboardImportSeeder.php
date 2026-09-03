<?php

namespace Database\Seeders;

use App\Services\DashboardImportNormalizer;
use Illuminate\Database\Seeder;

class PrepareDashboardImportSeeder extends Seeder
{
    public function run(): void
    {
        $result = (new DashboardImportNormalizer())->run();

        $this->command->info("Siap import: {$result['pegawai']} pegawai, {$result['riwayat']} riwayat diklat/sertifikasi.");
    }
}
