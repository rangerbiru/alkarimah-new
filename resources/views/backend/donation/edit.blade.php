@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="donation/edit"
    :breadcrumb-data="$donation->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('finance.donation.update', $donation->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $donation->name)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group-mask
                        name="total"
                        :label="__('label.total')"
                        :old="old('total', $donation->total)"
                        addon="Rp"
                        mask="nominal"
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('finance.donation.index')" />
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

    $(".nominal-mask").inputmask({alias: "nominal"})
})
</script>
@endpush
