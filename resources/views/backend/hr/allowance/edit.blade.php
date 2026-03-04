@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="hr/allowance/edit"
    :breadcrumb-data="$allowance->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('hr.allowance.update', $allowance->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $allowance->name)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.select
                        name="category"
                        :label="__('label.category')"
                        :option="$categories"
                        :old="old('category', $allowance->category)"
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('hr.allowance.index')" />
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
