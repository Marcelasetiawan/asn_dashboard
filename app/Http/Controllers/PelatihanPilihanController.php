<?php

namespace App\Http\Controllers;

use App\Models\PelatihanDipilih;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PelatihanPilihanController extends Controller
{
    /**
     * POST /pelatihan-pilihan
     *
     * Simpan pilihan pelatihan seorang pegawai. Body JSON:
     * {
     *   "nip": "...",
     *   "nama_okupasi": "...",
     *   "pelatihan": [ { "nama": "...", "kode_standar": "..." }, ... ]
     * }
     *
     * Pilihan lama pegawai itu untuk okupasi yang sama DIGANTI TOTAL
     * dengan yang baru dikirim (supaya gampang: uncheck di UI = otomatis
     * hilang dari database begitu disimpan ulang).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nip' => 'required|string|exists:pegawai,nip',
            'nama_okupasi' => 'required|string',
            'pelatihan' => 'array',
            'pelatihan.*.nama' => 'required|string',
            'pelatihan.*.kode_standar' => 'nullable|string',
        ]);

        // ASN cuma boleh ubah pilihan pelatihan MILIK SENDIRI -- admin bebas
        // mengubah pilihan siapa saja (dipakai dari dashboard admin).
        $user = $request->user();
        if ($user->role !== 'admin' && $data['nip'] !== $user->nip) {
            abort(403, 'Anda hanya bisa mengubah pilihan pelatihan milik sendiri.');
        }

        PelatihanDipilih::where('nip', $data['nip'])
            ->where('nama_okupasi', $data['nama_okupasi'])
            ->delete();

        foreach ($data['pelatihan'] ?? [] as $p) {
            PelatihanDipilih::create([
                'nip' => $data['nip'],
                'nama_okupasi' => $data['nama_okupasi'],
                'nama_pelatihan' => $p['nama'],
                'kode_standar' => $p['kode_standar'] ?? null,
                'dipilih_pada' => now(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'jumlah_dipilih' => count($data['pelatihan'] ?? []),
        ]);
    }

    /**
     * GET /pelatihan-pilihan/{nip}
     *
     * Lihat semua pelatihan yang sudah dipilih seorang pegawai (dipakai
     * untuk pre-check daftar pilihan waktu modalnya dibuka lagi).
     */
    public function show(Request $request, string $nip): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $nip !== $user->nip) {
            abort(403);
        }

        $rows = PelatihanDipilih::where('nip', $nip)->get(['nama_okupasi', 'nama_pelatihan', 'kode_standar', 'dipilih_pada']);
        return response()->json(['nip' => $nip, 'pelatihan_dipilih' => $rows]);
    }
}
