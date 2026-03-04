<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class StudentParentRequest extends FormRequest
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
            'gender' => 'required',
            'religion' => 'required',
            'birthdate' => 'required',
            'birthplace' => 'required|max:100',
            'address' => 'required',
            'child' => 'required',
            'school_from' => 'required|max:100',
        ];

        $rules['nik'] = [
            'required',
            'max:16',
            Rule::unique('App\Models\Student', 'nik')->ignore($this->student)
        ];

        return $rules;
    }

    public function attributes() {
        return [
            'nik' => __('label.nik'),
            'name' => __('label.name'),
            'gender' => __('label.gender'),
            'religion' => __('label.religion'),
            'birthdate' => __('label.birthdate'),
            'birthplace' => __('label.birthplace'),
            'address' => __('label.address'),
            'child' => __('label.child_ke'),
            'school_from' => __('label.school_from'),
        ];
    }
}
