<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsramaRequest;
use App\Models\Asrama;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AsramaController extends Controller
{
    private $title = 'label.asrama';
    private $icon = 'bx bx bx-building-house';
    private $path = 'backend.academic.asrama.';

    public function index()
    {
        $count = Asrama::count();

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

        $asrama = Asrama::select('id', 'name', 'name_musrif', 'description');
        $asrama_count = $asrama->count();

        if (empty($search))
            $asrama_filter = $asrama;
        else {
            $asrama_filter = $asrama->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('name_musrif', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $asrama_count_filter = $asrama_filter->count();
        $asrama_data = $asrama_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $asrama_arr = [];

        foreach ($asrama_data as $d) {
            $push = $d->toArray();
            $push['encrypted_id'] = $d->encrypted_id;

            array_push($asrama_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $asrama_count,
            'recordsFiltered' => $asrama_count_filter,
            'data' => $asrama_arr
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
        ]);
    }

    public function store(AsramaRequest $request)
    {
        Asrama::create($request->all());

        return Redirect::route('academic.asrama.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Asrama $asrama)
    {
        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'asrama' => $asrama
        ]);
    }

    public function update(AsramaRequest $request, Asrama $asrama)
    {
        $asrama->update($request->all());

        return Redirect::route('academic.asrama.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Asrama $asrama)
    {
        $asrama->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }
}
