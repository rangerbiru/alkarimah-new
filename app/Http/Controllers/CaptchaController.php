<?php

namespace App\Http\Controllers;

class CaptchaController extends Controller
{
    public function refresh()
    {
        return response()->json(['captcha' => captcha_img()]);
    }
}
