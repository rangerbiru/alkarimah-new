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
            // === TABLE: students (Data Inti) ===
            'name' => 'required|string|max:150',
            'gender' => 'required|in:male,female',
            'religion' => 'required|in:Islam,Kristen,Hindu,Budha',
            'birthdate' => 'required|date',
            'birthplace' => 'required|string|max:100',
            'address' => 'required|string',
            'child' => 'required|numeric|min:1',
            'school_from' => 'required|string|max:100',
            'card_number' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:200',
            'id_class' => 'nullable|exists:classes,id',

            // === TABLE: student_profiles ===
            'nickname' => 'nullable|string|max:100',
            'blood_type' => 'nullable',
            'weight' => 'nullable|numeric|min:0|max:999.99',
            'height' => 'nullable|numeric|min:0|max:999.99',
            'home_language' => 'nullable|string|max:100',
            'personality' => 'nullable|string|max:100',
            'medical_history' => 'nullable|string',
            'physical_disabilities' => 'nullable|string',
            'daily_habits' => 'nullable|string',
            'living_with_parents' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:50',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // === TABLE: student_parents (Data Ayah) ===
            'father_name' => 'nullable|string|max:50',
            'father_id_number' => 'nullable|max:16',
            'father_status' => 'nullable|in:Hidup,Meninggal',
            'father_education' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:50',
            'father_phone' => 'nullable|string|max:20',

            // === TABLE: student_parents (Data Ibu) ===
            'mother_name' => 'nullable|string|max:50',
            'mother_id_number' => 'nullable|max:16',
            'mother_status' => 'nullable|in:Hidup,Meninggal',
            'mother_education' => 'nullable|string|max:20',
            'mother_occupation' => 'nullable|string|max:50',
            'mother_phone' => 'nullable|string|max:20',

            // === TABLE: student_parents (Data Wali) ===
            'guardian_name' => 'nullable|string|max:50',
            'guardian_id_number' => 'nullable|max:16',
            'guardian_occupation' => 'nullable|string|max:50',
            'guardian_email' => 'nullable|email|max:100',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_income' => 'nullable|string|max:30',

            // === TABLE: student_parents (Data Keluarga) ===
            'family_card_number' => 'nullable|max:16',
            'family_income' => 'nullable|string|max:30',
            'child_order' => 'nullable|numeric|min:1',
            'siblings_count' => 'nullable|numeric|min:0',
            'step_siblings_count' => 'nullable|numeric|min:0',
            'adopted_siblings_count' => 'nullable|numeric|min:0',
            'family_members_count' => 'nullable|numeric|min:1',
            'orphan_status' => 'nullable|in:Bukan Yatim,Yatim Piatu,Yatim Ayah,Yatim Ibu',
            'guardian_notes' => 'nullable|string',
            'approval_status' => 'nullable|in:on,off',

            // === TABLE: student_addresses (Alamat Rumah) ===
            'home_address' => 'nullable|string',
            'home_district' => 'nullable|string|max:50',
            'home_regency' => 'nullable|string|max:50',
            'home_province' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:10',

            // === TABLE: student_addresses (Alamat Sekolah) ===
            'previous_school_address' => 'nullable|string',
            'previous_school_district' => 'nullable|string|max:50',
            'previous_school_regency' => 'nullable|string|max:50',
            'previous_school_province' => 'nullable|string|max:50',
            'distance_to_school' => 'nullable|string|max:50',

            // === TABLE: student_academics ===
            'previous_school_name' => 'nullable|string|max:100',
            'previous_school_npsn' => 'nullable|string|max:20',
            'previous_school_status' => 'nullable|string|max:20',
            'registration_number' => 'nullable|string|max:100',
            'session_id' => 'nullable|string|max:100',
            'entry_date' => 'nullable|date',
            'payment_status' => 'nullable|in:normal,pending,discount',
            'has_scholarship' => 'nullable|boolean',
            'scholarship_name' => 'nullable|string|max:100',
            'achievements' => 'nullable|string',
            'recommendation_status' => 'nullable|in:Recommended,Not Recommended',
            'graduation_status' => 'nullable|in:passed,failed,reserve,',
            'notes' => 'nullable|string',
            'foreign_origin' => 'nullable|string|max:100',
        ];

        // Validasi NIK Unique (kecuali untuk siswa yang sedang diedit)
        if ($this->filled('nik')) {
            $rules['nik'] = [
                'required',
                'max:16',
                Rule::unique('student', 'nik')->ignore($this->student->id)
            ];
        } else {
            $rules['nik'] = 'nullable|max:16';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi',
            'name.max' => 'Nama lengkap maksimal 150 karakter',
            'gender.required' => 'Jenis kelamin wajib diisi',
            'gender.in' => 'Jenis kelamin harus male atau female',
            'religion.required' => 'Agama wajib diisi',
            'religion.in' => 'Agama tidak valid',
            'birthdate.required' => 'Tanggal lahir wajib diisi',
            'birthdate.date' => 'Format tanggal lahir tidak valid',
            'birthplace.required' => 'Tempat lahir wajib diisi',
            'address.required' => 'Alamat wajib diisi',
            'child.required' => 'Anak ke- wajib diisi',
            'child.numeric' => 'Anak ke- harus berupa angka',
            'school_from.required' => 'Asal sekolah wajib diisi',
            'nik.required' => 'NIK wajib diisi',
            'nik.max' => 'NIK maksimal 16 karakter',
            'nik.unique' => 'NIK sudah digunakan oleh siswa lain',
            'guardian_email.email' => 'Format email wali tidak valid',
            'photo.image' => 'File yang diupload harus berupa gambar',
            'photo.mimes' => 'Format gambar harus jpeg, png, atau jpg',
            'photo.max' => 'Ukuran gambar maksimal 2MB',
            'weight.numeric' => 'Berat badan harus berupa angka',
            'height.numeric' => 'Tinggi badan harus berupa angka',
            'entry_date.date' => 'Format tanggal masuk tidak valid',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            // Data Diri
            'nik' => __('label.nik'),
            'name' => __('label.name'),
            'gender' => __('label.gender'),
            'religion' => __('label.religion'),
            'birthdate' => __('label.birthdate'),
            'birthplace' => __('label.birthplace'),
            'address' => __('label.address'),
            'child' => __('label.child_ke'),
            'school_from' => __('label.school_from'),
            'nickname' => __('label.nickname'),
            'blood_type' => __('label.blood_type'),
            'weight' => __('label.weight'),
            'height' => __('label.height'),
            'home_language' => __('label.home_language'),
            'personality' => __('label.personality'),
            'nationality' => __('label.nationality'),
            'photo' => __('label.photo'),

            // Data Ayah
            'father_name' => __('label.father_name'),
            'father_id_number' => __('label.father_id_number'),
            'father_status' => __('label.father_status'),
            'father_education' => __('label.father_education'),
            'father_occupation' => __('label.father_occupation'),
            'father_phone' => __('label.father_phone'),

            // Data Ibu
            'mother_name' => __('label.mother_name'),
            'mother_id_number' => __('label.mother_id_number'),
            'mother_status' => __('label.mother_status'),
            'mother_education' => __('label.mother_education'),
            'mother_occupation' => __('label.mother_occupation'),
            'mother_phone' => __('label.mother_phone'),

            // Data Wali
            'guardian_name' => __('label.guardian_name'),
            'guardian_id_number' => __('label.guardian_id_number'),
            'guardian_occupation' => __('label.guardian_occupation'),
            'guardian_email' => __('label.guardian_email'),
            'guardian_phone' => __('label.guardian_phone'),
            'guardian_income' => __('label.guardian_income'),

            // Data Keluarga
            'family_card_number' => __('label.family_card_number'),
            'family_income' => __('label.family_income'),
            'child_order' => __('label.child_ke'),
            'siblings_count' => __('label.siblings_count'),
            'step_siblings_count' => __('label.step_siblings_count'),
            'adopted_siblings_count' => __('label.adopted_siblings_count'),
            'family_members_count' => __('label.family_members_count'),
            'orphan_status' => __('label.orphan_status'),
            'guardian_notes' => __('label.guardian_notes'),
            'approval_status' => __('label.approval_status'),

            // Alamat
            'home_address' => __('label.home_address'),
            'home_district' => __('label.home_district'),
            'home_regency' => __('label.home_regency'),
            'home_province' => __('label.home_province'),
            'postal_code' => __('label.postal_code'),
            'previous_school_address' => __('label.previous_school_address'),
            'previous_school_district' => __('label.previous_school_district'),
            'previous_school_regency' => __('label.previous_school_regency'),
            'previous_school_province' => __('label.previous_school_province'),
            'distance_to_school' => __('label.distance_to_school'),

            // Akademik
            'previous_school_name' => __('label.previous_school_name'),
            'previous_school_npsn' => __('label.previous_school_npsn'),
            'previous_school_status' => __('label.previous_school_status'),
            'registration_number' => __('label.registration_number'),
            'session_id' => __('label.session_id'),
            'entry_date' => __('label.entry_date'),
            'payment_status' => __('label.payment_status'),
            'has_scholarship' => __('label.has_scholarship'),
            'scholarship_name' => __('label.scholarship_name'),
            'achievements' => __('label.achievements'),
            'recommendation_status' => __('label.recommendation_status'),
            'graduation_status' => __('label.graduation_status'),
            'notes' => __('label.notes'),
            'foreign_origin' => __('label.foreign_origin'),

            // Lainnya
            'card_number' => __('label.student_card_number'),
            'spp' => __('label.spp'),
            'location' => __('label.location'),
            'id_class' => __('label.class'),
            'medical_history' => __('label.medical_history'),
            'physical_disabilities' => __('label.physical_disabilities'),
            'daily_habits' => __('label.daily_habits'),
            'living_with_parents' => __('label.living_with_parents'),
        ];
    }
}
