<?php

namespace App\Http\Requests;

use App\Enums\BillPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillSetupRequest extends FormRequest
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
            'name' => 'required|max:100',
            'id_year' => 'required',
            'id_type' => 'required',
            'nominal' => 'required|integer',
            'billing_date1' => [
                Rule::requiredIf(fn() => (request()->period == BillPeriod::OneTime->value) ? true : false)
            ],
            'billing_date2' => [
                Rule::requiredIf(fn() => (request()->period == BillPeriod::OneTime->value) ? false : true)
            ],
            'due_date1' => [
                Rule::requiredIf(fn() => (request()->period == BillPeriod::OneTime->value) ? true : false)
            ],
            'due_date2' => [
                Rule::requiredIf(fn() => (request()->period == BillPeriod::OneTime->value) ? false : true)
            ],
        ];
    }

    public function attributes()
    {
        return [
            'name' => __('label.name'),
            'id_year' => __('label.year'),
            'id_type' => __('label.type'),
            'nominal' => __('label.nominal'),
            'billing_date1' => __('label.billing_date'),
            'billing_date2' => __('label.billing_date'),
            'due_date1' => __('label.due_date'),
            'due_date2' => __('label.due_date'),
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
            'nominal' => str_replace('.', '', $this->nominal)
        ]);
    }
}
