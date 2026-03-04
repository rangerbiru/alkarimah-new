@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/bill/type/edit"
    :breadcrumb-data="$type->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('finance.bill.type.update', $type->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $type->name)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.select
                        name="period"
                        id="period"
                        :label="__('label.period')"
                        :option="$periods"
                        :old="old('period', $type->period->value)"
                    />
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.radio
                        name="spp"
                        :label="__('label.spp')"
                        :old="old('spp')"
                        :option="$spp_options"
                        :old="old('spp', $type->spp)"
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('finance.bill.type.index')" />
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
