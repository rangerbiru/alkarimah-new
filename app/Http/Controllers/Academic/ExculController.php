<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExculGroupRequest;
use App\Http\Requests\ExculRequest;
use App\Models\Excul;
use App\Models\ExculGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ExculController extends Controller
{
    private $title = [
        'excul' => 'label.excul',
        'group' => 'label.group',
    ];

    private $icon = 'bx bx-universal-access';
    private $path = 'backend.academic.excul.';

    public function index()
    {
        $count = Excul::count();

        return view($this->path . 'index', [
            'title' => __($this->title['excul']),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function group(Excul $excul)
    {
        $count = ExculGroup::whereIdExcul($excul->id)->count();

        return view($this->path . 'group', [
            'title' => __($this->title['group']),
            'icon' => $this->icon,
            'count' => $count,
            'excul' => $excul,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $excul = Excul::select('id', 'name');
        $excul_count = $excul->count();

        if (empty($search))
            $excul_filter = $excul;
        else {
            $excul_filter = $excul->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $excul_count_filter = $excul_filter->count();
        $excul_data = $excul_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $excul_arr = [];

        foreach ($excul_data as $d) {
            $push = $d->toArray();
            $push['encrypted_id'] = $d->encrypted_id;

            array_push($excul_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $excul_count,
            'recordsFiltered' => $excul_count_filter,
            'data' => $excul_arr
        ]);
    }

    public function datatableGroup(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $excul = ExculGroup::select('id', 'name')->whereIdExcul($request->excul);
        $excul_count = $excul->count();

        if (empty($search))
            $excul_filter = $excul;
        else {
            $excul_filter = $excul->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $excul_count_filter = $excul_filter->count();
        $excul_data = $excul_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $excul_arr = [];

        foreach ($excul_data as $d) {
            $push = $d->toArray();
            $push['encrypted_id'] = $d->encrypted_id;

            array_push($excul_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $excul_count,
            'recordsFiltered' => $excul_count_filter,
            'data' => $excul_arr
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title['excul']),
            'icon' => $this->icon,
        ]);
    }

    public function createGroup(Excul $excul)
    {
        return view($this->path . 'create', [
            'title' => __($this->title['group']),
            'icon' => $this->icon,
            'excul' => $excul
        ]);
    }

    public function store(ExculRequest $request)
    {
        Excul::create($request->all());

        return Redirect::route('academic.excul.index')->with('success', __('message.create_success', ['label' => __($this->title['excul'])]));
    }

    public function storeGroup(ExculGroupRequest $request)
    {
        ExculGroup::create($request->all());

        return Redirect::route('academic.excul.group.index')->with('success', __('message.create_success', ['label' => __($this->title['grup'])]));
    }

    public function edit(Excul $excul)
    {
        return view($this->path . 'edit', [
            'title' => __($this->title['excul']),
            'icon' => $this->icon,
            'excul' => $excul
        ]);
    }

    public function editGroup(ExculGroup $group)
    {
        return view($this->path . 'edit', [
            'title' => __($this->title['group']),
            'icon' => $this->icon,
            'group' => $group
        ]);
    }

    public function update(ExculRequest $request, Excul $excul)
    {
        $excul->update($request->all());

        return Redirect::route('academic.excul.index')->with('success', __('message.update_success', ['label' => __($this->title['excul'])]));
    }

    public function updateGroup(ExculGroupRequest $request, ExculGroup $group)
    {
        $group->update($request->all());

        return Redirect::route('academic.excul.group.index')->with('success', __('message.update_success', ['label' => __($this->title['group'])]));
    }

    public function destroy(Excul $excul)
    {
        $excul->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['excul'])])
        ];

        return response()->json($response);
    }

    public function destroyGroup(ExculGroup $group)
    {
        $group->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['group'])])
        ];

        return response()->json($response);
    }
}
