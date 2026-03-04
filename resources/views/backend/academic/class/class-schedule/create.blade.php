@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/class-hours/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('academic.class-hours.store') }}">
                @csrf
                <div class="container py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text name="name" :label="__('label.name')" :old="old('name')" />
                        </div>
                    </div>
                    @php
                        $days = [
                            'senin' => 'Senin',
                            'selasa' => 'Selasa',
                            'rabu' => 'Rabu',
                            'kamis' => 'Kamis',
                            'jumat' => 'Jumat',
                            'sabtu' => 'Sabtu',
                            'minggu' => 'Minggu',
                        ];
                    @endphp

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.select name="day[]" :label="__('label.day')" :option="$days" required multiple
                                class="form-control" />
                        </div>
                    </div>
                    {{-- <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="">{{ __('label.building') }}</label>
                            <select name="branchname" id="branchname" class="form-select select2" required>
                                <option value="">-- Pilih Gedung --</option>
                                @foreach ($branch as $br)
                                    <option value="{{ $br->id }}">{{ $br->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_branch" id="id_branch" value="{{ old('id_branch') }}">
                        </div>
                    </div> --}}

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="">{{ __('label.class') }}</label>
                            <select name="classname" id="classname" class="form-select select2" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($class as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_class" id="id_class" value="{{ old('id_class') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <x-form.input-text type="time" name="start_time" :label="__('label.start_time')" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text type="time" name="end_time" :label="__('label.end_time')" />
                        </div>
                    </div>


                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('academic.class-schedule.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>

    </div>




@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>


    <script>
        $(document).ready(function() {

            $('#branchname').select2({
                placeholder: 'Pilih Gedung',
                allowClear: true
            });

            $('#classname').select2({
                placeholder: 'Pilih Kelas',
                allowClear: true
            });

            $('#branchname').on('change', function() {
                $('#id_branch').val($(this).val());
            });

            $('#classname').on('change', function() {
                $('#id_class').val($(this).val());
            });
        });
    </script>
@endpush
