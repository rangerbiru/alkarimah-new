<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
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
        return [
            'savings_withdrawal_limit' => 'required',
        ];
    }

    public function attributes(){
        return [
            'savings_withdrawal_limit' => __('label.savings_withdrawal_limit'),
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
            'savings_withdrawal_limit_max' => str_replace('.', '', $this->savings_withdrawal_limit_max)
        ]);
    }
}
