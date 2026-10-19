<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    /**
     * Satu-satunya kolom employee yang boleh diisi dari form. id_user, branch_id, status, salary, dan
     * kolom lain TIDAK pernah diambil dari request.
     */
    public const FORM_FIELDS = [
        'nip', 'nik', 'name', 'gender', 'phone', 'email', 'marital_status', 'education', 'address',
        'id_position', 'placement', 'status_employment', 'status_teacher', 'task_main', 'task_additional',
    ];

    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = true;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'nip' => 'required|max:50|unique:employee',
            'nik' => 'nullable|max:16|unique:employee',
            'name' => 'required|max:150',
            'phone' => 'required|max:20|unique:employee',
            // The email is copied to the login account too, so it must be free in users as well
            'email' => 'required|max:200|email|unique:employee|unique:users,email',
            'gender' => ['required', Rule::in(['male', 'female'])],
            'marital_status' => ['required', Rule::in(['0', '1'])],
            'education' => 'nullable|string|max:100',
            'address' => 'required',
            'id_position' => ['nullable', 'integer', Rule::exists('position', 'id')->whereNull('deleted_at')->where('branch_id', Auth::user()?->branch_id)],
            'placement' => 'required',
            'status_employment' => ['required', Rule::enum(EmployeeStatus::class)],
            'status_teacher' => ['required', Rule::in(['0', '1'])],
            'task_main' => 'required',
            'task_additional' => 'nullable|string',
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
            'is_parent' => 'required|in:0,1',
            'children' => 'nullable|array|max:20',
            'children.*' => 'string',
        ];

        if ($this->method() == 'PUT') {
            $rules['nip'] = [
                'required', 'max:50',
                Rule::unique('App\Models\Employee', 'nip')->ignore($this->employee)
            ];

            $rules['nik'] = [
                'nullable', 'max:16',
                Rule::unique('App\Models\Employee', 'nik')->ignore($this->employee)
            ];

            $rules['phone'] = [
                'required', 'max:20',
                Rule::unique('App\Models\Employee', 'phone')->ignore($this->employee)
            ];

            $rules['email'] = [
                'required', 'max:200',
                Rule::unique('App\Models\Employee', 'email')->ignore($this->employee),
                Rule::unique('users', 'email')->ignore($this->employee->id_user)
            ];

            $rules['password'] = 'nullable|min:8';
            $rules['password_confirm'] = 'nullable|same:password';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'nip' => __('label.nip'),
            'nik' => __('label.nik'),
            'name' => __('label.name'),
            'phone' => __('label.phone_number'),
            'email' => __('label.email'),
            'gender' => __('label.gender'),
            'marital_status' => __('label.marital_status'),
            'education' => __('label.last_education'),
            'address' => __('label.address'),
            'id_position' => __('label.position'),
            'placement' => __('label.placement'),
            'status_employment' => __('label.employment_status'),
            'status_teacher' => __('label.teacher_status'),
            'task_main' => __('label.main_task'),
            'task_additional' => __('label.additional_task'),
            'password' => __('label.password'),
            'password_confirm' => __('label.confirm_password'),
            'is_parent' => __('label.is_also_parent'),
            'children' => __('label.children'),
        ];
    }

    /** Field employee dari form yang lolos validasi (tanpa kolom sensitif). */
    public function employeeData(): array
    {
        return $this->safe()->only(self::FORM_FIELDS);
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'phone' => str_replace('-', '', $this->phone)
        ]);
    }
}
