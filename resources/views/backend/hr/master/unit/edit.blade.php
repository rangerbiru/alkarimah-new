@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/unit/edit" :breadcrumb-data="$unit->id" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hr.unit.update', $unit->id) }}" class="form-block">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <x-form.select name="location_id" :label="__('label.location')" :option="$locations" :old="$unit->location_id"
                            :selected="$unit->location_id" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input-text name="unit" :label="__('label.unit_location')" :old="$unit->unit" required />
                    </div>
                </div>

                <x-form.button-submit :cancel-route="route('hr.unit.index')" />
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
