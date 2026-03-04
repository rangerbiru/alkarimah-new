<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Hadist\AbuDaud;
use App\Models\Hadist\IbnuMajah;
use App\Models\Hadist\MusnadAhmad;
use App\Models\Hadist\MuwathoMalik;
use App\Models\Hadist\Nasai;
use App\Models\Hadist\ShahihBukhari;
use App\Models\Hadist\ShahihMuslim;
use App\Models\Hadist\Tirmidzi;
use Illuminate\Http\Request;

class HadistController extends Controller
{

    private $title = 'Dashboard';
    private $icon = 'fa-solid fa-book-quran';
    private $path = 'backend.dashboard.';
    private $feature = 'backend.service.';

    private $listHadist = [
        [
            'nomor' => 1,
            'judul' => 'Musnad Ahmad',
            'total' => 21848,
            'slug' => 'musnad-ahmad',
            'image' => 'ahmad.jpg',
        ],
        [
            'nomor' => 2,
            'judul' => 'Muwatho Malik',
            'total' => 1594,
            'slug' => 'muwatho-malik',
            'image' => 'malik.jpg',
        ],
        [
            'nomor' => 3,
            'judul' => 'Shahih Bukhari',
            'total' => 7008,
            'slug' => 'shahih-bukhari',
            'image' => 'bukhari.jpg',
        ],
        [
            'nomor' => 4,
            'judul' => 'Shahih Muslim',
            'total' => 5362,
            'slug' => 'shahih-muslim',
            'image' => 'muslim.jpg',
        ],
        [
            'nomor' => 5,
            'judul' => 'Abu Daud',
            'total' => 4590,
            'slug' => 'abu-daud',
            'image' => 'abu-daud.jpg',
        ],
        [
            'nomor' => 6,
            'judul' => 'Ibnu Majah',
            'total' => 4332,
            'slug' => 'ibnu-majah',
            'image' => 'ibnu-majah.jpg',
        ],
        [
            'nomor' => 7,
            'judul' => 'Tirmidzi',
            'total' => 3891,
            'slug' => 'tirmidzi',
            'image' => 'tirmidzi.jpg',
        ],
        [
            'nomor' => 8,
            'judul' => 'Nasai',
            'total' => 5662,
            'slug' => 'nasai',
            'image' => 'nasai.jpg',
        ],
    ];

    public function hadist()
    {
        return view($this->feature . 'hadist.index', [
            'title' => 'Hadist',
            'icon' => $this->icon,
            'listHadist' => $this->listHadist,
        ]);
    }

    public function hadistById($id)
    {
        $hadistList = [
            'musnad-ahmad' => [
                'model' => MusnadAhmad::class,
                'title' => $this->listHadist[0]['judul'],
                'slug' => $this->listHadist[0]['slug']
            ],
            'muwatho-malik' => [
                'model' => MuwathoMalik::class,
                'title' => $this->listHadist[1]['judul'],
                'slug' => $this->listHadist[1]['slug']
            ],
            'shahih-bukhari' => [
                'model' => ShahihBukhari::class,
                'title' => $this->listHadist[2]['judul'],
                'slug' => $this->listHadist[2]['slug']
            ],
            'shahih-muslim' => [
                'model' => ShahihMuslim::class,
                'title' => $this->listHadist[3]['judul'],
                'slug' => $this->listHadist[3]['slug']
            ],
            'abu-daud' => [
                'model' => AbuDaud::class,
                'title' => $this->listHadist[4]['judul'],
                'slug' => $this->listHadist[4]['slug']
            ],
            'ibnu-majah' => [
                'model' => IbnuMajah::class,
                'title' => $this->listHadist[5]['judul'],
                'slug' => $this->listHadist[5]['slug']
            ],
            'tirmidzi' => [
                'model' => Tirmidzi::class,
                'title' => $this->listHadist[6]['judul'],
                'slug' => $this->listHadist[6]['slug']
            ],
            'nasai' => [
                'model' => Nasai::class,
                'title' => $this->listHadist[7]['judul'],
                'slug' => $this->listHadist[7]['slug']
            ]
        ];

        if (!array_key_exists($id, $hadistList)) {
            abort(404, 'Hadist tidak ditemukan');
        }

        $model = $hadistList[$id]['model'];
        $title = $hadistList[$id]['title'];
        $slug = $hadistList[$id]['slug'];

        $hadistData = $model::orderBy('id', 'asc')->paginate(5);

        return view($this->feature . 'hadist.hadist-by-id', [
            'title' => $title,
            'slug' => $slug,
            'icon' => $this->icon,
            'listHadist' => $this->listHadist,
            'hadistData' => $hadistData,
        ]);
    }

    public function hadistSearch(Request $request, $id)
    {
        $hadistList = [
            'musnad-ahmad' => [
                'model' => MusnadAhmad::class,
                'title' => $this->listHadist[0]['judul'],
                'slug' => $this->listHadist[0]['slug']
            ],
            'muwatho-malik' => [
                'model' => MuwathoMalik::class,
                'title' => $this->listHadist[1]['judul'],
                'slug' => $this->listHadist[1]['slug']
            ],
            'shahih-bukhari' => [
                'model' => ShahihBukhari::class,
                'title' => $this->listHadist[2]['judul'],
                'slug' => $this->listHadist[2]['slug']
            ],
            'shahih-muslim' => [
                'model' => ShahihMuslim::class,
                'title' => $this->listHadist[3]['judul'],
                'slug' => $this->listHadist[3]['slug']
            ],
            'abu-daud' => [
                'model' => AbuDaud::class,
                'title' => $this->listHadist[4]['judul'],
                'slug' => $this->listHadist[4]['slug']
            ],
            'ibnu-majah' => [
                'model' => IbnuMajah::class,
                'title' => $this->listHadist[5]['judul'],
                'slug' => $this->listHadist[5]['slug']
            ],
            'tirmidzi' => [
                'model' => Tirmidzi::class,
                'title' => $this->listHadist[6]['judul'],
                'slug' => $this->listHadist[6]['slug']
            ],
            'nasai' => [
                'model' => Nasai::class,
                'title' => $this->listHadist[7]['judul'],
                'slug' => $this->listHadist[7]['slug']
            ]
        ];

        if (!array_key_exists($id, $hadistList)) {
            abort(404, 'Hadist tidak ditemukan');
        }

        $model = $hadistList[$id]['model'];
        $title = $hadistList[$id]['title'];
        $slug = $hadistList[$id]['slug'];

        // Ambil query pencarian
        $query = $request->input('q');

        if ($query) {
            $hadistData = $model::where('arab', 'LIKE', "%$query%")
                ->orWhere('terjemah', 'LIKE', "%$query%")
                ->orderBy('id', 'asc')
                ->paginate(5);

            // Highlight kata pencarian di hasil
            foreach ($hadistData as $hadist) {
                $hadist->arab = $this->highlightText($hadist->arab, $query);
                $hadist->terjemah = $this->highlightText($hadist->terjemah, $query);
            }
        } else {
            $hadistData = $model::orderBy('id', 'asc')->paginate(5);
        }

        return view($this->feature . 'hadist.hadist-search', [
            'title' => $title,
            'slug' => $slug,
            'icon' => $this->icon,
            'listHadist' => $this->listHadist,
            'hadistData' => $hadistData,

        ]);
    }

    private function highlightText($text, $query)
    {
        if (!$query) {
            return $text;
        }

        return preg_replace("/($query)/i", "<mark>$1</mark>", $text);
    }
}
