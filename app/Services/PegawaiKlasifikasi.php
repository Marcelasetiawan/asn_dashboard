<?php

namespace App\Services;

/**
 * Porting 1:1 dari proses_data.py (script Python yang dipakai untuk membangun
 * prototipe Dashboard Bangkom ASN). Semua konstanta & aturan di sini SENGAJA
 * dibuat identik dengan versi Python supaya hasil klasifikasi live dari
 * database ini sama persis dengan yang tampil di prototipe HTML.
 *
 * Kalau daftar kata kunci / bidang gelar di proses_data.py diubah di masa
 * depan, ubah juga persis yang sama di sini (dan sebaliknya) supaya kedua
 * versi tidak "menceng" satu sama lain.
 */
class PegawaiKlasifikasi
{
    /**
     * Gelar akademik di depan nama yang dikenali & dipisahkan.
     */
    public const GELAR_DEPAN = ['Dr.', 'Prof.', 'Ir.', 'Drs.', 'Dra.', 'H.', 'Hj.'];

    /**
     * Kata kunci jabatan yang dianggap "TIK" (dipakai untuk klasifikasi
     * kelompok ASN, dan juga sebagai kata kunci jabatan untuk bidang
     * "Teknologi Informasi" di KATA_KUNCI_JABATAN_PER_BIDANG).
     */
    public const KATA_KUNCI_IT_JABATAN = [
        'komputer', 'informatika', 'informasi', 'jaringan', 'data', 'digital', 'sistem',
        'aplikasi', 'teknologi', 'siber', 'cyber', 'programmer', 'developer', 'pengembang',
        'perangkat lunak', 'software', 'hardware', 'database', 'basis data', 'server',
        'cloud', 'website', 'web', 'analis sistem', 'sandi', 'persandian',
        'statistisi', 'statistik',
    ];

    /**
     * Daftar (nama bidang => token gelar EXACT, tanpa titik/spasi, huruf kecil)
     * yang jadi ciri satu bidang keilmuan tertentu. HANYA gelar yang TIDAK
     * AMBIGU yang dimasukkan -- "ST" (Sarjana Teknik umum), "S.Si"/"M.Si"
     * (Sains umum) SENGAJA tidak dimasukkan ke bidang manapun karena
     * dipakai lintas banyak jurusan berbeda.
     */
    public const BIDANG_GELAR = [
        'Teknologi Informasi' => ['skom', 'mkom', 'amdkom', 'sti', 'mti', 'amdti', 'sikom', 'sinf', 'minf', 'amdinf'],
        'Kesehatan' => ['dokter', 'drg', 'skep', 'ners', 'amdkep', 'skm', 'mkes', 'sgz', 'amdgz', 'sfarm', 'apt',
                         'amdkeb', 'skeb', 'amdfarm', 'amdrmik', 'srmik', 'amdrad', 'amdak', 'amdanalis'],
        'Pendidikan' => ['spd', 'mpd', 'spdi', 'mpdi'],
        'Hukum' => ['sh', 'mh', 'mkn', 'llm'],
        'Ekonomi/Akuntansi' => ['se', 'me', 'sakun', 'sak', 'mm', 'akt'],
        'Pertanian/Peternakan/Perikanan' => ['sp', 'mp', 'spt', 'mpt', 'spi', 'amdpi'],
        'Psikologi' => ['spsi', 'mpsi', 'psikolog'],
        'Arsitektur/Tata Ruang' => ['sars', 'mars'],
        'Sosial & Komunikasi Publik' => ['ssos', 'msos'],
        'Administrasi Publik' => ['sap', 'map'],
    ];

    /**
     * Kata kunci jabatan yang dianggap "sesuai" untuk masing-masing bidang gelar.
     */
    public const KATA_KUNCI_JABATAN_PER_BIDANG = [
        'Teknologi Informasi' => self::KATA_KUNCI_IT_JABATAN,
        'Kesehatan' => ['kesehatan', 'medis', 'dokter', 'perawat', 'bidan', 'apoteker', 'farmasi', 'gizi',
                         'epidemiolog', 'kefarmasian', 'puskesmas', 'laboratorium kesehatan', 'radiografer',
                         'rekam medis', 'promosi kesehatan', 'sanitasi', 'sanitarian'],
        'Pendidikan' => ['guru', 'pengawas sekolah', 'widyaiswara', 'instruktur', 'pamong belajar', 'pendidikan',
                          'pamong', 'penilik'],
        'Hukum' => ['hukum', 'advokat', 'legal', 'peraturan perundang', 'perancang peraturan'],
        'Ekonomi/Akuntansi' => ['akuntan', 'keuangan', 'anggaran', 'pajak', 'aset', 'verifikator keuangan',
                                 'perbendaharaan', 'akuntansi'],
        'Pertanian/Peternakan/Perikanan' => ['pertanian', 'peternakan', 'perikanan', 'penyuluh pertanian',
                                              'pengawas benih', 'pengawas bibit', 'medik veteriner', 'veteriner'],
        'Psikologi' => ['psikolog', 'konseling', 'bimbingan konseling'],
        'Arsitektur/Tata Ruang' => ['arsitek', 'tata ruang', 'penata ruang', 'penataan bangunan', 'perencana wilayah'],
        'Sosial & Komunikasi Publik' => ['penyuluh sosial', 'kehumasan', 'humas', 'informasi publik',
                                          'pekerja sosial', 'rehabilitasi sosial'],
        'Administrasi Publik' => ['administrasi', 'tata usaha', 'kearsipan', 'pengadministrasi'],
    ];

