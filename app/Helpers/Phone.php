<?php

namespace App\Helpers;

class Phone
{
    // Numbers are stored as 08xx, 628xx, +62 8xx, with dashes or spaces. The last 10 digits are
    // the part that survives every format, so two numbers are "the same" when those match
    public static function lastTen($phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) >= 10 ? substr($digits, -10) : null;
    }

    public static function digits($phone): string
    {
        return preg_replace('/\D/', '', (string) $phone);
    }

    /**
     * Bentuk baku nomor HP untuk dibandingkan: tanpa "-", spasi, dan titik; awalan +62 / 62 menjadi 0.
     * Data di DB tidak diubah: bandingkan normalize($input) dengan sql('kolom').
     */
    public static function normalize($phone): string
    {
        $value = str_replace(['-', ' ', '.'], '', (string) $phone);

        if (str_starts_with($value, '+62')) {
            return '0'.substr($value, 3);
        }

        if (str_starts_with($value, '62')) {
            return '0'.substr($value, 2);
        }

        return $value;
    }

    /** Nomor yang tidak pernah dipakai untuk mencari akun */
    public static function isBlank(string $normalized): bool
    {
        return $normalized === '' || $normalized === '0';
    }

    /**
     * Ekspresi SQL yang menormalkan kolom dengan aturan yang sama seperti normalize().
     * $column harus nama kolom tepercaya (bukan input pengguna).
     */
    public static function sql(string $column): string
    {
        $stripped = "REPLACE(REPLACE(REPLACE({$column}, '-', ''), ' ', ''), '.', '')";

        return "(CASE WHEN {$stripped} LIKE '+62%' THEN CONCAT('0', SUBSTRING({$stripped}, 4))"
            ." WHEN {$stripped} LIKE '62%' THEN CONCAT('0', SUBSTRING({$stripped}, 3))"
            ." ELSE {$stripped} END)";
    }
}
