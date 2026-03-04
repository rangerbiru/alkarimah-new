<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Bendahara = 'bendahara';
    case Kasir = 'kasir';
    case PenanggungJawabTabungan = 'penanggung-jawab-tabungan';
    case OrangTua = 'orang-tua';
    case WaliKelas = 'wali-kelas';
    case Pegawai = 'pegawai';
}
