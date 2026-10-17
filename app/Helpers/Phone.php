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
}
