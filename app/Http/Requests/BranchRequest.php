<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
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
            'name' => 'required|max:100',
            'phone' => 'required|max:20|unique:branch',
            'email' => 'required|max:200|email|unique:branch',
            'address' => 'required',
            'username' => 'required|max:255',
            'gender' => 'required',
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
        ];

        if ($this->method() == 'PUT') {
            $rules['phone'] = [
                'required', 'max:20',
                Rule::unique('App\Models\Branch', 'phone')->ignore($this->branch)
            ];

            $rules['email'] = [
                'required', 'max:200',
                Rule::unique('App\Models\Branch', 'email')->ignore($this->branch)
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
            'username' => __('label.user_name'),
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
