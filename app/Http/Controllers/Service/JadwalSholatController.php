<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JadwalSholatController extends Controller
{
    private $feature = 'backend.service.jadwal-sholat.';
    private $icon = 'fa-solid fa-mosque';

    public function index()
    {
        return view($this->feature . 'index', [
            'title' => 'Jadwal Sholat',
            'icon' => $this->icon,
        ]);
    }

    public function getJadwalSholat(Request $request)
    {
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');

        if (!$latitude || !$longitude) {
            return response()->json(['error' => 'Lokasi tidak ditemukan'], 400);
        }

        $response = Http::get("https://waktu-sholat.vercel.app/prayer?latitude={$latitude}&longitude={$longitude}");

        if ($response->failed()) {
            return response()->json(['error' => 'Gagal mengambil jadwal sholat'], 500);
        }

        return response()->json($response->json());
    }
}
