<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiwayatDiklatSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/dashboard-import/riwayat_diklat.csv');
        if (!is_file($path)) {
            throw new \RuntimeException("File import riwayat diklat tidak ditemukan: {$path}");
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
                'sumber' => $this->nullable($data['sumber']),
                'bkn_id' => $this->nullable($data['bkn_id']),
                'status_crawl' => $this->nullable($data['status_crawl'] ?? ''),
                'jenis_sertifikasi' => $this->nullable($data['jenis_sertifikasi']),
                'nama_diklat' => trim($data['nama_diklat']),
                'no_sertifikat' => $this->nullable($data['no_sertifikat']),
                'penyelenggara' => $this->nullable($data['penyelenggara']),
                'pelaksanaan' => $this->nullable($data['pelaksanaan']),
                'tanggal_mulai' => $this->nullable($data['tanggal_mulai']),
                'tanggal_selesai' => $this->nullable($data['tanggal_selesai']),
                'tahun' => $this->nullableInteger($data['tahun']),
                'jp' => $this->nullableInteger($data['jp']),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) === 500) {
                DB::table('riwayat_diklat')->insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }
        fclose($handle);

        if ($batch) {
            DB::table('riwayat_diklat')->insert($batch);
            $total += count($batch);
        }

        $this->command->info("Berhasil import {$total} riwayat diklat/sertifikasi.");
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
