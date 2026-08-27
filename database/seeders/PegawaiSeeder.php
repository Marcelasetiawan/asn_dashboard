<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/dashboard-import/pegawai.csv');
        if (!is_file($path)) {
            throw new \RuntimeException("File import pegawai tidak ditemukan: {$path}");
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $batch = [];
        $total = 0;
        $now = now();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }
            $data = array_combine($header, $row);
            $batch[] = [
                'nip' => trim($data['nip']),
                'nama' => trim($data['nama']),
                'jenis_kelamin' => $this->nullable($data['jenis_kelamin']),
                'umur' => $this->nullableInteger($data['umur']),
                'status' => $this->nullable($data['status']),
                'golongan_ruang' => $this->nullable($data['golongan_ruang']),
                'jabatan' => $this->nullable($data['jabatan']),
                'eselon' => $this->nullable($data['eselon']),
                'satuan_kerja' => $this->nullable($data['satuan_kerja']),
                'kelompok_opd' => $this->nullable($data['kelompok_opd']),
                'opd_induk' => $this->nullable($data['opd_induk']),
                'unor_nama' => $this->nullable($data['unor_nama']),
                'instansi_kerja' => $this->nullable($data['instansi_kerja']),
                'alamat' => $this->nullable($data['alamat']),
                'email' => $this->nullable($data['email']),
                'kategori_asn' => $this->nullable($data['kategori_asn']),
                'level_1' => $this->nullable($data['level_1']),
                'level_2' => $this->nullable($data['level_2']),
                'level_3' => $this->nullable($data['level_3']),
                'jabatan_fungsional_spesifik' => $this->nullable($data['jabatan_fungsional_spesifik'] ?? null),
                'kelas_jabatan_fungsional' => $this->nullableInteger($data['kelas_jabatan_fungsional'] ?? null),
                'jabatan_umum_label' => $this->nullable($data['jabatan_umum_label'] ?? null),
                'unor_detail' => $this->nullable($data['unor_detail'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) === 500) {
                DB::table('pegawai')->insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }
        fclose($handle);

        if ($batch) {
            DB::table('pegawai')->insert($batch);
            $total += count($batch);
        }

        $this->command->info("Berhasil import {$total} pegawai.");
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function nullableInteger(?string $value): ?int
    {
        $value = $this->nullable($value);
        return $value === null ? null : (int) $value;
    }
}
