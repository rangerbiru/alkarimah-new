@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/attendance/group/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('hr.attendance.group.update', $data->id) }}">
                @csrf
                <div class="container py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text type="text" name="group_name" :label="__('label.group_name')" :old="old('group_name', $data->group_name)"
                                required />
                        </div>
                    </div>

                    @php
                        $days = $data->days->pluck('day_name')->toArray();
                    @endphp

                    <div class="row mb-1">
                        <div class="col-md-6 mb-1">
                            <label>{{ __('label.day') }}</label>
                            <select name="day_name" id="daySelect" class="form-control">
                                <option value="#" selected disabled>Pilih Hari</option>
                                @foreach ($days as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mt-2 d-none" id="time-section">
                        <div class="col-md-6">
                            <x-form.input-text type="time" id="check_in_time" name="check_in_time" label="Check In" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text type="time" id="check_out_time" name="check_out_time" label="Check Out" />
                        </div>
                    </div>

                    <div class="row mb-3 mt-2">
                        <div class="col-md-12">
                            <x-form.text-area name="description" label="Deskripsi (Opsional)" :old="old('description')" />
                        </div>
                    </div>

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('hr.attendance.group.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const dayTimes = @json($dayTimes);

        const daySelect = document.getElementById("day-select");
        const timeSection = document.getElementById("time-section");
        const checkIn = document.getElementById("check_in_time");
        const checkOut = document.getElementById("check_out_time");

        $(document).ready(function() {
            // Inisiasi select2
            $('#daySelect').select2({
                placeholder: 'Pilih Hari',
                allowClear: true,
            });

            $('#daySelect').on('change', function(e) {
                const selectedDay = $(this).val();

                if (dayTimes[selectedDay]) {
                    checkIn.value = dayTimes[selectedDay].check_in_time ?? '';
                    checkOut.value = dayTimes[selectedDay].check_out_time ?? '';
                    timeSection.classList.remove("d-none");
                } else {
                    checkIn.value = '';
                    checkOut.value = '';
                    timeSection.classList.remove("d-none");
                }
            });

            // Kode select2 lainnya (untuk group_name dan purpose)
            $('#group_name').select2({
                placeholder: 'Pilih Siswa',
                allowClear: true
            });

            $('#purpose').select2({
                placeholder: 'Pilih Grup Absensi',
                allowClear: true
            });

            $('#group_name').on('change', function() {
                $('#group_id').val($(this).val());
            });
        });
    </script>
@endpush

<style>
    .page {
        min-height: 0 !important;
    }

    .select2-results__option[aria-selected] {
        text-transform: capitalize;
    }
</style>
