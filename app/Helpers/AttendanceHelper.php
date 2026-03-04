<?php

namespace App\Helpers;

class AttendanceHelper
{
    /**
     * Cek apakah user berada dalam radius absensi
     *
     * @param float $userLat
     * @param float $userLng
     * @param object $attendanceLocation
     * @return bool
     */
    public static function isWithinAttendanceRadius($userLat, $userLng, $attendanceLocation)
    {
        // Pisahkan koordinat lokasi absensi
        [$locLat, $locLng] = explode(',', $attendanceLocation->coordinate);

        // Konversi ke float
        $locLat = (float) trim($locLat);
        $locLng = (float) trim($locLng);

        // Hitung jarak menggunakan rumus Haversine
        $earthRadius = 6371000; // dalam meter
        $latFrom = deg2rad($userLat);
        $lonFrom = deg2rad($userLng);
        $latTo = deg2rad($locLat);
        $lonTo = deg2rad($locLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $distance = $earthRadius * $angle; // hasil jarak dalam meter

        // Bandingkan dengan radius
        return $distance <= $attendanceLocation->attendance_radius;
    }

    public static function calculateDistance($userLat, $userLng, $attendanceLocation)
    {
        [$locLat, $locLng] = explode(',', $attendanceLocation->coordinate);
        $locLat = (float) trim($locLat);
        $locLng = (float) trim($locLng);

        $earthRadius = 6371000;
        $latFrom = deg2rad($userLat);
        $lonFrom = deg2rad($userLng);
        $latTo = deg2rad($locLat);
        $lonTo = deg2rad($locLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($earthRadius * $angle, 2);
    }
}
