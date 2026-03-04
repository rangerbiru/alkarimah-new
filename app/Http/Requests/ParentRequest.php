<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class ParentRequest extends FormRequest
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
            'name' => 'required|max:150',
            'phone' => 'nullable|unique:parent',
            'gender' => 'required',
            'password' => [
                'nullable', 'min:8',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && empty(request()->phone))
                        return $fail(__('string.phone_required_if_you_want_to_create_account'));
                }
            ],
            'password_confirm' => 'nullable|same:password',
            'income' => 'nullable|numeric'
        ];

        if ($this->method() == 'PUT') {
            $rules['phone'] = [
                'nullable', 'max:20',
                Rule::unique('App\Models\Parents', 'phone')->ignore($this->parent)
            ];

            $rules['password'] = 'nullable|min:8';
            $rules['password_confirm'] = 'nullable|same:password';
        }

        return $rules;

    }

    public function attributes() {
        return [
            'name' => __('label.name'),
            'phone' => __('label.phone_number'),
            'password' => __('label.password'),
            'password_confirm' => __('label.confirm_password')
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'phone' => str_replace('-', '', $this->phone),
            'income' => str_replace('.', '', $this->income),
        ]);
    }
}
