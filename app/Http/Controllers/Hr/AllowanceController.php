<?php

namespace App\Http\Controllers\Hr;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\AllowanceRequest;
use App\Models\Allowance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AllowanceController extends Controller
{
    private $title = 'label.allowance';
    private $icon = 'bx bx bx-credit-card-alt';
    private $path = 'backend.hr.allowance.';

    public function index()
    {
        $count = Allowance::count();

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

        $allowance = Allowance::select('id', 'name', 'category');
        $allowance_count = $allowance->count();

        if (empty($search))
            $allowance_filter = $allowance;
        else {
            $allowance_filter = $allowance->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%');
            });
        }

        $allowance_count_filter = $allowance_filter->count();
        $allowance_data = $allowance_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $allowance_arr = [];

        foreach ($allowance_data as $a) {
            $push = $a->toArray();
            $push['encrypted_id'] = $a->encrypted_id;
            $push['category_name'] = $a->category_name;

            array_push($allowance_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $allowance_count,
            'recordsFiltered' => $allowance_count_filter,
            'data' => $allowance_arr
        ]);
    }

    public function create()
    {
        $categories = Common::option('allowance_category');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'categories' => $categories
        ]);
    }

    public function store(AllowanceRequest $request)
    {
        Allowance::create($request->all());

        return Redirect::route('hr.allowance.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Allowance $allowance)
    {
        $categories = Common::option('allowance_category');

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'allowance' => $allowance,
            'categories' => $categories
        ]);
    }

    public function update(AllowanceRequest $request, Allowance $allowance)
    {
        $allowance->update($request->all());

        return Redirect::route('hr.allowance.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Allowance $allowance)
    {
        $allowance->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }
}
