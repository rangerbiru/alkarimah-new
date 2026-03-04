@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
<div class="card card-tab">
    <div class="card-body p-3">
        <form method="post" action="{{ route('academic.student.update.parent', $student->encrypted_id) }}" class="form-block" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <x-form.input-text
                name="nik"
                :label="__('label.nik')"
                :old="old('nik', $student->nik)"
            />
            <x-form.input-text
                name="name"
                :label="__('label.name')"
                :old="old('name', $student->name)"
            />
            <x-form.radio
                name="gender"
                :label="__('label.gender')"
                :old="old('gender', $student->gender->value)"
                :option="$genders"
            />
            <x-form.select
                name="religion"
                :label="__('label.religion')"
                :option="$religions"
                :old="old('religion', $student->religion->value)"
            />
            <x-form.date-picker
                name="birthdate"
                picker-type="date"
                :label="__('label.birthdate')"
                :old="old('birthdate', $student->birthdate)"
            />
            <x-form.input-text
                name="birthplace"
                :label="__('label.birthplace')"
                :old="old('birthplace', $student->birthplace)"
            />
            <x-form.text-area
                name="address"
                :label="__('label.address')"
                :old="old('address', $student->address)"
            />
            <x-form.input-mask
                name="child"
                :label="__('label.child_ke')"
                mask="nominal"
                :old="old('child', $student->child)"
                :info="__('string.fill_in_with_numbers')"
            />

            <x-section-form
                icon="fa-solid fa-award"
                :label="__('label.academic')"
            />
            <x-form.input-text
                name="school_from"
                :label="__('label.school_from')"
                :old="old('school_from', $student->school_from)"
            />

            <x-form.button-submit :cancel-route="route('academic.student.show', $student->encrypted_id)" />
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    $(".nominal-mask").inputmask({ alias: "nominal" })
})
</script>
@endpush