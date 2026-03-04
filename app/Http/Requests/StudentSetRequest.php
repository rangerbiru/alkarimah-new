<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentSetRequest extends FormRequest
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
            'class' => 'nullable',
            'asrama' => 'nullable',
            'halaqah' => 'nullable',
            'student' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'class' => __('label.class'),
            'asrama' => __('label.asrama'),
            'halaqah' => __('label.halaqah'),
            'student' => __('label.student'),
        ];
    }
}
