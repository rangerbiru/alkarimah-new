<?php

namespace App\Http\Requests;

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $user = $this->route('user');
        $isUpdate = $this->method() == 'PUT' && $user instanceof User;

        $rules = [
            'name' => 'required|max:100',
            'phone' => [
                'required',
                'max:20',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'max:200',
                'email',
                Rule::unique('users', 'email'),
            ],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'role' => ['required', Rule::in(UserController::MANAGED_ROLES)],
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
        ];

        if ($isUpdate) {
            // Role tidak pernah diubah lewat form ini
            unset($rules['role']);

            // Nomor HP lama bisa saja dipakai bersama akun lain; hanya periksa bila diganti
            $rules['phone'] = $this->phone === $user->phone
                ? 'required|max:20'
                : ['required', 'max:20', Rule::unique('users', 'phone')->whereNull('deleted_at')->ignore($user->id)];

            $rules['email'] = [
                'required',
                'max:200',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
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
            'name' => __('label.name'),
            'phone' => __('label.phone_number'),
            'email' => __('label.email'),
            'address' => __('label.address'),
            'gender' => __('label.gender'),
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
