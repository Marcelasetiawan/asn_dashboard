<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\RiwayatDiklat;
use App\Services\BangkomDashboardData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Halaman self-service untuk ASN (role "asn") -- beda dari dashboard admin:
 * cuma menampilkan & mengizinkan pengubahan data MILIK SENDIRI (di-scope
 * lewat NIP dari akun yang sedang login), bukan seluruh pegawai. Dipecah
 * jadi beberapa halaman (Ringkasan/Profil/Riwayat/Pelatihan/Akun) yang
 * berbagi 1 layout sidebar (resources/views/layouts/saya.blade.php),
 * mirip strukturnya dengan dashboard admin tapi jauh lebih sederhana.
 */
class SelfServiceController extends Controller
{
    public function ringkasan(Request $request): View
    {
        $data = $this->pegawaiData($request);

        return view('saya.ringkasan', [
            'pegawai' => $data['pegawai'],
            'rataRataJp' => BangkomDashboardData::rataRataJpSeluruhAsn(),
            'jumlahDipilih' => count($data['dipilihTikNama']) + count($data['dipilihWajibNama']),
        ]);
    }

    public function profil(Request $request): View
    {
        return view('saya.profil', ['pegawai' => $this->pegawaiData($request)['pegawai']]);
    }

    public function riwayat(Request $request): View
    {
        $data = $this->pegawaiData($request);

        return view('saya.riwayat', [
            'pegawai' => $data['pegawai'],
            'riwayat' => $data['riwayat'],
        ]);
    }

    public function pelatihan(Request $request): View
    {
        $data = $this->pegawaiData($request);

        return view('saya.pelatihan', [
            'pegawai' => $data['pegawai'],
            'dipilihTikNama' => $data['dipilihTikNama'],
            'dipilihWajibNama' => $data['dipilihWajibNama'],
        ]);
    }

    public function akun(Request $request): View
    {
        return view('saya.akun', ['pegawai' => $this->pegawaiData($request)['pegawai']]);
    }

    /**
     * PATCH /saya/akun -- ubah data kontak milik sendiri (alamat & email).
     * Field lain (jabatan, gelar, golongan, dst) sengaja TIDAK bisa diubah
     * di sini -- itu data resmi kepegawaian, cuma admin yang bisa perbarui
     * lewat proses impor data.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alamat' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        Pegawai::where('nip', $request->user()->nip)->update($data);

        return back()->with('status', 'Data kontak berhasil diperbarui.');
    }

    /**
     * POST /saya/sertifikat/{riwayat} -- unggah/perbarui nomor & berkas
     * sertifikat untuk SATU baris riwayat diklat milik sendiri. Dicek dulu
     * baris riwayat itu memang punya nip yang sama dengan akun yang login
     * (bukan riwayat orang lain) sebelum diizinkan diubah.
     */
    public function uploadSertifikat(Request $request, RiwayatDiklat $riwayat): RedirectResponse
    {
        if ($riwayat->nip !== $request->user()->nip) {
            abort(403);
        }

        $data = $request->validate([
            'no_sertifikat' => 'required|string|max:255',
            'berkas' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('berkas')) {
            if ($riwayat->berkas_sertifikat) {
                Storage::disk('public')->delete($riwayat->berkas_sertifikat);
            }
            $data['berkas_sertifikat'] = $request->file('berkas')->store('sertifikat/'.$riwayat->nip, 'public');
        }

        $riwayat->update([
            'no_sertifikat' => $data['no_sertifikat'],
            'berkas_sertifikat' => $data['berkas_sertifikat'] ?? $riwayat->berkas_sertifikat,
        ]);

        return back()->with('status', 'Sertifikat untuk "'.$riwayat->nama_diklat.'" berhasil disimpan.');
    }

    /**
     * PUT /saya/password -- ganti password akun sendiri.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => 'required|string',
            'password_baru' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        if (!Hash::check($data['password_lama'], $user->password)) {
            return back()->withErrors(['password_lama' => 'Password lama salah.']);
        }

        $user->update(['password' => Hash::make($data['password_baru'])]);

        return back()->with('status', 'Password berhasil diubah.');
    }

    /**
     * Data pegawai (+ turunannya) milik akun yang sedang login -- dipakai
     * bersama oleh semua halaman "Profil Saya" supaya tidak dobel-tulis.
     */
    private function pegawaiData(Request $request): array
    {
        $nip = $request->user()->nip;
        $data = BangkomDashboardData::buildForNip($nip);
        $pegawai = $data['pegawai'];

        // Pisahkan pelatihan yang sudah dipilih berdasarkan grup (TIK vs
        // Pelatihan Wajib) supaya di Blade tinggal dicek in_array() untuk
        // nge-checked checkbox-nya -- sama seperti pola di dashboard admin.
        $opsi = $pegawai['rekomendasi_pelatihan_opsi'];
        $dipilihTikNama = [];
        $dipilihWajibNama = [];
        foreach ($pegawai['pelatihan_dipilih'] as $d) {
            if ($opsi && $d['nama_okupasi'] === $opsi['nama_okupasi']) {
                $dipilihTikNama[] = $d['nama_pelatihan'];
            }
            if ($d['nama_okupasi'] === 'Pelatihan Wajib') {
                $dipilihWajibNama[] = $d['nama_pelatihan'];
            }
        }

        return [
            'pegawai' => $pegawai,
            'riwayat' => $data['diklat'],
            'dipilihTikNama' => $dipilihTikNama,
            'dipilihWajibNama' => $dipilihWajibNama,
        ];
    }
}
