@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/violation/edit" :breadcrumb-data="$violation->id" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hr.violation.update', $violation->id) }}" class="form-block">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text name="code" :label="__('label.code')" :old="old('code', $violation->code)" required maxlength="20" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input-text name="group" :label="__('label.group')" :old="old('group', $violation->group)" required maxlength="100" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="impact_level" :label="__('label.impact_level')" :option="[
                            'rendah' => __('Rendah'),
                            'menengah' => __('Menengah'),
                            'tinggi' => __('Tinggi'),
                            'sangat tinggi' => __('Sangat Tinggi'),
                            'fatal' => __('Fatal'),
                        ]" :old="old('impact_level', $violation->impact_level)" required />
                    </div>
                    <div class="col-md-6">
                        <x-form.input-text type="number" name="points" :label="__('label.points')" :old="old('points', $violation->points)"
                            min="0" required />
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <x-form.text-area name="description" :label="__('label.description')" :old="old('description', $violation->description)" required
                            maxlength="255" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="status" :label="__('label.status')" :option="[
                            'Aktif' => __('Aktif'),
                            'Nonaktif' => __('Nonaktif'),
                        ]" :old="old('status', $violation->status)" required />
                    </div>
                </div>

                <x-form.button-submit :cancel-route="route('hr.violation.index')" />
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset";

        $(document).ready(function() {
            if (error !== "") {
                setNotifInfo(error);
            }
        });
    </script>
@endpush
