@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/student-permit-group/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('academic.basic.store') }}">
                @csrf
                <div class="container py-2">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacher_name">{{ __('label.name') }}</label>
                            <select id="teacher_name" class="form-select select2">
                                <option value="">-- Pilih Ustadz --</option>
                                @foreach ($employee as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_teacher" id="id_teacher" value="{{ old('id_teacher') }}"
                                required>

                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="">{{ __('label.subject') }}</label>
                            <select name="subject_name" id="subject_name" class="form-select select2" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach ($subject as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_subject" id="id_subject" value="{{ old('id_subject') }}">
                        </div>
                    </div>

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

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('academic.student-permit-group.index') }}" id="btn-cancel"
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
            $('#teacher_name').select2({
                placeholder: 'Pilih Pegawai',
                allowClear: true
            });

            $('#subject_name').select2({
                placeholder: 'Pilih Mata Pelajaran',
                allowClear: true
            });

            $('#classname').select2({
                placeholder: 'Pilih Kelas',
                allowClear: true
            });

            $('#teacher_name').on('change', function() {
                $('#id_teacher').val($(this).val());
            });

            $('#subject_name').on('change', function() {
                $('#id_subject').val($(this).val());
            });

            $('#classname').on('change', function() {
                $('#id_class').val($(this).val());
            });


        });
    </script>
@endpush
