<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\RiwayatDiklat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // ── Konstanta mapping (samakan dengan Model Python) ──────────────────
    const REF_YEAR = 2026;
    const GOL_ORDER = ['II/c'=>1,'II/d'=>2,'III/a'=>3,'III/b'=>4,'III/c'=>5,'III/d'=>6,'IV/a'=>7,'IV/b'=>8,'IV/c'=>9,'IV/d'=>10];
    const PEND_ORDER = ['DI'=>1,'DII'=>2,'DIII'=>3,'DIV'=>4,'S1'=>5,'S2'=>6,'S3'=>7];

    // kata kunci diklat GENERIK (soft-skill/budaya kerja, berlaku semua ASN apapun profesinya)
    const KATA_KUNCI_GENERIK = [
        'adaptif', 'loyal', 'digital habit', 'kepemimpinan di era digital',
        'pembinaan pegawai', 'webinar asn belajar', 'cerdas bermedsos',
        'peningkatan kapasitas kinerja', 'manajemen risiko', 'orientasi',
        'latihan pra jabatan', 'asn bersinar', 'asn digital', 'mfa',
    ];

    /**
     * Halaman utama (Single Page, 4 tab difokuskan lewat JS).
     */
    public function index()
    {
        return view('dashboard');
    }

    // ══════════════════════════════════════════════════════════════════
    // HALAMAN 1: RINGKASAN (poin 1,4,9,10 — lebih bermakna, filter tahun,
    // sinkron antar chart, metrik 20 JP/tahun)
    // ══════════════════════════════════════════════════════════════════
    public function apiRingkasan(Request $request)
    {
        $tahunFilter = $request->query('tahun'); // null = semua tahun

        $pegawai = Pegawai::all();
        $riwayat = RiwayatDiklat::all();

        // ekstrak tahun dari kolom pelaksanaan (format bervariasi: "29 Mei 2024" / "06-Sep-24")
        $riwayat = $riwayat->map(function ($r) {
            $r->tahun_ekstrak = $this->ekstrakTahun($r->pelaksanaan);
            return $r;
        });

        $tahunTersedia = $riwayat->pluck('tahun_ekstrak')->filter()->unique()->sort()->values();

        if ($tahunFilter) {
            $riwayat = $riwayat->where('tahun_ekstrak', (int) $tahunFilter);
        }

        // JP & jumlah diklat per pegawai (sudah difilter tahun kalau ada)
        $jpPerNip = $riwayat->groupBy('nip')->map(fn($g) => $g->sum('jp'));
        $jmlPerNip = $riwayat->groupBy('nip')->map->count();

        $totalAsn = $pegawai->count();
        $totalJp = $riwayat->sum('jp');
        $totalRiwayat = $riwayat->count();

        // Metrik UTAMA baru sesuai feedback: berapa ASN memenuhi standar 20 JP/tahun (bukan cuma "aktif")
        $asnMemenuhi = 0;
        foreach ($pegawai as $p) {
            $jp = $jpPerNip[$p->nip] ?? 0;
            if ($jp >= 20) $asnMemenuhi++;
        }

        // Top 15 diklat (untuk chart, ikut filter tahun)
        $top15 = $riwayat->groupBy('nama_diklat')
            ->map(fn($g) => ['nama' => $g->first()->nama_diklat, 'jenis' => $g->first()->jenis_sertifikasi, 'jumlah' => $g->count()])
            ->sortByDesc('jumlah')->take(15)->values();

        // Distribusi jenis sertifikasi (dipakai untuk cross-filter poin 2)
        $jenisDist = $riwayat->groupBy('jenis_sertifikasi')
            ->map(fn($g, $k) => ['jenis' => $k ?: 'Tidak Diketahui', 'jumlah' => $g->count()])
            ->values();

        // Rata-rata JP per tahun (poin 9: rata-rata pertahun)
        $rataJpPerTahun = $riwayat->groupBy('tahun_ekstrak')
            ->filter(fn($g, $k) => $k !== null)
            ->map(fn($g, $k) => ['tahun' => $k, 'total_jp' => $g->sum('jp'), 'jumlah_diklat' => $g->count()])
            ->sortKeys()->values();

        return response()->json([
            'total_asn' => $totalAsn,
            'total_riwayat' => $totalRiwayat,
            'total_jp' => (int) $totalJp,
            'asn_memenuhi_20jp' => $asnMemenuhi,
            'pct_memenuhi_20jp' => $totalAsn ? round($asnMemenuhi / $totalAsn * 100, 1) : 0,
            'tahun_tersedia' => $tahunTersedia,
            'tahun_filter' => $tahunFilter,
            'top15_diklat' => $top15,
            'jenis_distribusi' => $jenisDist,
            'rata_jp_per_tahun' => $rataJpPerTahun,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // HALAMAN 2: DIKLAT & UNIT KERJA (GABUNGAN — poin 7)
    // Filter silang: pilih jenis diklat -> daftar diklat & unit kerja ikut ke-filter
    // ══════════════════════════════════════════════════════════════════
    public function apiDiklatUnit(Request $request)
    {
        $q = $request->query('q', '');
        $jenis = $request->query('jenis', '');
        $unit = $request->query('unit', '');

        $riwayatQuery = RiwayatDiklat::query();
        if ($q) $riwayatQuery->where('nama_diklat', 'like', "%{$q}%");
        if ($jenis) $riwayatQuery->where('jenis_sertifikasi', $jenis);

        $riwayat = $riwayatQuery->get()->load('pegawai');
        // load relasi manual karena RiwayatDiklat::with('pegawai') lebih efisien, tapi supaya jelas:
        $nipList = $riwayat->pluck('nip')->unique();
        $pegawaiMap = Pegawai::whereIn('nip', $nipList)->get()->keyBy('nip');

        if ($unit) {
            $riwayat = $riwayat->filter(function ($r) use ($pegawaiMap, $unit) {
                $p = $pegawaiMap[$r->nip] ?? null;
                return $p && $p->satuan_kerja === $unit;
            })->values();
        }

        // daftar diklat (kiri)
        $diklatList = $riwayat->groupBy('nama_diklat')->map(function ($g, $nama) {
            return [
                'nama_diklat' => $nama,
                'jenis' => $g->first()->jenis_sertifikasi,
                'jumlah' => $g->count(),
            ];
        })->sortByDesc('jumlah')->values();

        // rekap unit kerja (kanan) — otomatis ikut ter-filter oleh $q / $jenis di atas
        $unitRekap = [];
        foreach ($riwayat as $r) {
            $p = $pegawaiMap[$r->nip] ?? null;
            if (!$p) continue;
            $uk = $p->satuan_kerja ?: '(Tidak diketahui)';
            if (!isset($unitRekap[$uk])) {
                $unitRekap[$uk] = ['unit_kerja' => $uk, 'jumlah_riwayat' => 0, 'nip_unik' => []];
            }
            $unitRekap[$uk]['jumlah_riwayat']++;
            $unitRekap[$uk]['nip_unik'][$r->nip] = true;
        }
        $unitRekap = collect($unitRekap)->map(function ($u) {
            $u['jumlah_pegawai_terlibat'] = count($u['nip_unik']);
            unset($u['nip_unik']);
            return $u;
        })->sortByDesc('jumlah_riwayat')->values();

        return response()->json([
            'diklat_list' => $diklatList,
            'unit_rekap' => $unitRekap,
            'total_hasil' => $riwayat->count(),
        ]);
    }

    /**
     * Detail peserta untuk 1 nama diklat (dipanggil saat klik baris di tabel).
     */
    public function apiDiklatPeserta(Request $request)
    {
        $nama = $request->query('nama');
        $riwayat = RiwayatDiklat::where('nama_diklat', $nama)->get();
        $nipList = $riwayat->pluck('nip')->unique();
        $pegawaiMap = Pegawai::whereIn('nip', $nipList)->get()->keyBy('nip');

        $hasil = $riwayat->map(function ($r) use ($pegawaiMap) {
            $p = $pegawaiMap[$r->nip] ?? null;
            return [
                'nip' => $r->nip,
                'nama' => $p->nama ?? '-',
                'satuan_kerja' => $p->satuan_kerja ?? '-',
                'pelaksanaan' => $r->pelaksanaan,
                'jp' => $r->jp,
            ];
        })->values();

        return response()->json($hasil);
    }

    // ══════════════════════════════════════════════════════════════════
    // HALAMAN 3: ANALISIS LANJUTAN (DIFOKUSKAN — poin 5, 6, 8, 12)
    // Fokus ke 2 insight paling bermakna: kepatuhan standar JP per dinas,
    // dan relevansi nama diklat vs instansi (crossmatch)
    // ══════════════════════════════════════════════════════════════════
    public function apiAnalisisLanjutan()
    {
        $pegawai = Pegawai::all();
        $riwayat = RiwayatDiklat::all();

        $jpPerNip = $riwayat->groupBy('nip')->map(fn($g) => $g->sum('jp'));

        // ── A. Rata-rata JP & kepatuhan standar per satuan kerja ──
        $perUnit = [];
        foreach ($pegawai as $p) {
            $uk = $p->satuan_kerja ?: '(Tidak diketahui)';
            if (!isset($perUnit[$uk])) {
                $perUnit[$uk] = ['unit_kerja' => $uk, 'jml_pegawai' => 0, 'total_jp' => 0, 'jml_memenuhi' => 0];
            }
            $jp = $jpPerNip[$p->nip] ?? 0;
            $perUnit[$uk]['jml_pegawai']++;
            $perUnit[$uk]['total_jp'] += $jp;
            if ($jp >= 20) $perUnit[$uk]['jml_memenuhi']++;
        }
        $perUnit = collect($perUnit)->map(function ($u) {
            $u['rata_jp'] = $u['jml_pegawai'] ? round($u['total_jp'] / $u['jml_pegawai'], 1) : 0;
            $u['pct_memenuhi'] = $u['jml_pegawai'] ? round($u['jml_memenuhi'] / $u['jml_pegawai'] * 100, 1) : 0;
            return $u;
        })->filter(fn($u) => $u['jml_pegawai'] >= 10);

        $rataJpTerendah = $perUnit->sortBy('rata_jp')->take(5)->values();
        $rataJpTertinggi = $perUnit->sortByDesc('rata_jp')->take(5)->values();

        // ── B. Relevansi nama diklat: generik vs spesifik domain ──
        $riwayat = $riwayat->map(function ($r) {
            $r->kategori_relevansi = $this->kategoriRelevansi($r->nama_diklat);
            return $r;
        });
        $relevansiCount = $riwayat->groupBy('kategori_relevansi')
            ->map(fn($g, $k) => ['kategori' => $k, 'jumlah' => $g->count()])
            ->values();

        $totalRiwayat = $riwayat->count();
        $pctGenerik = $totalRiwayat ? round($riwayat->where('kategori_relevansi', 'Generik (budaya kerja/soft-skill)')->count() / $totalRiwayat * 100, 1) : 0;

        // contoh nyata diklat spesifik domain + distribusi unit kerja pesertanya (poin 8: crossmatch)
        $spesifik = $riwayat->where('kategori_relevansi', 'Spesifik domain/teknis');
        $nipList = $spesifik->pluck('nip')->unique();
        $pegawaiMap = Pegawai::whereIn('nip', $nipList)->get()->keyBy('nip');

        $crossmatchContoh = $spesifik->groupBy('nama_diklat')->map(function ($g, $nama) use ($pegawaiMap) {
            $unitTerlibat = $g->map(fn($r) => $pegawaiMap[$r->nip]->satuan_kerja ?? '-')
                ->countBy()->sortDesc()->take(3);
            return [
                'nama_diklat' => $nama,
                'jumlah_peserta' => $g->count(),
                'unit_terbanyak' => $unitTerlibat,
            ];
        })->sortByDesc('jumlah_peserta')->take(10)->values();

        return response()->json([
            'rata_jp_terendah' => $rataJpTerendah,
            'rata_jp_tertinggi' => $rataJpTertinggi,
            'relevansi_count' => $relevansiCount,
            'pct_generik' => $pctGenerik,
            'crossmatch_contoh' => $crossmatchContoh,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // HALAMAN 4: PREDIKSI (DIFOKUSKAN — poin 3)
    // Cukup 1 fungsi inti: prediksi + alasan. Tidak ditambah fitur lain
    // yang tidak berkaitan langsung.
    // ══════════════════════════════════════════════════════════════════
    public function apiPrediksi(Request $request)
    {
        $genderEnc = (int) $request->input('gender', 0);
        $golEnc = (int) $request->input('golongan', 5);
        $pendEnc = (int) $request->input('pendidikan', 5);
        $eselon = (int) $request->input('eselon', 0);
        $masaKerja = (float) $request->input('masa_kerja', 5);
        $sisaMasaKerja = (float) $request->input('sisa_masa_kerja', 10);

        $hasil = $this->predictTree($genderEnc, $golEnc, $pendEnc, $eselon, $masaKerja, $sisaMasaKerja);

        // konteks data riil (query ke database, bukan cuma output model berdiri sendiri)
        $pegawai = Pegawai::where('golongan_ruang', array_search($golEnc, self::GOL_ORDER))
            ->when($eselon, fn($q) => $q->whereNotNull('eselon'), fn($q) => $q->whereNull('eselon'))
            ->get();

        $basis = "eselon " . ($eselon ? 'struktural' : 'non-struktural') . " & golongan " . array_search($golEnc, self::GOL_ORDER);
        if ($pegawai->count() < 5) {
            $pegawai = Pegawai::when($eselon, fn($q) => $q->whereNotNull('eselon'), fn($q) => $q->whereNull('eselon'))->get();
            $basis = "eselon " . ($eselon ? 'struktural' : 'non-struktural') . " saja (sampel golongan sama terlalu sedikit)";
        }

        $jpPerNip = RiwayatDiklat::whereIn('nip', $pegawai->pluck('nip'))->get()->groupBy('nip')->map(fn($g) => $g->sum('jp'));
        $jmlMemenuhi = 0;
        foreach ($pegawai as $p) {
            if (($jpPerNip[$p->nip] ?? 0) >= 20) $jmlMemenuhi++;
        }
        $pctMemenuhi = $pegawai->count() ? round($jmlMemenuhi / $pegawai->count() * 100, 1) : 0;

        return response()->json([
            'label' => $hasil['label'],
            'proba' => $hasil['proba'],
            'konteks' => [
                'jumlah_mirip' => $pegawai->count(),
                'basis' => $basis,
                'pct_memenuhi_20jp' => $pctMemenuhi,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER FUNCTIONS
    // ══════════════════════════════════════════════════════════════════

    private function ekstrakTahun(?string $pelaksanaan): ?int
    {
        if (!$pelaksanaan || $pelaksanaan === '-') return null;
        if (preg_match('/(20\d{2})/', $pelaksanaan, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/-(\d{2})$/', $pelaksanaan, $m)) {
            return 2000 + (int) $m[1];
        }
        return null;
    }

    private function kategoriRelevansi(string $namaDiklat): string
    {
        $n = strtolower($namaDiklat);
        foreach (self::KATA_KUNCI_GENERIK as $kw) {
            if (str_contains($n, $kw)) {
                return 'Generik (budaya kerja/soft-skill)';
            }
        }
        return 'Spesifik domain/teknis';
    }

    /**
     * Decision Tree hasil training ulang (target: standar 20 JP/tahun).
     * Struktur pohon di-hardcode di sini karena model dilatih di Python,
     * lalu logikanya di-porting manual ke PHP (tidak ada library ML di PHP-nya).
     */
    private function predictTree($genderEnc, $golEnc, $pendEnc, $eselon, $masaKerja, $sisaMasaKerja): array
    {
        if ($eselon <= 0.5) {
            if ($pendEnc <= 5.5) {
                if ($sisaMasaKerja <= 7.5) {
                    if ($golEnc <= 6.5) {
                        return ["label" => "Belum Memenuhi", "proba" => 0.9041];
                    } else {
                        return ["label" => "Memenuhi Standar", "proba" => 0.5312];
                    }
                } else {
                    if ($pendEnc <= 4.5) {
                        return ["label" => "Belum Memenuhi", "proba" => 0.7835];
                    } else {
                        return ["label" => "Belum Memenuhi", "proba" => 0.5756];
                    }
                }
            } else {
                if ($masaKerja <= 3.5) {
                    if ($sisaMasaKerja <= 4.5) {
                        return ["label" => "Belum Memenuhi", "proba" => 1.0000];
                    } else {
                        return ["label" => "Memenuhi Standar", "proba" => 0.7731];
                    }
                } else {
                    if ($masaKerja <= 9.5) {
                        return ["label" => "Belum Memenuhi", "proba" => 0.7000];
                    } else {
                        return ["label" => "Memenuhi Standar", "proba" => 0.8235];
                    }
                }
            }
        } else {
            if ($masaKerja <= 1.5) {
                if ($genderEnc <= 0.5) {
                    if ($golEnc <= 8.5) {
                        return ["label" => "Memenuhi Standar", "proba" => 0.7442];
                    } else {
                        return ["label" => "Belum Memenuhi", "proba" => 1.0000];
                    }
                } else {
                    if ($masaKerja <= 0.5) {
                        return ["label" => "Belum Memenuhi", "proba" => 0.5000];
                    } else {
                        return ["label" => "Belum Memenuhi", "proba" => 1.0000];
                    }
                }
            } else {
                if ($sisaMasaKerja <= 1.5) {
                    if ($masaKerja <= 15.5) {
                        return ["label" => "Memenuhi Standar", "proba" => 0.8214];
                    } else {
                        return ["label" => "Belum Memenuhi", "proba" => 1.0000];
                    }
                } else {
                    if ($golEnc <= 7.5) {
                        return ["label" => "Memenuhi Standar", "proba" => 0.7315];
                    } else {
                        return ["label" => "Memenuhi Standar", "proba" => 0.6071];
                    }
                }
            }
        }
    }
}
