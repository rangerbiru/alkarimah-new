<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\HalaqahRequest;
use App\Models\Halaqah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class HalaqahController extends Controller
{
    private $title = 'label.halaqah';
    private $icon = 'bx bx bx-building-house';
    private $path = 'backend.academic.halaqah.';

    public function index()
    {
        $count = Halaqah::count();

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

        $halaqah = Halaqah::select('id', 'name', 'name_pengampu', 'description');
        $halaqah_count = $halaqah->count();

        if (empty($search))
            $halaqah_filter = $halaqah;
        else {
            $halaqah_filter = $halaqah->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('name_pengampu', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $halaqah_count_filter = $halaqah_filter->count();
        $halaqah_data = $halaqah_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $halaqah_arr = [];

        foreach ($halaqah_data as $d) {
            $push = $d->toArray();
            $push['encrypted_id'] = $d->encrypted_id;

            array_push($halaqah_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $halaqah_count,
            'recordsFiltered' => $halaqah_count_filter,
            'data' => $halaqah_arr
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
        ]);
    }

    public function store(HalaqahRequest $request)
    {
        Halaqah::create($request->all());

        return Redirect::route('academic.halaqah.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Halaqah $halaqah)
    {
        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'halaqah' => $halaqah
        ]);
    }

    public function update(HalaqahRequest $request, Halaqah $halaqah)
    {
        $halaqah->update($request->all());

        return Redirect::route('academic.halaqah.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Halaqah $halaqah)
    {
        $halaqah->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }
}
