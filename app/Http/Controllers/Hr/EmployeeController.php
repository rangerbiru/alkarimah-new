<?php

namespace App\Http\Controllers\Hr;

use App\Constants\Modules;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\Module;
use App\Models\ModuleRights;
use App\Models\Parents;
use App\Models\Position;
use App\Models\Scopes\BranchScope;
use App\Models\Student;
use App\Services\AccountLinkException;
use App\Services\AccountLinker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeController extends Controller
{
    private $title = [
        'employee' => 'label.employee',
        'rights' => 'label.access_rights'
    ];
    private $icon = 'bx bx-building';
    private $path = 'backend.hr.employee.';

    public function index()
    {
        $count = Employee::count();

        return view($this->path . 'index', [
            'title' => __($this->title['employee']),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function rights(Employee $employee)
    {
        $module = Module::select('id', 'name', 'description')->orderBy('id')->get()->keyBy('id');
        $module_rights = ModuleRights::select('id_module')->whereIdUser($employee->id_user)->pluck('id_module', 'id_module')->toArray();

        $educations = Common::option('education_level');
        $selected_educations = [];

        foreach (Modules::WithEducationLevels as $module_id) {
            $selected_educations[$module_id] = $employee->user ? $employee->user->educationLevelValues($module_id) : [];
        }

        return view($this->path . 'rights', [
            'title' => __($this->title['rights']),
            'icon' => $this->icon,
            'employee' => $employee,
            'module' => $module,
            'module_groups' => Modules::Groups,
            'module_rights' => $module_rights,
            'educations' => $educations,
            'selected_educations' => $selected_educations,
            'level_modules' => Modules::WithEducationLevels,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $employee = Employee::select('id', 'nip', 'nik', 'task_main', 'name', 'phone', 'status');
        $employee_count = $employee->count();

        if (empty($search))
            $employee_filter = $employee;
        else {
            $employee_filter = $employee->where(function ($query) use ($search) {
                $query->where('nip', 'like', '%' . $search . '%')
                    ->orWhere('nik', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . Common::phoneFormat($search) . '%');
            });
        }

        $employee_count_filter = $employee_filter->count();
        $employee_data = $employee_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $employee_arr = [];

        foreach ($employee_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;
            $push['status_badge'] = $b->status_badge;

            array_push($employee_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $employee_count,
            'recordsFiltered' => $employee_count_filter,
            'data' => $employee_arr
        ];

        return response()->json($response);
    }

    public function create()
    {
        $genders = Common::option('gender');
        $marital_statuses = Common::option('marital_status');
        $employments = Common::option('employment_status');
        $yesno = Common::option('yesno');
        $positions = Position::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'create', $this->alsoParentData(null) + [
            'title' => __($this->title['employee']),
            'icon' => $this->icon,
            'genders' => $genders,
            'marital_statuses' => $marital_statuses,
            'employments' => $employments,
            'yesno' => $yesno,
            'positions' => $positions,
        ]);
    }

    public function store(EmployeeRequest $request)
    {
        $link = $this->linkSelection($request);

        try {
            DB::transaction(function () use ($request, $link) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'role' => UserRole::Pegawai,
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                ]);

                $request->merge([
                    'id_user' => $user->id
                ]);

                $employee = Employee::create($request->except(['also_parent', 'link_students']));

                $this->performLink($employee, $link);
            });
        } catch (AccountLinkException $e) {
            return Redirect::back()->withErrors($e->getMessage())->withInput($request->except(['password', 'password_confirm']));
        }

        return Redirect::route('hr.employee.index')->with('success', __('message.create_success', ['label' => __($this->title['employee'])]));
    }

    /**
     * Pilihan "Juga Orang Tua" dari santri yang dipilih (link_students[]). Hanya admin. Hasil:
     *  - ['parent' => id]      semua santri dari satu data orang tua (yang belum punya akun)
     *  - ['students' => [ids]] semua santri belum punya data orang tua: data orang tua baru dibuat dari data pegawai
     */
    private function linkSelection(Request $request): ?array
    {
        $encrypted = array_filter((array) $request->input('link_students', []));

        if (! $encrypted || $request->input('also_parent') != 1) {
            return null;
        }

        abort_unless(Auth::user()->hasRole(UserRole::Admin->value), 403);

        $ids = [];

        foreach ($encrypted as $value) {
            try {
                $ids[] = (int) \Illuminate\Support\Facades\Crypt::decrypt($value);
            } catch (\Throwable) {
                abort(422);
            }
        }

        $fail = fn (string $key) => throw \Illuminate\Validation\ValidationException::withMessages([
            'link_students' => __('message.'.$key),
        ]);

        $parentIds = collect(app(AccountLinker::class)->activeParentIds($ids))->unique()->values();

        if ($parentIds->isEmpty()) {
            $fail('account_link_parent_required');
        }

        if ($parentIds->count() === 1 && $parentIds->first() === null) {
            return ['students' => $ids];
        }

        if ($parentIds->contains(fn ($id) => $id === null)) {
            $fail('account_link_mixed_selection');
        }

        if ($parentIds->count() !== 1) {
            $fail('account_link_students_one_parent');
        }

        // Orang tua yang sudah punya akun digabung lewat form edit orang tua (ada preview akun lama)
        $parent = Parents::withoutGlobalScope(BranchScope::class)->find($parentIds->first());

        if ($parent && $parent->id_user && User::whereKey($parent->id_user)->exists()) {
            $fail('account_link_parent_has_account');
        }

        return ['parent' => (int) $parentIds->first()];
    }

    private function performLink(Employee $employee, ?array $link): void
    {
        if (! $link) {
            return;
        }

        $linker = app(AccountLinker::class);

        if (isset($link['parent'])) {
            $linker->link($employee->id, $link['parent']);
        } else {
            $linker->linkNew($employee->id, $link['students']);
        }
    }

    /**
     * Data untuk bagian "Juga Orang Tua" di form pegawai.
     */
    private function alsoParentData(?Employee $employee): array
    {
        $parent = $employee?->id_user
            ? Parents::withoutGlobalScope(BranchScope::class)->where('id_user', $employee->id_user)->first()
            : null;

        // Santri yang dipilih sebelum validasi gagal (old input), agar pilihan tidak hilang
        $oldIds = [];

        foreach ((array) old('link_students', []) as $value) {
            try {
                $oldIds[] = (int) \Illuminate\Support\Facades\Crypt::decrypt($value);
            } catch (\Throwable) {
            }
        }

        $selected = $oldIds
            ? Student::withoutGlobalScope(BranchScope::class)->whereIn('id', $oldIds)->get(['id', 'id_parent', 'name', 'nis'])
                ->map(fn ($st) => [
                    'id' => $st->encrypted_id, 'name' => $st->name, 'nis' => $st->nis,
                    'parent_key' => \Illuminate\Support\Facades\Crypt::encrypt($st->id_parent),
                ])->values()
            : collect();

        return [
            'selected_students' => $selected,
            'linked_parent' => $parent,
            'linked_children' => $parent
                ? Student::withoutGlobalScope(BranchScope::class)->with('class:id,name')->where('id_parent', $parent->id)->orderBy('name')->get(['id', 'id_class', 'name', 'nis'])
                : collect(),
        ];
    }

    public function storeRights(Request $request, Employee $employee)
    {
        $education_keys = implode(',', array_keys(Common::option('education_level')));

        $request->validate([
            'module' => 'nullable|array',
            'module.*' => 'integer|exists:module,id',
            'level_education' => 'nullable|array',
            'level_education.*' => 'array',
            'level_education.*.*' => 'in:' . $education_keys,
        ], [], [
            'module' => __('label.module'),
            'level_education' => __('label.level_education'),
            'level_education.*' => __('label.level_education'),
            'level_education.*.*' => __('label.level_education'),
        ]);

        $module = array_map('intval', $request->input('module', []));
        $levels = $request->input('level_education', []);

        // Modul berjenjang (Kepala Sekolah, Kasir Unit) wajib punya minimal satu jenjang
        foreach (Modules::WithEducationLevels as $module_id) {
            if (in_array($module_id, $module, true) && empty($levels[$module_id])) {
                $module_name = Module::whereKey($module_id)->value('name');

                return Redirect::back()->withInput()->withErrors([
                    'level_education.' . $module_id => __('message.module_requires_level', ['module' => $module_name]),
                ]);
            }
        }

        $module_rights = ModuleRights::select('id_module')->whereIdUser($employee->id_user)->pluck('id_module')->map(fn ($m) => (int) $m)->all();
        $rights = array_diff($module, $module_rights);
        $delete = array_diff($module_rights, $module);

        DB::transaction(function () use ($rights, $delete, $employee, $module, $levels) {
            foreach ($rights as $r)
                ModuleRights::create(['id_module' => $r, 'id_user' => $employee->id_user]);

            foreach ($delete as $d)
                ModuleRights::whereIdModule($d)->whereIdUser($employee->id_user)->delete();

            foreach (Modules::WithEducationLevels as $module_id) {
                $employee->user->syncEducationLevels(
                    in_array($module_id, $module, true) ? array_values($levels[$module_id] ?? []) : [],
                    $module_id
                );
            }
        });

        return Redirect::route('hr.employee.index')->with('success', __('message.update_success', ['label' => __($this->title['rights'])]));
    }

    public function edit(Employee $employee)
    {
        $genders = Common::option('gender');
        $marital_statuses = Common::option('marital_status');
        $employments = Common::option('employment_status');
        $yesno = Common::option('yesno');
        $positions = Position::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'edit', $this->alsoParentData($employee) + [
            'title' => __($this->title['employee']),
            'icon' => $this->icon,
            'employee' => $employee,
            'genders' => $genders,
            'marital_statuses' => $marital_statuses,
            'employments' => $employments,
            'yesno' => $yesno,
            'positions' => $positions,
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $link = $this->linkSelection($request);

        try {
            DB::transaction(function () use ($request, $employee, $link) {
                $employee->update($request->except(['also_parent', 'link_students']));

                $update_user = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'gender' => $request->gender
                ];

                if (!empty($request->password))
                    $update_user['password'] = $request->password;

                $employee->user->update($update_user);

                // Orang tua yang terhubung ke akun ini memakai nomor HP akun yang sama
                Parents::withoutGlobalScope(BranchScope::class)
                    ->where('id_user', $employee->id_user)
                    ->update(['phone' => $employee->user->phone]);

                $this->performLink($employee, $link);
            });
        } catch (AccountLinkException $e) {
            return Redirect::back()->withErrors($e->getMessage())->withInput($request->except(['password', 'password_confirm']));
        }

        return Redirect::route('hr.employee.index')->with('success', __('message.update_success', ['label' => __($this->title['employee'])]));
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['employee'])])
        ];

        return response()->json($response);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet()->toArray();

        $headerRow = 9;
        $header = array_map(fn($h) => strtolower(trim($h)), $sheet[$headerRow]);

        $dataRows = array_slice($sheet, $headerRow + 1);

        $success = 0;
        $errors = [];

        foreach ($dataRows as $index => $row) {

            $row = array_map(fn($r) => trim($r), $row);
            $data = array_combine($header, $row);

            $validator = Validator::make($data, [
                'nip' => 'required',
                'nama_lengkap' => 'required',
                'jenis_kelamin' => 'required',
                'telepon' => 'required',
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $index + $headerRow + 2,
                    'name' => $data['nama_lengkap'] ?? '-',
                    'error' => $validator->errors()->first()
                ];
                continue;
            }

            $data = array_map(fn($v) => $v === '' ? null : $v, $data);

            try {

                DB::beginTransaction();

                $existingUser = User::where('email', $data['email'])->first();

                if ($existingUser) {
                    $errors[] = [
                        'row' => $index + $headerRow + 2,
                        'name' => $data['nama_lengkap'],
                        'error' => 'Email sudah terdaftar'
                    ];
                    DB::rollBack();
                    continue;
                }

                $existingEmployee = Employee::where('nip', $data['nip'])
                    ->when(!empty($data['nik']), function ($q) use ($data) {
                        $q->orWhere('nik', $data['nik']);
                    })
                    ->first();

                if ($existingEmployee) {
                    $errors[] = [
                        'row' => $index + $headerRow + 2,
                        'name' => $data['nama_lengkap'],
                        'error' => 'NIP / NIK sudah terdaftar'
                    ];
                    DB::rollBack();
                    continue;
                }

                $user = User::create([
                    'name' => $data['nama_lengkap'],
                    'email' => $data['email'],
                    'phone' => $data['telepon'],
                    'password' => bcrypt($data['password'] ?? '12345678'), // default password
                    'role' => UserRole::Pegawai,
                    'gender' => $data['jenis_kelamin'],
                ]);

                Employee::create([
                    'id_user' => $user->id,
                    'nip' => $data['nip'],
                    'nik' => $data['nik'],
                    'name' => $data['nama_lengkap'],
                    'gender' => $data['jenis_kelamin'],
                    'phone' => $data['telepon'],
                    'email' => $data['email'],
                    'address' => $data['alamat'],
                    'education' => $data['pendidikan'],
                    'marital_status' => $data['status_pernikahan'] ?? 0,
                    'status_employment' => $data['status_kepegawaian'] ?? 1,
                    'status_teacher' => $data['status_pengajar'] ?? 0,
                    'status' => 1,
                    'branch_id' => Auth::user()->branch_id
                ]);

                DB::commit();
                $success++;
            } catch (\Exception $e) {

                DB::rollBack();

                $errors[] = [
                    'row' => $index + $headerRow + 2,
                    'name' => $data['nama_lengkap'] ?? '-',
                    'error' => $e->getMessage()
                ];
            }
        }

        // dd($errors);

        if ($success > 0 && count($errors) == 0) {

            return back()->with('success', "$success data berhasil diimport");
        } elseif ($success > 0 && count($errors) > 0) {

            return back()->with([
                'warning' => "$success data berhasil diimport, tetapi ada error",
                'errors_import' => $errors
            ]);
        } else {

            return back()->with([
                'error' => "Import gagal",
                'errors_import' => $errors
            ]);
        }
    }
}
