<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HalaqahRequest extends FormRequest
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
            'name' => 'required|max:150',
            'name_pengampu' => 'required|max:150',
        ];
    }

    public function attributes()
    {
        return [
            'name' => __('label.name'),
            'name_pengampu' => __('label.pengampu_name'),
        ];
    }
}
