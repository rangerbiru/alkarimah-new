<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class YearRequest extends FormRequest
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
            'start_year' => 'required|unique:year',
            'end_year' => 'required|unique:year',
        ];

        if ($this->method() == 'PUT') {
            $rules['start_year'] = [
                'required',
                Rule::unique('App\Models\Year', 'start_year')->ignore($this->year)
            ];

            $rules['end_year'] = [
                'required',
                Rule::unique('App\Models\Year', 'end_year')->ignore($this->year)
            ];
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
            'start_year' => __('label.start_year'),
            'end_year' => __('label.end_year'),
        ];
    }
}
