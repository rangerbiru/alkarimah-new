@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/position/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('hr.permit-type.store') }}" class="form-block">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text name="permit_type" :label="__('label.permit_type_name')" :old="old('permit_type')" />
                    </div>
                    <div class="col-md-6">
                        <x-form.radio name="level" :label="__('label.level')" :old="old('level')" :option="$level" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="col-md-4">
                            @php
                                $wage = [
                                    'y' => 'Tetap Dibayar',
                                    'n' => 'Tidak Dibayar',
                                ];
                            @endphp
                            <x-form.radio name="wage_status" :label="__('label.wage_status')" :old="old('wage_status')" :option="$wage" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <x-form.text-area name="description" :label="__('label.description')" :old="old('description')" />
                    </div>
                </div>

                <x-form.button-submit :cancel-route="route('hr.position.index')" />
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
