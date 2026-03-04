<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;

class RegionController extends Controller
{
    public function option(Request $request)
    {
        $options = '<option value=""></option>';
        $region = Region::select('id', 'name')
            ->whereIdParent($request->id_parent)
            ->orderBy('name')
            ->get();

        foreach ($region as $r)
            $options .= '<option value="' . $r->id . '">' . $r->name . '</option>';

        return response()->json(['option' => $options]);
    }
}
