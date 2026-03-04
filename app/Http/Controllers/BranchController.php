<?php

namespace App\Http\Controllers;

use App\Constants\UserMenu;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserRights;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class BranchController extends Controller
{
    private $title = 'label.branch';
    private $icon = 'bx bx-building';
    private $path = 'backend.branch.';

    public function index()
    {
        $count = Branch::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $branch = Branch::select('id', 'name', 'phone', 'email', 'address');
        $branch_count = $branch->count();

        if (empty($search))
            $branch_filter = $branch;
        else {
            $branch_filter = $branch->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        $branch_count_filter = $branch_filter->count();
        $branch_data = $branch_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $branch_arr = [];

        foreach ($branch_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;

            array_push($branch_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $branch_count,
            'recordsFiltered' => $branch_count_filter,
            'data' => $branch_arr
        ];

        return response()->json($response);
    }

    public function create()
    {
        $genders = Common::option('gender');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'genders' => $genders
        ]);
    }

    public function store(BranchRequest $request)
    {
        DB::transaction(function() use($request) {
            $branch = Branch::create($request->all());

            $user = User::create([
                'name' => $request->username,
                'email' => $request->email,
                'password' => $request->password,
                'role' => UserRole::Admin,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'branch_id' => $branch->id
            ]);

            $menu = Menu::select('id', 'actions', 'is_parent')->whereIn('id', UserMenu::Admin)->orderBy('sort')->get();

            foreach ($menu as $m) {
                UserRights::create([
                    'id_user' => $user->id,
                    'id_menu' => $m->id,
                    'actions' => $m->actions,
                    'is_parent' => $m->is_parent,
                ]);
            }
        });

        return Redirect::route('branch.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Branch $branch)
    {
        $genders = Common::option('gender');

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'branch' => $branch,
            'genders' => $genders
        ]);
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        DB::transaction(function() use($request, $branch) {
            $branch->update($request->all());

            $update_user = [
                'name' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender
            ];

            if (!empty($request->password))
                $update_user['password'] = $request->password;

            $branch->user->update($update_user);
        });

        return Redirect::route('branch.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }
}
