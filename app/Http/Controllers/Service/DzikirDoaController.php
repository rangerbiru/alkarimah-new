<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\DzikirDoa;
use Illuminate\Http\Request;

class DzikirDoaController extends Controller
{
    private $feature = 'backend.service.';
    private $icon = 'fa-solid fa-book-quran';

    private $listDzikirDoa = [
        [
            'title' => 'Dzikir Pagi',
            'slug' => 'dzikir-pagi',
            'image' => 'dzikir-pagi-petang.jpeg',
        ],
        [
            'title' => 'Dzikir Petang',
            'slug' => 'dzikir-petang',
            'image' => 'dzikir-pagi-petang.jpeg',
        ],
        [
            'title' => "Do'a Pilihan",
            'slug' => 'doa-pilihan',
            'image' => 'doa-pilihan.png',
        ]
    ];

    public function index()
    {

        $dataDzikirDoa = DzikirDoa::all();

        return view($this->feature . 'dzikir-doa.index', [
            'title' => 'Dzikir Doa',
            'icon' => $this->icon,
            'dataDzikirDoa' => $dataDzikirDoa,
            'listDzikirDoa' => $this->listDzikirDoa,
        ]);
    }

    public function dzikirDoaById(Request $request, $slug)
    {
        $search = $request->input('q');

        $dataDzikirDoa = DzikirDoa::where('slug', $slug)
            ->when($search, function ($queryBuilder) use ($search) {
                return $queryBuilder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('arabic', 'like', '%' . $search . '%')
                    ->orWhere('arti', 'like', '%' . $search . '%')
                    ->orWhere('penjelasan', 'like', '%' . $search . '%');
            })
            ->get();

        if ($search) {
            foreach ($dataDzikirDoa as $item) {
                $item->title = $this->highlightText($item->title, $search);
                $item->arabic = $this->highlightText($item->arabic, $search);
                $item->arti = $this->highlightText($item->arti, $search);
                $item->penjelasan = $this->highlightText($item->penjelasan, $search);
            }
        }

        return view($this->feature . 'dzikir-doa.detail', [
            'title' => 'Dzikir Doa',
            'icon' => $this->icon,
            'dataDzikirDoa' => $dataDzikirDoa,
        ]);
    }


    private function highlightText($text, $query)
    {
        if (!$query) {
            return $text;
        }

        return preg_replace("/(" . preg_quote($query, '/') . ")/i", "<mark>$1</mark>", $text);
    }
}
