<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// A parent links an existing student to themself: NIS + birthdate prove they know the child
class StudentChildRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nis' => 'required|string|max:50',
            'birthdate' => 'required|date_format:d-m-Y',
        ];
    }

    public function attributes()
    {
        return [
            'nis' => __('label.nis'),
            'birthdate' => __('label.birthdate'),
        ];
    }
}
