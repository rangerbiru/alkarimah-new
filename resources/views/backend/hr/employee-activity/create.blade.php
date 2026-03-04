@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/employee-activity/create" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('hr.employee-activity.store') }}" class="form-block"
                enctype="multipart/form-data">
                <div class="card">
                    <div class="card-body">
                        @csrf
                        {{-- <input type="hidden" name="id_employee" id="id_employee" value="{{ $idEmployee }}"> --}}
                        <div class="row">
                            <div class="col-md-12">
                                <x-form.input-text name="activity_name" :label="__('label.name_activity')" :old="old('activity_name')" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <x-form.select name="id_position" :label="__('label.position')" :option="$position->pluck('name', 'id')" :old="$position" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <x-form.select name="activity_type" :label="__('label.type')" :option="$type" :old="$type" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="description" :label="__('label.description')" :old="old('description')" />
                            </div>
                        </div>
                        <x-form.button-submit :cancel-route="route('hr.employee-activity.index')" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

        $(document).ready(function() {
            if (error != "") setNotifInfo(error)
        })
    </script>
@endsection
