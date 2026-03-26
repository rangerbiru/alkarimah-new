@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
    <x-section-page-mobile :label="$title" :icon="$icon" />
@endsection

@section('content')
    <div class="card card-tab">
        <div class="card-body p-3">
            <ul class="nav nav-tabs tab-style-1 overflow-x-auto flex-nowrap" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link d-flex align-items-center active" data-bs-toggle="tab" data-bs-target="#form-identity"
                        aria-current="page" href="#form-identity" aria-selected="true" role="tab">
                        <i class="fa-solid fa-address-card"></i> &nbsp;{{ __('label.identity') }}
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link d-flex align-items-center" style="width: 120px !important" data-bs-toggle="tab"
                        data-bs-target="#form-parent" href="#form-parent" aria-selected="false" role="tab"
                        tabindex="-1">
                        <i class="fa-solid fa-users"></i> &nbsp;{{ __('label.parent') }}
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#form-academic"
                        href="#form-academic" aria-selected="false" role="tab" tabindex="-1">
                        <i class="fa-solid fa-award"></i> &nbsp;{{ __('label.academic') }}
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#form-other"
                        href="#form-other" aria-selected="false" role="tab" tabindex="-1">
                        <i class="fa-solid fa-bars"></i> &nbsp;{{ __('label.other') }}
                    </a>
                </li>
            </ul>
            <form method="post" action="{{ route('academic.student.update.parent', $student->encrypted_id) }}"
                class="form-block" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="tab-content">

                    <div class="tab-pane active border-0 p-0 pt-2" id="form-identity" role="tabpanel">

                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="nik" :label="__('label.nik')" :old="old('nik', $student->nik)" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="name" :label="__('label.name')" :old="old('name', $student->name)" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="nickname" :label="__('label.nickname')" :old="old('nickname', $student->profile->nickname ?? null)" optional />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-md-3">
                                <x-form.radio name="gender" :label="__('label.gender')" :old="old('gender', $student->gender->value)" :option="$genders" />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.select name="religion" :label="__('label.religion')" :option="$religions" :old="old('religion', $student->profile->religion ?? ($student->religion ?? null))" />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.select name="blood_type" :label="__('label.blood_type')" :option="['A', 'B', 'AB', 'O']" :old="old('blood_type', $student->profile->blood_type ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.date-picker name="birthdate" picker-type="date" :label="__('label.birthdate')"
                                    :old="old('birthdate', $student->birthdate)" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="birthplace" :label="__('label.birthplace')" :old="old('birthplace', $student->birthplace)" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-mask name="child" :label="__('label.child_ke')" mask="nominal" :old="old('child', $student->child)"
                                    :info="__('string.fill_in_with_numbers')" optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="nationality" :label="__('label.nationality')" :old="old('nationality', $student->academic->nationality ?? 'Indonesia')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="weight" :label="__('label.weight')" mask="nominal" :old="old('weight', $student->profile->weight ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="height" :label="__('label.height')" mask="nominal" :old="old('height', $student->profile->height ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="home_language" :label="__('label.home_language')" :old="old('home_language', $student->profile->home_language ?? null)" optional />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form.text-area name="address" :label="__('label.home_address')" :old="old('address', $student->address->home_address ?? $student->address)" rows="3" />
                            </div>
                            <div class="col-md-6">
                                <x-form.text-area name="personality" :label="__('label.personality')" :old="old('personality', $student->profile->personality ?? null)" rows="3"
                                    optional />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-file name="photo" id="photo" :label="__('label.photo')" accept-file="image"
                                    image-height="100px" :info="__('string.info_photo')" optional />
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane border-0 p-0 pt-2" id="form-parent" role="tabpanel">

                        {{-- Data Ayah --}}
                        <h6 class="text-primary mb-3">Data Ayah</h6>
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="father_name" :label="__('label.father_name')" :old="old('father_name', $student->studentParent->father_name ?? null)" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="father_id_number" :label="__('label.father_id_number')" :old="old('father_id_number', $student->studentParent->father_id_number ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.select name="father_status" :label="__('label.father_status')" :option="['Hidup', 'Meninggal']"
                                    :old="old('father_status', $student->studentParent->father_status ?? null)" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="father_education" :label="__('label.father_education')" :old="old('father_education', $student->studentParent->father_education ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="father_occupation" :label="__('label.father_occupation')" :old="old('father_occupation', $student->studentParent->father_occupation ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="father_phone" :label="__('label.father_phone')" :old="old('father_phone', $student->studentParent->father_phone ?? null)" optional />
                            </div>
                        </div>

                        {{-- Data Ibu --}}
                        <h6 class="text-primary mb-3 mt-4">Data Ibu</h6>
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="mother_name" :label="__('label.mother_name')" :old="old('mother_name', $student->studentParent->mother_name ?? null)" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="mother_id_number" :label="__('label.mother_id_number')" :old="old('mother_id_number', $student->studentParent->mother_id_number ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.select name="mother_status" :label="__('label.mother_status')" :option="['Hidup', 'Meninggal']"
                                    :old="old('mother_status', $student->studentParent->mother_status ?? null)" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="mother_education" :label="__('label.mother_education')" :old="old('mother_education', $student->studentParent->mother_education ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="mother_occupation" :label="__('label.mother_occupation')" :old="old('mother_occupation', $student->studentParent->mother_occupation ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="mother_phone" :label="__('label.mother_phone')" :old="old('mother_phone', $student->studentParent->mother_phone ?? null)" optional />
                            </div>
                        </div>

                        {{-- Data Wali --}}
                        <h6 class="text-primary mb-3 mt-4">Data Wali (Jika Ada)</h6>
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="guardian_name" :label="__('label.guardian_name')" :old="old('guardian_name', $student->studentParent->guardian_name ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="guardian_id_number" :label="__('label.guardian_id_number')" :old="old(
                                    'guardian_id_number',
                                    $student->studentParent->guardian_id_number ?? null,
                                )"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="guardian_phone" :label="__('label.guardian_phone')" :old="old('guardian_phone', $student->studentParent->guardian_phone ?? null)" optional />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="guardian_occupation" :label="__('label.guardian_occupation')" :old="old(
                                    'guardian_occupation',
                                    $student->studentParent->guardian_occupation ?? null,
                                )"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="guardian_email" :label="__('label.guardian_email')" :old="old('guardian_email', $student->studentParent->guardian_email ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="guardian_income" :label="__('label.guardian_income')" :old="old('guardian_income', $student->studentParent->guardian_income ?? null)" optional />
                            </div>
                        </div>

                        {{-- Data Keluarga --}}
                        <h6 class="text-primary mb-3 mt-4">Data Keluarga</h6>
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="family_card_number" :label="__('label.family_card_number')" :old="old(
                                    'family_card_number',
                                    $student->studentParent->family_card_number ?? null,
                                )"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="family_income" :label="__('label.family_income')" :old="old('family_income', $student->studentParent->family_income ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.select name="orphan_status" :label="__('label.orphan_status')" :option="['Bukan Yatim', 'Yatim Piatu', 'Yatim Ayah', 'Yatim Ibu']"
                                    :old="old('orphan_status', $student->studentParent->orphan_status ?? null)" optional />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-mask name="child_order" :label="__('label.child_ke')" mask="nominal" :old="old('child_order', $student->studentParent->child_order ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-mask name="siblings_count" :label="__('label.siblings_count')" mask="nominal"
                                    :old="old('siblings_count', $student->studentParent->siblings_count ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-mask name="step_siblings_count" :label="__('label.step_siblings_count')" mask="nominal"
                                    :old="old(
                                        'step_siblings_count',
                                        $student->studentParent->step_siblings_count ?? 0,
                                    )" optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-mask name="adopted_siblings_count" :label="__('label.adopted_siblings_count')" mask="nominal"
                                    :old="old(
                                        'adopted_siblings_count',
                                        $student->studentParent->adopted_siblings_count ?? 0,
                                    )" optional />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <x-form.text-area name="guardian_notes" :label="__('label.guardian_notes')" :old="old('guardian_notes', $student->studentParent->guardian_notes ?? null)"
                                    rows="3" optional />
                            </div>
                            <div class="col-md-6">
                                <x-form.select name="approval_status" :label="__('label.approval_status')" :option="['on' => 'Disetujui', 'off' => 'Belum Disetujui']"
                                    :old="old(
                                        'approval_status',
                                        $student->studentParent->approval_status ?? 'off',
                                    )" />
                            </div>
                        </div>
                    </div>

                    {{-- TAB 3: DATA ALAMAT (Table: student_addresses) --}}
                    <div class="tab-pane border-0 p-0 pt-2" id="form-address" role="tabpanel">
                        <x-section-form icon="fa-solid fa-map-location-dot" :label="__('label.address_data')" />

                        {{-- Alamat Rumah --}}
                        <h6 class="text-primary mb-3 mt-4">Alamat Rumah</h6>
                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="home_address" :label="__('label.home_address')" :old="old('home_address', $student->address->home_address ?? null)" rows="2"
                                    optional />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="home_district" :label="__('label.home_district')" :old="old('home_district', $student->address->home_district ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="home_regency" :label="__('label.home_regency')" :old="old('home_regency', $student->address->home_regency ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="home_province" :label="__('label.home_province')" :old="old('home_province', $student->address->home_province ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="postal_code" :label="__('label.postal_code')" :old="old('postal_code', $student->address->postal_code ?? null)" optional />
                            </div>
                        </div>

                        {{-- Alamat Sekolah Asal --}}
                        <h6 class="text-primary mb-3 mt-4">Alamat Sekolah Asal</h6>
                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="previous_school_address" :label="__('label.previous_school_address')" :old="old(
                                    'previous_school_address',
                                    $student->address->previous_school_address ?? null,
                                )"
                                    rows="2" optional />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="previous_school_district" :label="__('label.previous_school_district')" :old="old(
                                    'previous_school_district',
                                    $student->address->previous_school_district ?? null,
                                )"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="previous_school_regency" :label="__('label.previous_school_regency')" :old="old(
                                    'previous_school_regency',
                                    $student->address->previous_school_regency ?? null,
                                )"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="previous_school_province" :label="__('label.previous_school_province')" :old="old(
                                    'previous_school_province',
                                    $student->address->previous_school_province ?? null,
                                )"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="distance_to_school" :label="__('label.distance_to_school')" :old="old('distance_to_school', $student->address->distance_to_school ?? null)"
                                    optional />
                            </div>
                        </div>
                    </div>

                    {{-- TAB 4: DATA AKADEMIK & LAINNYA (Table: student_academics & students) --}}
                    <div class="tab-pane border-0 p-0 pt-2" id="form-academic" role="tabpanel">
                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="school_from" :label="__('label.school_from')" :old="old(
                                    'school_from',
                                    $student->academic->school_from ?? ($student->school_from ?? null),
                                )" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="previous_school_npsn" :label="__('label.previous_school_npsn')" :old="old(
                                    'previous_school_npsn',
                                    $student->academic->previous_school_npsn ?? null,
                                )"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="previous_school_status" :label="__('label.previous_school_status')" :old="old(
                                    'previous_school_status',
                                    $student->academic->previous_school_status ?? null,
                                )"
                                    optional />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="registration_number" :label="__('label.registration_number')" :old="old('registration_number', $student->academic->registration_number ?? null)"
                                    optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.date-picker name="entry_date" picker-type="date" :label="__('label.entry_date')"
                                    :old="old(
                                        'entry_date',
                                        $student->academic->entry_date ?? ($student->entry_date ?? null),
                                    )" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.checkbox name="has_scholarship" :label="__('label.has_scholarship')" :old="old('has_scholarship', $student->academic->has_scholarship ?? 0)" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="scholarship_name" :label="__('label.scholarship_name')" :old="old('scholarship_name', $student->academic->scholarship_name ?? null)"
                                    optional />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form.text-area name="achievements" :label="__('label.achievements')" :old="old('achievements', $student->academic->achievements ?? null)"
                                    rows="3" optional />
                            </div>
                            <div class="col-md-6">
                                <x-form.text-area name="notes" :label="__('label.notes')" :old="old('notes', $student->academic->notes ?? null)" rows="3"
                                    optional />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-md-4">
                                <x-form.select name="recommendation_status" :label="__('label.recommendation_status')" :option="[
                                    'Recommended' => 'Direkomendasikan',
                                    'Not Recommended' => 'Tidak Direkomendasikan',
                                ]"
                                    :old="old(
                                        'recommendation_status',
                                        $student->academic->recommendation_status ?? 'Recommended',
                                    )" />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.select name="graduation_status" :label="__('label.graduation_status')" :option="[
                                    '' => '-',
                                    'passed' => 'Lulus',
                                    'failed' => 'Tidak Lulus',
                                    'reserve' => 'Cadangan',
                                ]"
                                    :old="old('graduation_status', $student->academic->graduation_status ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <x-form.input-text name="foreign_origin" :label="__('label.foreign_origin')" :old="old('foreign_origin', $student->academic->foreign_origin ?? null)"
                                    optional />
                            </div>
                        </div>


                    </div>

                    <div class="tab-pane border-0 p-0 pt-2" id="form-other" role="tabpanel">
                        <div class="row">
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-text name="card_number" :label="__('label.student_card_number')" :old="old('card_number', $student->card_number ?? null)" optional />
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <x-form.input-group-mask name="spp" :label="__('label.spp')" :old="old('spp', $student->spp ?? null)"
                                    mask="nominal" addon="Rp" optional />
                            </div>
                        </div>
                    </div>

                </div>

                <x-form.button-submit :cancel-route="route('academic.student.show', $student->encrypted_id)" />
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

        $(document).ready(function() {
            if (error != "")
                setNotifInfo(error)

            $(".nominal-mask").inputmask({
                alias: "nominal",
            })
        })
    </script>
@endpush
