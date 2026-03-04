<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    private $path = 'frontend.page.';

    public function privacyPolicy()
    {
        return view($this->path . 'privacy-policy');
    }
}
