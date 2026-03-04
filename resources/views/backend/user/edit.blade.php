@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="user/edit"
    :breadcrumb-data="(object) ['id' => $user->encrypted_id, 'role' => $role]"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('user.update', $user->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $user->name)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group-mask
                        name="phone"
                        :label="__('label.phone_number')"
                        :old="old('phone', $user->phone)"
                        mask="handphone"
                        addon="<i class='bx bx-mobile'></i>"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group-mask
                        name="email"
                        :label="__('label.email')"
                        :old="old('email', $user->email)"
                        mask="email"
                        addon="<i class='bx bxs-envelope'></i>"
                    />
                </div>
                <div class="col-md-4">
                    <x-form.radio
                        name="gender"
                        :label="__('label.gender')"
                        :old="old('gender', $user->gender->value)"
                        :option="$genders"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group
                        type="password"
                        name="password"
                        :label="__('label.password')"
                        :old="old('password')"
                        addon="<i class='bx bxs-lock-alt'></i>"
                        autocomplete="new-password"
                        :inf="__('string.info_only_filled')"
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

            <x-form.button-submit :cancel-route="route('user.index', $role)" />
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
