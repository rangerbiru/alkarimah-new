@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/halaqah/edit"
    :breadcrumb-data="$halaqah->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('academic.halaqah.update', $halaqah->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $halaqah->name)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name_pengampu"
                        :label="__('label.pengampu_name')"
                        :old="old('name_pengampu', $halaqah->name_pengampu)"
                    />
                </div>
            </div>

            <x-form.text-area
                name="description"
                :label="__('label.description')"
                :old="old('description', $halaqah->description)"
                optional
            />

            <x-form.button-submit :cancel-route="route('academic.halaqah.index')" />
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
