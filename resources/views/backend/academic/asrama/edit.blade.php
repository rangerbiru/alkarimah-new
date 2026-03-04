@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/asrama/edit"
    :breadcrumb-data="$asrama->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('academic.asrama.update', $asrama->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $asrama->name)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name_musrif"
                        :label="__('label.musrif_name')"
                        :old="old('name_musrif', $asrama->name_musrif)"
                    />
                </div>
            </div>

            <x-form.text-area
                name="description"
                :label="__('label.description')"
                :old="old('description', $asrama->description)"
                optional
            />

            <x-form.button-submit :cancel-route="route('academic.asrama.index')" />
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
