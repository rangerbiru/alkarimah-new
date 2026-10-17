<?php

namespace App\Helpers;

class Phone
{
    /**
     * 10 digit terakhir nomor HP (tanpa karakter non-digit) untuk membandingkan nomor yang ditulis
     * dengan format berbeda (08.., +62.., 62..). Null bila nomor kosong atau terlalu pendek.
     */
    public static function lastTen(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) >= 8 ? substr($digits, -10) : null;
    }
}
