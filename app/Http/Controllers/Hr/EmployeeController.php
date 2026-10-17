<?php

namespace App\Http\Controllers\Hr;

use App\Constants\Modules;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Helpers\Phone;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\Module;
use App\Models\ModuleRights;
use App\Models\Parents;
use App\Models\Position;
use App\Models\Scopes\BranchScope;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountLinkException;
use App\Services\AccountLinker;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        return view($this->path . 'create', [
            'title' => __($this->title['employee']),
            'icon' => $this->icon,
            'genders' => $genders,
            'marital_statuses' => $marital_statuses,
            'employments' => $employments,
            'yesno' => $yesno,
            'positions' => $positions,
            'children' => $this->formChildren(null),
            'linked_children' => [],
        ]);
    }

    public function store(EmployeeRequest $request)
    {
        $owner = null;

        if ($request->boolean('is_parent')) {
            $owner = $this->childrenOwnerWithAccount($request, null);
            $this->assertNoSimilarParent($request, null, $owner?->id);
            $owner && $this->requireLinkConfirmation($request, $owner, null);
        }

        DB::transaction(function () use ($request, $owner) {
            $user = User::forceCreate([
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

            $employee = Employee::create($request->except(['is_parent', 'children', 'confirm_link_parent']));

            if ($request->boolean('is_parent')) {
                $parent = $owner
                    ? $this->linkOwnerParent($employee, $owner)
                    : $this->ensureParentRecord($user, $request);
                $this->linkChildren($parent, $request->input('children', []), $user->id);
            }
        });

        return Redirect::route('hr.employee.index')->with('success', __('message.create_success', ['label' => __($this->title['employee'])]));
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
        $parent = Parents::withoutGlobalScope(BranchScope::class)->whereIdUser($employee->id_user)->first();
        $genders = Common::option('gender');
        $marital_statuses = Common::option('marital_status');
        $employments = Common::option('employment_status');
        $yesno = Common::option('yesno');
        $positions = Position::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'edit', [
            'title' => __($this->title['employee']),
            'icon' => $this->icon,
            'employee' => $employee,
            'genders' => $genders,
            'marital_statuses' => $marital_statuses,
            'employments' => $employments,
            'yesno' => $yesno,
            'positions' => $positions,
            'is_parent' => (bool) $parent,
            'children' => $this->formChildren($parent),
            'linked_children' => $parent ? Student::whereIdParent($parent->id)->pluck('id')->all() : [],
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $owner = null;

        if ($request->boolean('is_parent')) {
            $owner = $this->childrenOwnerWithAccount($request, $employee->id_user);
            $this->assertNoSimilarParent($request, $employee->id_user, $owner?->id);
            $owner && $this->requireLinkConfirmation($request, $owner, $employee);
        }

        DB::transaction(function () use ($request, $employee, $owner) {
            $employee->update($request->except(['is_parent', 'children', 'confirm_link_parent']));

            $update_user = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender
            ];

            if (!empty($request->password))
                $update_user['password'] = $request->password;

            $employee->user->update($update_user);

            // The parent record of a dual-role user follows the login phone
            Parents::withoutGlobalScopes()->whereIdUser($employee->id_user)->where('phone', '!=', $request->phone)->get()
                ->each(fn ($parent) => $parent->update(['phone' => $request->phone]));

            // "No" never deletes an existing parent record: it may still have students attached
            if ($request->boolean('is_parent')) {
                $parent = $owner
                    ? $this->linkOwnerParent($employee, $owner)
                    : $this->ensureParentRecord($employee->user, $request);
                $this->linkChildren($parent, $request->input('children', []), $employee->id_user);
            }
        });

        return Redirect::route('hr.employee.index')->with('success', __('message.update_success', ['label' => __($this->title['employee'])]));
    }

    public function destroy(Employee $employee)
    {
        // Akun orang tua lama yang menjadi alias akun pegawai ini kembali normal
        DB::transaction(function () use ($employee) {
            $employee->delete();
            app(AccountLinker::class)->unlinkAliasesOf($employee->id_user, 'employee-deleted');
        });

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

                $user = User::forceCreate([
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

    // Makes the employee's user also a parent: one parent row with the same id_user. Existing rows
    // are left as they are (a soft-deleted one is restored) so a user never ends up with two
    // "Also a parent" must not create a second parent record for someone who already is one. If a parent
    // record with the same phone or name exists, the admin is sent to Parents > Edit > merge instead.
    // Nothing is saved. A user that already has a parent record (linked or not) is left alone
    private function assertNoSimilarParent(Request $request, $user_id = null, $except_parent_id = null): void
    {
        if ($user_id && Parents::withoutGlobalScope(BranchScope::class)->withTrashed()->whereIdUser($user_id)->exists()) {
            return;
        }

        $name = $this->normalizeName($request->name);
        $last_ten = Phone::lastTen($request->phone);

        $similar = Parents::select('id', 'id_user', 'name', 'phone')
            ->when($except_parent_id, fn ($query) => $query->where('id', '!=', $except_parent_id))
            ->where(function ($query) use ($name, $last_ten) {
                if ($last_ten) {
                    $query->orWhereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), 10) = ?", [$last_ten]);
                }

                if ($name !== '') {
                    $query->orWhereRaw('LOWER(name) LIKE ?', ['%'.$name.'%']);
                }
            })
            ->limit(20)
            ->get()
            ->filter(fn ($parent) => ($last_ten && Phone::lastTen($parent->phone) === $last_ten)
                || ($name !== '' && $this->normalizeName($parent->name) === $name))
            ->take(5);

        if ($similar->isNotEmpty()) {
            throw ValidationException::withMessages(['is_parent' => __('message.parent_similar_exists', [
                'list' => $similar->map(fn ($parent) => $parent->name.' ('.($parent->phone ?: '-').')')->implode(', '),
            ])]);
        }
    }

    // "Joko Warsito, S.Sos." and "joko  warsito" are the same person: drop the title after the comma
    private function normalizeName($name): string
    {
        $name = Str::lower(trim(explode(',', (string) $name)[0]));

        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name));
    }

    private function ensureParentRecord(User $user, Request $request): Parents
    {
        $parent = Parents::withoutGlobalScope(BranchScope::class)
            ->withTrashed()
            ->whereIdUser($user->id)
            ->first();

        if ($parent) {
            if ($parent->trashed()) {
                $parent->restore();
            }

            return $parent;
        }

        return Parents::create([
            'id_user' => $user->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'gender' => $request->gender,
        ]);
    }

    // Students for the "pick children" modal on the employee form
    public function datatableStudent(Request $request)
    {
        $search = $request->input('search')['value'] ?? '';
        $limit = $request->input('length');
        $start = $request->input('start');
        $user_id = $this->employeeUserId($request->employee);

        $student = Student::select('id', 'id_parent', 'id_class', 'nis', 'name')
            ->with([
                'parent' => fn ($query) => $query->withoutGlobalScope(BranchScope::class)->select('id', 'id_user', 'name'),
                'class' => fn ($query) => $query->select('id', 'name'),
            ]);

        $student_count = $student->count();

        if (! empty($search)) {
            $student->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nis', 'like', '%'.$search.'%')
                    ->orWhereHas('class', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('parent', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
            });
        }

        $student_count_filter = $student->count();
        $student_data = $student->limit($limit)->offset($start)->orderBy('name')->get();

        $student_arr = $student_data->map(fn ($s) => [
            'encrypted_id' => $s->encrypted_id,
            'nis' => $s->nis,
            'name' => $s->name,
            'class' => $s->class?->name ?? '-',
            'parent' => $s->parent?->name ?? '-',
            // The child's parent already has an account: picking it goes through the merge confirmation
            'has_account' => $s->parent && $s->parent->id_user && $s->parent->id_user != $user_id,
        ]);

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $student_count,
            'recordsFiltered' => $student_count_filter,
            'data' => $student_arr,
        ]);
    }

    // Picked children whose parent already has a login account (other than this employee's). One such
    // parent: the form merges that account into the employee account after an explicit confirmation
    // (AccountLinker, the old parent account becomes an alias). Two different ones: refused
    private function childrenOwnerWithAccount(Request $request, $user_id): ?Parents
    {
        try {
            $ids = collect($request->input('children', []))->map(fn ($id) => Crypt::decrypt((string) $id))->unique()->values();
        } catch (DecryptException $e) {
            throw ValidationException::withMessages(['children' => __('message.children_invalid')]);
        }

        if ($ids->isEmpty()) {
            return null;
        }

        $parent_ids = Student::whereIn('id', $ids)->pluck('id_parent')->filter()->unique();

        $owners = Parents::withoutGlobalScope(BranchScope::class)
            ->withTrashed()
            ->whereIn('id', $parent_ids)
            ->whereNotNull('id_user')
            ->when($user_id, fn ($query) => $query->where('id_user', '!=', $user_id))
            ->get()
            ->filter(fn ($parent) => User::withoutGlobalScopes()->whereKey($parent->id_user)->exists())
            ->values();

        if ($owners->count() > 1) {
            throw ValidationException::withMessages(['children' => __('message.children_multiple_accounted_parents', [
                'parents' => $owners->pluck('name')->implode(' & '),
            ])]);
        }

        return $owners->first();
    }

    // Nothing is saved until the admin ticks the confirmation for this very parent
    private function requireLinkConfirmation(Request $request, Parents $owner, ?Employee $employee): void
    {
        try {
            $confirmed = $request->filled('confirm_link_parent')
                && (int) Crypt::decrypt((string) $request->input('confirm_link_parent')) === $owner->id;
        } catch (DecryptException $e) {
            $confirmed = false;
        }

        if ($confirmed) {
            return;
        }

        $preview = app(AccountLinker::class)->preview($employee?->id, $owner->id);

        if (! $preview['can_link']) {
            throw ValidationException::withMessages(['children' => $preview['blockers'][0]['message']]);
        }

        // Passwords are not sent back to the browser: they are typed again on the confirmed submit
        throw new HttpResponseException(
            Redirect::back()
                ->withInput($request->except(['password', 'password_confirm', 'confirm_link_parent']))
                ->with('link_preview', $preview + ['token' => $owner->encrypted_id])
        );
    }

    // Merges the picked children's parent account into this employee account and returns the parent row
    private function linkOwnerParent(Employee $employee, Parents $owner): Parents
    {
        try {
            app(AccountLinker::class)->link($employee->id, $owner->id);
        } catch (AccountLinkException $e) {
            throw ValidationException::withMessages(['children' => $e->getMessage()]);
        }

        return Parents::withoutGlobalScope(BranchScope::class)->findOrFail($owner->id);
    }

    // Children picked on the form become this parent's students. student.id_parent is NOT NULL, so
    // the form only adds: a child is moved to another parent from the student page. A child whose
    // current parent logs in with an account of its own is refused
    private function linkChildren(Parents $parent, array $encrypted_ids, $user_id): void
    {
        try {
            $ids = collect($encrypted_ids)->map(fn ($id) => Crypt::decrypt((string) $id))->unique()->values();
        } catch (DecryptException $e) {
            throw ValidationException::withMessages(['children' => __('message.children_invalid')]);
        }

        if ($ids->isEmpty()) {
            return;
        }

        $students = Student::whereIn('id', $ids)->lockForUpdate()->get();

        if ($students->count() !== $ids->count()) {
            throw ValidationException::withMessages(['children' => __('message.children_invalid')]);
        }

        $current_parents = Parents::withoutGlobalScope(BranchScope::class)
            ->withTrashed()
            ->whereIn('id', $students->pluck('id_parent'))
            ->get()
            ->keyBy('id');

        foreach ($students as $student) {
            if ($student->id_parent == $parent->id) {
                continue;
            }

            $current = $current_parents[$student->id_parent] ?? null;

            if ($current && $current->id_user && $current->id_user != $user_id) {
                throw ValidationException::withMessages(['children' => __('message.child_has_other_parent_admin', [
                    'name' => $student->name,
                    'parent' => $current->name,
                ])]);
            }

            $student->update(['id_parent' => $parent->id]);
        }
    }

    // Children shown on the form: the ones submitted before a failed validation, else the linked ones
    private function formChildren(?Parents $parent)
    {
        if (old('children')) {
            try {
                $ids = collect(old('children'))->map(fn ($id) => Crypt::decrypt((string) $id));
            } catch (DecryptException $e) {
                $ids = collect();
            }

            return Student::select('id', 'nis', 'name')->whereIn('id', $ids)->orderBy('name')->get();
        }

        return $parent
            ? Student::select('id', 'nis', 'name')->whereIdParent($parent->id)->orderBy('name')->get()
            : collect();
    }

    private function employeeUserId($encrypted_employee)
    {
        if (empty($encrypted_employee)) {
            return null;
        }

        try {
            return Employee::whereId(Crypt::decrypt((string) $encrypted_employee))->value('id_user');
        } catch (DecryptException $e) {
            return null;
        }
    }
}
