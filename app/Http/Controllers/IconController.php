<?php

namespace App\Http\Controllers;

use App\Models\Icon;
use Illuminate\Http\Request;

class IconController extends Controller
{
    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $type = Icon::select('id', 'class');
        $type_count = $type->count();

        if (empty($search))
            $type_filter = $type;
        else {
            $type_filter = $type->where(function ($query) use ($search) {
                $query->where('class', 'like', '%' . $search . '%');
            });
        }

        $type_count_filter = $type_filter->count();
        $type_data = $type_filter->limit($limit)
            ->offset($start)
            ->orderBy('class')
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $type_count,
            'recordsFiltered' => $type_count_filter,
            'data' => $type_data
        ]);
    }
}
