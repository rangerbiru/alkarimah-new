<?php

namespace App\Http\Controllers\Hr;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\PositionRequest;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PositionController extends Controller
{
    private $title = 'label.position';
    private $icon = 'ti ti-briefcase';
    private $path = 'backend.hr.position.';

    public function index()
    {
        $count = Position::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $position = Position::select('id', 'name');
        $position_count = $position->count();

        if (empty($search))
            $position_filter = $position;
        else
            $position_filter = $position->where('name', 'like', '%' . $search . '%');

        $position_count_filter = $position_filter->count();
        $position_data = $position_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $position_arr = [];

        foreach ($position_data as $a) {
            $push = $a->toArray();
            $push['encrypted_id'] = $a->encrypted_id;

            array_push($position_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $position_count,
            'recordsFiltered' => $position_count_filter,
            'data' => $position_arr
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon
        ]);
    }

    public function store(PositionRequest $request)
    {
        Position::create($request->all());

        return Redirect::route('hr.position.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Position $position)
    {
        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'position' => $position
        ]);
    }

    public function update(PositionRequest $request, Position $position)
    {
        $position->update($request->all());

        return Redirect::route('hr.position.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Position $position)
    {
        $position->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }
}