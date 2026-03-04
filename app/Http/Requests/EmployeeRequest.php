<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
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
            'nik' => 'nullable|max:116|unique:employee',
            'name' => 'required|max:150',
            'phone' => 'required|max:20|unique:employee',
            'email' => 'required|max:200|email|unique:employee',
            'gender' => 'required',
            'marital_status' => 'required',
            'address' => 'required',
            'placement' => 'required',
            'status_employment' => 'required',
            'status_teacher' => 'required',
            'task_main' => 'required',
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
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
                Rule::unique('App\Models\Employee', 'email')->ignore($this->employee)
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
            'address' => __('label.address'),
            'placement' => __('label.placement'),
            'status_employment' => __('label.employment_status'),
            'status_teacher' => __('label.teacher_status'),
            'task_main' => __('label.main_task'),
            'password' => __('label.password'),
            'password_confirm' => __('label.confirm_password'),
        ];
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
