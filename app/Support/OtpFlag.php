<?php

namespace App\Support;

/**
 * Tanda di session bahwa OTP sudah terverifikasi. Terikat ke data tertentu
 * (misalnya parent id + nomor HP), kedaluwarsa, dan dibuang setelah dipakai.
 */
class OtpFlag
{
    public const TTL_MINUTES = 15;

    public static function grant(string $key, array $bindings): void
    {
        session()->put('otp.'.$key, [
            'bindings' => self::normalize($bindings),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
        ]);
    }

    public static function check(string $key, array $bindings): bool
    {
        $flag = session('otp.'.$key);

        return is_array($flag)
            && ($flag['expires_at'] ?? 0) >= now()->getTimestamp()
            && ($flag['bindings'] ?? null) === self::normalize($bindings);
    }

    public static function forget(string $key): void
    {
        session()->forget('otp.'.$key);
    }

    private static function normalize(array $bindings): array
    {
        ksort($bindings);

        return array_map('strval', $bindings);
    }
}
