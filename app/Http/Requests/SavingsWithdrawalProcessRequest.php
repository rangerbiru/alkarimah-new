<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavingsWithdrawalProcessRequest extends FormRequest
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
        return [
            'id_parent' => 'required',
            'dates' => 'required',
            'total' => [
                'required', 'integer',
                function ($attribute, $value, $fail) {
                    if ($value < 1)
                        return $fail(__('string.withdrawal_more_then_zero'));
                }
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'id_parent' => __('label.penanggung_jawab_tabungan'),
            'dates' => __('label.withdrawal_date'),
            'total' => __('label.total'),
        ];
    }
}
