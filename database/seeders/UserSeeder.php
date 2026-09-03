<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Akun login: 1 admin + 1 akun per pegawai (role "asn").
 *
 * Username = NIP (bukan email -- cuma ~1/3 pegawai yang punya data email).
 * Password awal ASN = NIP-nya sendiri (untuk demo/skripsi ini; idealnya
 * dipaksa ganti password di produksi sungguhan). ASN bisa ganti password
 * sendiri lewat halaman "Profil Saya" setelah login.
 *
 * Hash password ASN dibuat dengan cost bcrypt rendah (rounds=4) supaya
 * proses seed ~14 ribu akun tidak makan waktu lama -- ini simplifikasi
 * yang wajar untuk demo lokal, BUKAN untuk deployment publik sungguhan.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->delete();

        DB::table('users')->insert([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@bangkomasn.local',
            'role' => 'admin',
            'nip' => null,
            'password' => Hash::make('admin123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Pegawai::query()->select('nip', 'nama')->orderBy('nip')->chunk(1000, function ($pegawais) {
            $rows = [];
            foreach ($pegawais as $p) {
                $rows[] = [
                    'username' => $p->nip,
                    'name' => $p->nama,
                    'email' => $p->nip.'@bangkomasn.local',
                    'role' => 'asn',
                    'nip' => $p->nip,
                    'password' => Hash::make($p->nip, ['rounds' => 4]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('users')->insert($rows);
        });
    }
}
