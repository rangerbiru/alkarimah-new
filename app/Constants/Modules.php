<?php

namespace App\Constants;

abstract class Modules
{
    // Umum (disamakan dengan PPIA)
    const CreateAbsence = 1;
    const AdminAcademic = 2;
    const AdminHumanResource = 3;
    const AdminInventory = 4;

    // Tata Usaha
    const KepalaSekolah = 5;
    const WaliKelas = 6;
    const Kasir = 7;
    const KasirUnit = 8;
    const Bendahara = 9;
    const PenanggungJawabTabungan = 10;
    const CancelTransaction = 11;

    /**
     * Kelompok modul di halaman Hak Akses pegawai (label => ID modul).
     */
    const Groups = [
        'general' => [self::CreateAbsence, self::AdminAcademic, self::AdminHumanResource, self::AdminInventory],
        'tu' => [self::KepalaSekolah, self::WaliKelas, self::Kasir, self::KasirUnit, self::Bendahara, self::PenanggungJawabTabungan, self::CancelTransaction],
    ];

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
