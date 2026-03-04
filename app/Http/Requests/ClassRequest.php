<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassRequest extends FormRequest
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
            'id_wali_kelas' => 'required',
            'name' => 'required|max:50',
            'level_education' => 'required',
            'level_class' => 'required',
        ];
    }

    public function attributes(){
        return [
            'id_wali_kelas' => __('label.wali_kelas'),
            'name' => __('label.name'),
            'level_education' => __('label.level_education'),
            'level_class' => __('label.level_class'),
        ];
    }

}

