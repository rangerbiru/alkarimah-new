@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="user/create"
    :breadcrumb-data="$role"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('user.store.kepala-sekolah') }}" class="form-block">
            @csrf

            <div class="alert alert-outline-info">
                {{ __('string.kepala_sekolah_info') }}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('label.employee') }}</label>
                        <x-form.select
                            name="employee"
                            id="employee"
                            :option="$employees"
                            :selected="old('employee')"
                            :data-placeholder="__('label.choose') . ' ' . __('label.employee')"
                        />
                    </div>
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
})
</script>
@endpush
