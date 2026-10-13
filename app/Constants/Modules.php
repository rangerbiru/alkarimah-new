<?php

namespace App\Constants;

abstract class Modules
{
    const CreateAbsence = 1;
    const KepalaSekolah = 2;
    const WaliKelas = 3;
    const Kasir = 4;
    const KasirUnit = 5;
    const Bendahara = 6;
    const PenanggungJawabTabungan = 7;

    /**
     * Akses TU (slug role) => modul yang membukanya untuk akun pegawai.
     */
    const ByAccess = [
        'kepala-sekolah' => self::KepalaSekolah,
        'wali-kelas' => self::WaliKelas,
        'kasir' => self::Kasir,
        'kasir-unit' => self::KasirUnit,
        'bendahara' => self::Bendahara,
        'penanggung-jawab-tabungan' => self::PenanggungJawabTabungan,
    ];

    /**
     * Modul yang wajib punya jenjang (tabel user_education_levels).
     */
    const WithEducationLevels = [self::KepalaSekolah, self::KasirUnit];
}