    /**
     * Pecah kolom "nama" mentah (format: "Dr. Ir. NAMA, ST., M.Pd") menjadi
     * [gelar_depan, nama_bersih, gelar_belakang].
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public static function pisahNamaGelar(?string $nama): array
    {
        if (!$nama) {
            return ['', '', ''];
        }
        $sisa = trim($nama);
        $depan = [];
        while (true) {
            $cocok = null;
            foreach (self::GELAR_DEPAN as $g) {
                if (str_starts_with($sisa, $g . ' ')) {
                    $cocok = $g;
                    break;
                }
            }
            if ($cocok) {
                $depan[] = $cocok;
                $sisa = trim(substr($sisa, strlen($cocok)));
            } else {
                break;
            }
        }
        if (str_contains($sisa, ',')) {
            [$namaBersih, $belakang] = explode(',', $sisa, 2);
        } else {
            $namaBersih = $sisa;
            $belakang = '';
        }
        return [
            implode(' ', $depan),
            trim($namaBersih),
            trim($belakang, " .\t\n\r\0\x0B"),
        ];
    }

    private static function normalisasi(string $s): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    }

    /**
     * Cocokkan substring/whitespace-padded terhadap daftar kata kunci
     * (bukan exact match -- dipakai untuk teks jabatan yang bisa mengandung
     * kata kunci di tengah kalimat).
     */
    public static function mengandungKataKunci(?string $teks, array $daftarKata): bool
    {
        $t = ' ' . self::normalisasi($teks ?? '') . ' ';
        foreach ($daftarKata as $k) {
            if (str_contains($t, $k)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pecah gelar jadi token-token EXACT (tanpa titik/spasi) untuk dicocokkan
     * ke daftar token bidang -- BUKAN substring match, supaya "ST" (Sarjana
     * Teknik) tidak ikut cocok ke token yang mengandung "st" di tengahnya.
     */
    private static function tokenGelar(?string $gelar): array
    {
        if (!$gelar || trim($gelar) === '') {
            return [];
        }
        $mentah = preg_split('/[,\s]+/', trim($gelar));
        return array_map(
            fn ($tok) => strtolower(preg_replace('/[.\s]/', '', $tok)),
            $mentah
        );
    }

    public static function jabatanMengandungIt(?string $jabatan): bool
    {
        return self::mengandungKataKunci($jabatan, self::KATA_KUNCI_IT_JABATAN);
    }

    /**
     * Kembalikan nama bidang kalau gelar belakang seseorang jelas & tidak
     * ambigu menunjukkan satu bidang tertentu; null kalau tidak ada yang cocok.
     */
    public static function bidangGelar(?string $gelarBelakang): ?string
    {
        $token = self::tokenGelar($gelarBelakang);
        foreach ($token as $tok) {
            foreach (self::BIDANG_GELAR as $namaBidang => $tokenSet) {
                if (in_array($tok, $tokenSet, true)) {
                    return $namaBidang;
                }
            }
        }
        return null;
    }

    /**
     * Klasifikasi kelompok ASN: Manajerial (punya eselon) > TIK (jabatan
     * mengandung kata kunci IT) > Non TIK (selain itu).
     */
    public static function kelompok(?string $jabatan, ?string $eselon): string
    {
        if ($eselon !== null && trim((string) $eselon) !== '') {
            return 'Manajerial';
        }
        if (self::jabatanMengandungIt($jabatan)) {
            return 'TIK';
        }
        return 'Non TIK';
    }

    /**
     * True kalau: tidak ada bidang gelar spesifik (bukan kandidat penilaian),
     * ATAU jabatannya memang menyinggung kata kunci bidang gelarnya.
     */
    public static function jabatanSesuaiBidang(?string $jabatan, ?string $bidangGelar): bool
    {
        if (!$bidangGelar) {
            return true;
        }
        $kataKunci = self::KATA_KUNCI_JABATAN_PER_BIDANG[$bidangGelar] ?? [];
        return self::mengandungKataKunci($jabatan, $kataKunci);
    }

    /**
     * True kalau ASN ini perlu direkomendasikan pelatihan penyegaran
     * kompetensi: (a) gelarnya jelas menunjukkan satu bidang tertentu,
     * (b) jabatannya sama sekali tidak menyinggung bidang itu, dan
     * (c) bukan pegawai manajerial.
     */
    public static function rekomendasiPelatihan(?string $bidangGelar, bool $jabatanSesuaiBidang, bool $punyaEselon): bool
    {
        return $bidangGelar !== null && !$jabatanSesuaiBidang && !$punyaEselon;
    }

    /**
     * True kalau nomor sertifikat terisi & bukan placeholder "-".
     */
    public static function sertifikatLengkap(?string $noSertifikat): bool
    {
        $v = trim((string) $noSertifikat);
        return $v !== '' && $v !== '-';
    }
}
