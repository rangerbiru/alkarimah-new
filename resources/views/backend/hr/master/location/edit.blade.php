@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/location/edit" :breadcrumb-data="$location->id" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hr.location.update', $location->id) }}" class="form-block">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <x-form.input-text name="code" :label="__('label.location_code')" :value="$location->code" :old="$location->code" required />
                    </div>
                    <div class="col-md-6">
                        <x-form.input-text name="name" :label="__('label.name')" :value="$location->name" :old="$location->name" required />
                    </div>
                </div>

                <x-form.button-submit :cancel-route="route('hr.location.index')" />
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

        $(document).ready(function() {
            if (error != "")
                setNotifInfo(error)
        })
    </script>
@endpush
