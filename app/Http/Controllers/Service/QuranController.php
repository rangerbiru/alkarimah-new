<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class QuranController extends Controller
{
    private $title = 'Dashboard';
    private $icon = 'fa-solid fa-book-quran';
    private $path = 'backend.dashboard.';
    private $feature = 'backend.service.quran.';
    public function quran()
    {
        $quranData = Http::get("https://equran.id/api/v2/surat")->json()['data'];

        return view($this->feature . 'quran', [
            'title' => 'Al-Qur`an',
            'icon' => 'bx bxs-book-open',
            'quranData' => $quranData,
            'icon' => $this->icon
        ]);
    }

    public function quranById($id)
    {
        $quranDataById = Http::get("https://equran.id/api/v2/surat/$id")->json()['data'];

        $qariNames = [
            "01" => "Abdullah Al-Juhany",
            "02" => "Abdul Muhsin Al-Qasim",
            "03" => "Abdurrahman As-Sudais",
            "04" => "Ibrahim Al-Dossari",
            "05" => "Misyari Rasyid Al-Afasi"
        ];

        foreach ($quranDataById['ayat'] as &$ayat) {
            $newAudioList = [];
            foreach ($ayat['audio'] as $key => $url) {
                if (isset($qariNames[$key])) {
                    $newAudioList[$qariNames[$key]] = $url;
                }
            }
            $ayat['audio'] = $newAudioList;
        }

        return view($this->feature . 'quran-by-id', [
            'title' => 'Surah ' . $quranDataById['namaLatin'],
            'icon' => $this->icon
        ], compact('quranDataById'));
    }
}
