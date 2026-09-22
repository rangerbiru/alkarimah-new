@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="user/edit/kepala-sekolah"
    :breadcrumb-data="(object) ['id' => $user->encrypted_id, 'role' => $role]"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('user.update.kepala-sekolah', $user->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="alert alert-outline-info">
                {{ __('string.kepala_sekolah_edit_info') }}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('label.employee') }}</label>
                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <x-form.select
                        name="level_education[]"
                        id="level_education"
                        :label="__('label.level_education')"
                        :option="$educations"
                        :selected="old('level_education', $selected_educations)"
                        :data-placeholder="__('label.choose_education_level')"
                        multiple
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
})
</script>
@endpush
