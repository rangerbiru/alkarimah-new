<?php

namespace App\Http\Controllers\Hr;

use App\Enums\UserRole;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\Module;
use App\Models\ModuleRights;
use App\Models\Position;
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
        $module = Module::select('id', 'name', 'description')->orderBy('name')->get();
        $module_rights = ModuleRights::select('id_module')->whereIdUser($employee->id_user)->pluck('id_module', 'id_module')->toArray();

        return view($this->path . 'rights', [
            'title' => __($this->title['rights']),
            'icon' => $this->icon,
            'employee' => $employee,
            'module' => $module,
            'module_rights' => $module_rights,
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
        ]);
    }

    public function store(EmployeeRequest $request)
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->phone,
                'email' => $request->email,
                'password' => $request->password,
                'role' => UserRole::Pegawai,
                'phone' => $request->phone,
                'gender' => $request->gender,
            ]);

            $request->merge([
                'id_user' => $user->id
            ]);

            Employee::create($request->all());
        });

        return Redirect::route('hr.employee.index')->with('success', __('message.create_success', ['label' => __($this->title['employee'])]));
    }

    public function storeRights(Request $request, Employee $employee)
    {
        $module = $request->module;
        $module_rights = ModuleRights::select('id_module')->whereIdUser($employee->id_user)->pluck('id_module', 'id_module')->toArray();
        $rights = [];
        $old = [];
        $delete = [];

        foreach ($module_rights as $m) {
            if (array_search($m, $module) === false)
                array_push($delete, $m);
            else
                array_push($old, $m);
        }

        foreach ($module as $m) {
            if (array_search($m, $old) === false)
                array_push($rights, $m);
        }

        DB::transaction(function () use ($rights, $delete, $employee) {
            foreach ($rights as $r)
                ModuleRights::create(['id_module' => $r, 'id_user' => $employee->id_user]);

            foreach ($delete as $d)
                ModuleRights::whereIdModule($d)->whereIdUser($employee->id_user)->delete();
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

        return view($this->path . 'edit', [
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
        DB::transaction(function () use ($request, $employee) {
            $employee->update($request->all());

            $update_user = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender
            ];

            if (!empty($request->password))
                $update_user['password'] = $request->password;

            $employee->user->update($update_user);
        });

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
