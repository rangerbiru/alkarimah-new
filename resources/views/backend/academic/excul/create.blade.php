@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/excul/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('academic.excul.store') }}" class="form-block">
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

            <x-form.button-submit :cancel-route="route('academic.excul.index')" />
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
