<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\PosterDakwah;
use Illuminate\Http\Request;

class PosterDakwahController extends Controller
{
    private $main = 'backend.service.';

    public function index()
    {

        // $dataPosterDakwah = PosterDakwah::all();

        return view($this->main . 'poster-dakwah.index', [
            'title' => 'Poster Dakwah',
            'icon' => 'fa-solid fa-images',
            // 'dataPosterDakwah' => $dataPosterDakwah,
        ]);
    }
}
