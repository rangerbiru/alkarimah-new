<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentChangeRequest extends FormRequest
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
            'to_class' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'to_class' => __('label.target_class'),
        ];
    }
}
