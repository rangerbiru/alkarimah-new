<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentExculRequest extends FormRequest
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
            'exculs' => 'required',
            'student' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'exculs' => __('label.excul'),
            'student' => __('label.student'),
        ];
    }
}
