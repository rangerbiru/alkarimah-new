<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class BillDiscountRequest extends FormRequest
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
            'student' => [
                'required',
                function ($attribute, $value, $fail) {
                    $st = explode(' - ', $value);
                    $student = Student::whereNis($st[0])->count();

                    if ($student == 0)
                        $fail(__('string.student_data_not_found'));
                }
            ],
            'id_year' => 'required',
            'id_bill' => 'required',
            'nominal' => 'required|integer',
        ];
    }

    public function attributes()
    {
        return [
            'student' => __('label.student'),
            'id_year' => __('label.school_year'),
            'id_bill' => __('label.bill'),
            'nominal' => __('label.nominal'),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $applies = [];

        foreach ($this->applies_to as $a)
            $applies[$a] = 0;

        $this->merge([
            'nominal' => str_replace('.', '', $this->nominal),
            'applies_to' => $applies
        ]);
    }
}
