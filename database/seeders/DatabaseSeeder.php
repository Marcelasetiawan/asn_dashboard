<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Seeder ini memang mengganti dataset dashboard sebelumnya.
        // Hapus tabel anak lebih dahulu agar foreign key tetap valid.
        DB::table('pelatihan_dipilih')->delete();
        DB::table('riwayat_diklat')->delete();
        DB::table('pegawai')->delete();

        $this->call([
            PrepareDashboardImportSeeder::class,
            PegawaiSeeder::class,
            RiwayatDiklatSeeder::class,
            OkupasiSeeder::class,
            PelatihanWajibSeeder::class,
            UserSeeder::class,
        ]);
    }
}


