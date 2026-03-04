@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="branch/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('branch.store') }}" class="form-block">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name')"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4 col-md-3">
                    <x-form.input-group-mask
                        name="phone"
                        :label="__('label.phone_number')"
                        :old="old('phone')"
                        mask="handphone"
                        addon="<i class='bx bx-mobile'></i>"
                    />
                </div>
                <div class="col-sm-4 col-md-3">
                    <x-form.input-group-mask
                        name="email"
                        :label="__('label.email')"
                        :old="old('email')"
                        mask="email"
                        addon="<i class='bx bxs-envelope'></i>"
                    />
                </div>
            </div>

            <x-form.input-group
                name="address"
                :label="__('label.address')"
                :old="old('address')"
                addon="<i class='bx bxs-map'></i>"
            />

            <x-section-form
                :label="__('label.user_account')"
                icon="bx bxs-user-circle"
            />
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.input-text
                        name="username"
                        :label="__('label.user_name')"
                        :old="old('username')"
                    />
                </div>
                <div class="col-sm-6 col-md-4">
                    <x-form.radio
                        name="gender"
                        :label="__('label.gender')"
                        :old="old('gender')"
                        :option="$genders"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.input-group
                        type="password"
                        name="password"
                        :label="__('label.password')"
                        :old="old('password')"
                        addon="<i class='bx bxs-lock-alt'></i>"
                        autocomplete="new-password"
                    />
                </div>
                <div class="col-sm-6 col-md-4">
                    <x-form.input-group
                        type="password"
                        name="password_confirm"
                        :label="__('label.confirm_password')"
                        :old="old('password_confirm')"
                        addon="<i class='bx bxs-lock-alt'></i>"
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('branch.index')" />
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
