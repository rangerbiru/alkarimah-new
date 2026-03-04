<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
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
            'nis' => 'required|max:50',
            'nik' => 'nullable|max:16|unique:student',
            'nisn' => 'nullable|max:20|unique:student',
            'nis_local' => 'nullable|max:50|unique:student',
            'name' => 'required|max:150',
            'gender' => 'required',
            'id_parent' => 'required',
            'religion' => 'required',
            'birthplace' => 'nullable|max:100',
            'card_number' => 'required|max:50',
            'id_asrama' => 'required',
            'id_halaqah' => 'required',
            'id_class' => 'required',
            'school_from' => 'nullable|max:100',
            'entry_date' => 'required',
            'spp' => 'nullable|numeric',
            'location' => 'nullable|max:200',
        ];

        if ($this->method() == 'PUT') {
            $rules['nis'] = [
                'required', 'max:50',
                Rule::unique('App\Models\Student', 'nis')->ignore($this->student)
            ];

            $rules['nik'] = [
                'nullable', 'max:16',
                Rule::unique('App\Models\Student', 'nik')->ignore($this->student)
            ];

            $rules['nisn'] = [
                'nullable', 'max:20',
                Rule::unique('App\Models\Student', 'nisn')->ignore($this->student)
            ];

            $rules['nis_local'] = [
                'nullable', 'max:20',
                Rule::unique('App\Models\Student', 'nis_local')->ignore($this->student)
            ];
        }

        return $rules;
    }

    public function attributes() {
        return [
            'nis' => __('label.nis'),
            'nis_local' => __('label.nis_local'),
            'nik' => __('label.nik'),
            'nisn' => __('label.nisn'),
            'name' => __('label.name'),
            'gender' => __('label.gender'),
            'id_parent' => __('label.parent'),
            'religion' => __('label.religion'),
            'birthplace' => __('label.birthplace'),
            'card_number' => __('label.student_card_number'),
            'id_asrama' => __('label.asrama'),
            'id_halaqah' => __('label.halaqah'),
            'id_class' => __('label.class'),
            'school_from' => __('label.school_from'),
            'entry_date' => __('label.entry_date'),
            'spp' => __('label.spp'),
            'location' => __('label.location'),
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'spp' => str_replace('.', '', $this->spp),
            'id_parent' => (empty($this->id_parent)) ? '' : Crypt::decrypt($this->id_parent)
        ]);
    }
}
