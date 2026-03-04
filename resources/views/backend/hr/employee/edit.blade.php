@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="hr/employee/edit"
    :breadcrumb-data="$employee->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('hr.employee.update', $employee->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-text
                        name="nip"
                        :label="__('label.nip')"
                        :old="old('nip', $employee->nip)"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-text
                        name="nik"
                        :label="__('label.nik')"
                        :old="old('nik', $employee->nik)"
                        optional
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $employee->name)"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group-mask
                        name="phone"
                        :label="__('label.phone_number')"
                        :old="old('phone', $employee->phone)"
                        mask="handphone"
                        addon="<i class='bx bx-mobile'></i>"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group-mask
                        name="email"
                        :label="__('label.email')"
                        :old="old('email', $employee->email)"
                        mask="email"
                        addon="<i class='bx bx-envelope'></i>"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.radio
                        name="gender"
                        :label="__('label.gender')"
                        :old="old('gender', $employee->gender->value)"
                        :option="$genders"
                    />
                </div>
                <div class="col-sm-6 col-md-4">
                    <x-form.radio
                        name="marital_status"
                        :label="__('label.marital_status')"
                        :old="old('marital_status', $employee->marital_status)"
                        :option="$marital_statuses"
                    />
                </div>
                <div class="col-md-4">
                    <x-form.input-text
                        name="education"
                        :label="__('label.last_education')"
                        :old="old('education', $employee->education)"
                        optional
                    />
                </div>
            </div>

            <div class="mb-5">
                <x-form.text-area
                    name="address"
                    :label="__('label.address')"
                    :old="old('address', $employee->address)"
                />
            </div>

            <x-section-form
                :label="__('label.work')"
                icon="fa-solid fa-briefcase"
            />

            <div class="row">
                <div class="col-md-4">
                    <x-form.select
                        name="id_position"
                        :label="__('label.position')"
                        :option="$positions"
                        :old="old('id_position', $employee->id_position)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="placement"
                        :label="__('label.placement')"
                        :old="old('placement', $employee->placement)"
                    />
                </div>
                <div class="col-sm-6 col-md-4">
                    <x-form.radio
                        name="status_employment"
                        :label="__('label.employment_status')"
                        :old="old('status_employment', $employee->status_employment)"
                        :option="$employments"
                    />
                </div>
                <div class="col-sm-6 col-md-4">
                    <x-form.radio
                        name="status_teacher"
                        :label="__('label.teacher_status')"
                        :old="old('status_teacher', $employee->status_teacher)"
                        :option="$yesno"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <x-form.text-area
                        name="task_main"
                        :label="__('label.main_task')"
                        :old="old('task_main', $employee->task_main)"
                    />
                </div>
                <div class="col-sm-6">
                    <x-form.text-area
                        name="task_additional"
                        :label="__('label.additional_task')"
                        :old="old('task_additional', $employee->task_additional)"
                        optional
                    />
                </div>
            </div>

            <x-section-form
                :label="__('label.user_account')"
                icon="fa-solid fa-user-circle"
            />

            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group
                        type="password"
                        name="password"
                        :label="__('label.password')"
                        :old="old('password')"
                        addon="<i class='bx bxs-lock-alt'></i>"
                        autocomplete="new-password"
                        :info="__('string.info_only_filled')"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group
                        type="password"
                        name="password_confirm"
                        :label="__('label.confirm_password')"
                        :old="old('password_confirm')"
                        addon="<i class='bx bxs-lock-alt'></i>"
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('hr.employee.index')" />
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

    $(".handphone-mask").inputmask({ alias: "handphone" })
    $(".email-mask").inputmask({ alias: "email" })
})
</script>
@endpush
