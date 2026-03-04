<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Models\Parents;
use App\Http\Requests\ParentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;

class ParentController extends Controller
{
    private $title = 'label.parent';
    private $icon = 'bx bx-user';
    private $path = 'backend.academic.parent.';

    public function index(){
        $count = Parents::count();

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

        $parent = Parents::select('id', 'id_village', 'id_relation', 'name', 'phone', 'gender', 'address')
            ->with([
                'village' => function ($query) {
                    $query->select('id', 'id_parent', 'name')->with([
                        'parent' => function ($query) {
                            $query->select('id', 'id_parent', 'name')->with([
                                'parent' => function ($query) {
                                    $query->select('id', 'id_parent', 'name')->with([
                                        'parent' => function ($query) {
                                            $query->select('id', 'id_parent', 'name');
                                        }
                                    ]);
                                }
                            ]);
                        }
                    ]);
                },
                'relation' => fn($query) => $query->select('id', 'name')
            ])
            ->when(!empty($request->except), fn($query) => $query->where('id', '!=', $request->except));

        $parent_count = $parent->count();

        if (empty($search))
            $parent_filter = $parent;
        else {
            $parent_filter = $parent->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        $parent_count_filter = $parent_filter->count();
        $parent_data = $parent_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $parent_arr = [];

        foreach ($parent_data as $p) {
            $push = $p->toArray();
            $push['encrypted_id'] = $p->encrypted_id;
            $push['gender_name'] = $p->gender_name;
            $push['address_full'] = $p->address_full;

            array_push($parent_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $parent_count,
            'recordsFiltered' => $parent_count_filter,
            'data' => $parent_arr
        ];

        return response()->json($response);
    }

    public function create()
    {
        $genders = Common::option('gender');
        $provinces = Region::select('id', 'name')->province()->pluck('name', 'id');

        return view($this->path . 'create', [
            'title' =>  __($this->title),
            'icon' => $this->icon,
            'genders' => $genders,
            'provinces' => $provinces,
        ]);
    }

    public function store(ParentRequest $request)
    {
        $user_count = User::wherePhone($request->phone)->count();

        if (!empty($request->password) && $user_count > 0)
            return Redirect::route('academic.parent.create')->withErrors(__('string.account_with_this_phone_exists'))->withInput();

        DB::transaction(function () use ($request) {
            $merge = [];

            if (empty($request->income))
                $merge['income'] = null;

            if (!empty($request->id_relation))
                $merge['id_relation'] = Crypt::decrypt($request->id_relation);

            if (!empty($merge))
                $request->merge($merge);

            if (empty($request->password)) {
                $parent = Parents::create($request->all());
            } else {
                $user = User::create([
                    'name' => $request->name,
                    'email' => strtolower(explode(' ', $request->name)[0]) . '_' . date('YmdHis') . '@binabbas.org',
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                    'password' => $request->password,
                    'role' => UserRole::OrangTua,
                ]);

                $parentData = $request->all();
                $parentData['id_user'] = $user->id;

                $parent = Parents::create($parentData);

                $user->update([
                    'email' => strtolower(explode(' ', $request->name)[0]) . '_' . $user->id . '@binabbas.org'
                ]);
            }

            if (!empty($parent->id_relation)) {
                Parents::whereIdRelation($parent->id_relation)->where('id', '!=', $parent->id)->update(['id_relation' => null]);
                Parents::whereId($parent->id_relation)->update(['id_relation' => $parent->id]);
            }
        });

        return Redirect::route('academic.parent.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Parents $parent)
    {
        $parent->load('user');
        $genders = Common::option('gender');
        $provinces = Region::select('id', 'name')->province()->pluck('name', 'id');

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'parent' => $parent,
            'genders' => $genders,
            'provinces' => $provinces,
        ]);
    }

    public function update(ParentRequest $request, Parents $parent)
    {
        if (empty($parent->id_user)) {
            if (!empty($request->password)) {
                $user_count = User::wherePhone($request->phone)->count();

                if ($user_count > 0)
                    return Redirect::route('academic.parent.create')->withErrors(__('string.account_with_this_phone_exists'))->withInput();
            }
        } else {
            $user_count = User::wherePhone($request->phone)->where('id', '!=', $parent->id_user)->count();

            if ($user_count > 0)
                return Redirect::route('academic.parent.create')->withErrors(__('string.account_with_this_phone_exists'))->withInput();
        }

        DB::transaction(function () use ($request, $parent) {
            $relation_old = $parent->id_relation;
            $merge = [];

            if (empty($request->income))
                $merge['income'] = null;

            if (!empty($request->id_relation))
                $merge['id_relation'] = Crypt::decrypt($request->id_relation);

            if (!empty($merge))
                $request->merge($merge);

            if (empty($parent->id_user)) {
                if (!empty($request->password)) {
                    $user = User::create([
                        'name' => $request->name,
                        'email' => strtolower(explode(' ', $request->name)[0]) . '_' . $parent->id . '@binabbas.org',
                        'phone' => $request->phone,
                        'gender' => $request->gender,
                        'password' => $request->password,
                        'role' => UserRole::OrangTua,
                    ]);

                    $request->merge(['id_user' => $user->id]);

                    $user->update([
                        'email' => strtolower(explode(' ', $request->name)[0]) . '_' . $user->id . '@binabbas.org'
                    ]);
                }
            } else {
                $update_user = [
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'gender' => $request->gender
                ];

                if ($request->password != null)
                    $update_user['password'] = $request->password;

                $parent->user->update($update_user);
            }

            $parent->update($request->all());

            if ($relation_old != $parent->id_relation) {
                Parents::whereIdRelation($parent->id_relation)->where('id', '!=', $parent->id)->update(['id_relation' => null]);
                Parents::whereId($parent->id_relation)->update(['id_relation' => $parent->id]);
            }
        });

        return Redirect::route('academic.parent.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Parents $parent)
    {
        $parent->delete();
        return response()->json(['message' => __('message.delete_success', ['label' => __($this->title)])]);
    }
}
