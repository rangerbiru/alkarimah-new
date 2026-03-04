<?php

namespace App\Http\Requests;

use App\Enums\BillPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillGenerateRequest extends FormRequest
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
            'id_year' => 'required',
            'id_bill' => 'required',
            'method' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'id_year' => __('label.year'),
            'id_bill' => __('label.bill'),
            'method' => __('label.method'),
        ];
    }
}
