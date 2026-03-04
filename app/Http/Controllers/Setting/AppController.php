<?php

namespace App\Http\Controllers\Setting;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Models\Setting;

class AppController extends Controller
{
    private $title = 'label.setting';
    private $icon = 'bx bx-cog';
    private $path = 'backend.setting.';

    public function index()
    {
        $setting = Setting::first();
        $on_off = Common::option('on_off');

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'setting' => $setting,
            'on_off' => $on_off,
        ]);
    }

    public function update(SettingRequest $request, Setting $setting)
    {
        $setting->update($request->all());

        return redirect()->route('setting.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }
}
