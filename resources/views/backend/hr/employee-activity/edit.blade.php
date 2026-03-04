@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/employee-activity/edit" :breadcrumb-data="$activity->id" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('hr.employee-activity.update', $activity->id) }}" class="form-block"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- <input type="hidden" name="id_employee" id="id_employee" value="{{ $idEmployee }}"> --}}

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <x-form.input-text name="activity_name" :label="__('label.name_activity')" :old="$activity->activity_name" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="id_position">{{ __('label.position') }}</label>
                                <select id="id_position" name="id_position" class="form-select select2" required>
                                    @foreach ($position->pluck('name', 'id') as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ old('id_position', $activity->id_position) == $key ? 'selected' : '' }}>
                                            {{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="activity_type">{{ __('label.type') }}</label>
                                <select id="activity_type" name="activity_type" class="form-select select2" required>
                                    @foreach ($type as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ old('activity_type', $activity->activity_type) == $key ? 'selected' : '' }}>
                                            {{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="description" :label="__('label.description')" :old="$activity->description" />
                            </div>
                        </div>

                        <x-form.button-submit :cancel-route="route('hr.employee-activity.index')" submit-label="Simpan Perubahan" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .icon-photo {
            font-size: 72px;
            color: #888
        }

        .preview-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


        <script>
            $(document).ready(function() {
                // Select2
                $('#activity_type').select2();
                $('#id_position').select2();
            });

            // Tampilkan notifikasi error jika ada
            const error =
                "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset";

            $(document).ready(function() {
                if (error != "") setNotifInfo(error);
            });
        </script>
    @endpush
@endsection
