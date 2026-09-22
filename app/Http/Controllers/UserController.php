<?php

namespace App\Http\Controllers;

use App\Constants\UserMenu;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Http\Requests\UserPasswordRequest;
use App\Http\Requests\UserProfileRequest;
use App\Http\Requests\UserRequest;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserRights;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    private $title = 'label.user';

    private $icon = 'bx bx-user';

    private $path = 'backend.user.';

    public function index($role)
    {
        $count = User::whereRole($role)
            ->whereBranchId(Auth::user()->branch_id)
            ->count();

        return view($this->path.'index', [
            'title' => __($this->title).' - '.__('label.'.str_replace('-', '_', $role)),
            'icon' => $this->icon,
            'role' => $role,
            'count' => $count,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $user = User::select('id', 'name', 'phone', 'email', 'gender')
            ->whereBranchId(Auth::user()->branch_id)
            ->whereRole($request->role);

        $user_count = $user->count();

        if (empty($search)) {
            $user_filter = $user;
        } else {
            $user_filter = $user->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $user_count_filter = $user_filter->count();
        $is_kepala_sekolah = $request->role == UserRole::KepalaSekolah->value;

        $user_data = $user_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->when($is_kepala_sekolah, fn ($query) => $query->with('educationLevels'))
            ->get();

        $user_arr = [];

        foreach ($user_data as $u) {
            $push = $u->toArray();
            $push['encrypted_id'] = $u->encrypted_id;
            $push['gender'] = $u->gender_name;

            if ($is_kepala_sekolah) {
                $push['education_levels'] = array_map('strtoupper', $u->educationLevelValues());
            }

            array_push($user_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $user_count,
            'recordsFiltered' => $user_count_filter,
            'data' => $user_arr,
        ];

        return response()->json($response);
    }

    public function create($role)
    {
        if ($role == UserRole::KepalaSekolah->value) {
            return $this->createKepalaSekolah();
        }

        $genders = Common::option('gender');

        return view($this->path.'create', [
            'title' => __($this->title).' - '.__('label.'.str_replace('-', '_', $role)),
            'icon' => $this->icon,
            'role' => $role,
            'genders' => $genders,
        ]);
    }

    private function createKepalaSekolah()
    {
        $role = UserRole::KepalaSekolah->value;

        $employees = Employee::select('id', 'nip', 'name')
            ->whereStatus(1)
            ->whereHas('user', function ($query) {
                $query->whereRole(UserRole::Pegawai)
                    ->whereBranchId(Auth::user()->branch_id);
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($e) => [$e->id => $e->nip.' - '.$e->name]);

        $educations = Common::option('education_level');

        return view($this->path.'create-kepala-sekolah', [
            'title' => __($this->title).' - '.__('label.kepala_sekolah'),
            'icon' => $this->icon,
            'role' => $role,
            'employees' => $employees,
            'educations' => $educations,
        ]);
    }

    public function storeKepalaSekolah(Request $request)
    {
        $request->validate([
            'employee' => 'required|exists:employee,id',
            'level_education' => 'required|array|min:1',
            'level_education.*' => 'in:'.implode(',', array_keys(Common::option('education_level'))),
        ], [], [
            'employee' => __('label.employee'),
            'level_education' => __('label.level_education'),
            'level_education.*' => __('label.level_education'),
        ]);

        $employee = Employee::with('user')->findOrFail($request->employee);

        if (! $employee->user || $employee->user->role != UserRole::Pegawai || $employee->user->branch_id != Auth::user()->branch_id) {
            return Redirect::back()->with('error', __('string.kepala_sekolah_employee_invalid'));
        }

        DB::transaction(function () use ($request, $employee) {
            $employee->user->update(['role' => UserRole::KepalaSekolah]);
            $employee->user->syncEducationLevels($request->level_education);
        });

        return Redirect::route('user.index', UserRole::KepalaSekolah->value)->with('success', __('message.create_success', ['label' => __('label.kepala_sekolah')]));
    }

    public function editKepalaSekolah(User $user)
    {
        if ($user->role != UserRole::KepalaSekolah || $user->branch_id != Auth::user()->branch_id) {
            abort(404);
        }

        $role = UserRole::KepalaSekolah->value;
        $educations = Common::option('education_level');

        return view($this->path.'edit-kepala-sekolah', [
            'title' => __($this->title).' - '.__('label.kepala_sekolah'),
            'icon' => $this->icon,
            'user' => $user,
            'role' => $role,
            'educations' => $educations,
            'selected_educations' => $user->educationLevelValues(),
        ]);
    }

    public function updateKepalaSekolah(Request $request, User $user)
    {
        if ($user->role != UserRole::KepalaSekolah || $user->branch_id != Auth::user()->branch_id) {
            abort(404);
        }

        $request->validate([
            'level_education' => 'required|array|min:1',
            'level_education.*' => 'in:'.implode(',', array_keys(Common::option('education_level'))),
        ], [], [
            'level_education' => __('label.level_education'),
            'level_education.*' => __('label.level_education'),
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->syncEducationLevels($request->level_education);
        });

        return Redirect::route('user.index', UserRole::KepalaSekolah->value)->with('success', __('message.update_success', ['label' => __('label.kepala_sekolah')]));
    }

    public function store(UserRequest $request)
    {
        DB::transaction(function () use ($request) {
            $user = User::create($request->all());

            switch ($user->role->value) {
                case UserRole::Bendahara->value:
                    $menu_user = UserMenu::Bendahara;
                    break;

                case UserRole::PenanggungJawabTabungan->value:
                    $menu_user = UserMenu::PenanggungJawab;
                    break;

                case UserRole::WaliKelas->value:
                    $menu_user = UserMenu::WaliKelas;
                    break;

                case UserRole::Kasir->value:
                    $menu_user = UserMenu::Kasir;
                    break;

                case UserRole::KasirTabungan->value:
                    $menu_user = UserMenu::KasirTabungan;
                    break;
            }

            $menu = Menu::select('id', 'actions', 'is_parent')->whereIn('id', $menu_user)->orderBy('sort')->get();

            foreach ($menu as $m) {
                UserRights::create([
                    'id_user' => $user->id,
                    'id_menu' => $m->id,
                    'actions' => $m->actions,
                    'is_parent' => $m->is_parent,
                ]);
            }
        });

        return Redirect::route('user.index', $request->role)->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Request $request, User $user)
    {
        $role = $user->role->value;
        $genders = Common::option('gender');

        return view($this->path.'edit', [
            'title' => __($this->title).' - '.__('label.'.str_replace('-', '_', $role)),
            'icon' => $this->icon,
            'user' => $user,
            'role' => $role,
            'genders' => $genders,
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        if (empty($request->password)) {
            unset($request->password);
        }

        $user->update($request->all());

        return Redirect::route('user.index', $user->role->value)->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function destroy(User $user)
    {
        // Akun kepala sekolah adalah akun pegawai, jadi cukup dikembalikan ke role pegawai
        if ($user->role == UserRole::KepalaSekolah) {
            DB::transaction(function () use ($user) {
                $user->update(['role' => UserRole::Pegawai]);
                $user->syncEducationLevels([]);
            });

            return response()->json([
                'status' => true,
                'message' => __('string.kepala_sekolah_reverted'),
            ]);
        }

        $user->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)]),
        ];

        return response()->json($response);
    }

    public function editProfile()
    {
        // dd(Auth::user()->lastlogin_at);
        $genders = Common::option('gender');

        return view($this->path.'profile', [
            'title' => __('label.profile'),
            'icon' => $this->icon,
            'user' => Auth::user(),
            'genders' => $genders,
        ]);

    }

    public function updateProfile(UserProfileRequest $request)
    {
        $user = Auth::user();
        $user->update($request->all());

        return Redirect::route('user.profile')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function editPassword()
    {
        return view($this->path.'change-password', [
            'title' => __('label.change_password'),
            'icon' => $this->icon,
            'user' => Auth::user(),
        ]);
    }

    public function updatePassword(UserPasswordRequest $request)
    {
        $user = User::find(Auth::user()->id);
        $user->password = $request->new_password;
        $user->save();

        return Redirect::route('dashboard.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }
}
