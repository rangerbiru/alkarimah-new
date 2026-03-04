<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class SavingsWithdrawalRequest extends FormRequest
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
            'id_student' => 'required',
            'dates' => 'required',
            'total' => [
                'required','integer',
                function ($attribute, $value, $fail) {
                    $setting = Setting::select('savings_withdrawal_limit', 'savings_withdrawal_limit_max')->first();

                    if ($setting->savings_withdrawal_limit) {
                        if ($value > $setting->savings_withdrawal_limit_max)
                            return $fail(__('string.withdrawal_limit_reached', ['limit' => number_format($setting->savings_withdrawal_limit_max, 0, '', '.')]));
                    }

                    $student = Student::select('balance_savings')->whereId(request()->id_student)->first();
                    $student_balance = (int) @$student->balance_savings;

                    if ($value > $student_balance)
                        return $fail(__('string.savings_balance_is_insufficient'));
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
            'id_student' => __('label.student'),
            'dates' => __('label.request_date'),
            'total' => __('label.total'),
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
            'total' => str_replace('.', '', $this->total)
        ]);
    }
}
