@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/attendance/group/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('hr.attendance.group.store') }}">
                @csrf
                <div class="container py-2">
                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.input-text type="text" name="group_name" :label="__('label.group_name')" :old="old('group_name')"
                                required />
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

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.select name="day[]" :label="__('label.day')" :option="$days" required multiple
                                class="form-control" />
                        </div>
                    </div>

                    {{-- @php
                        $position = [
                            'guru' => 'Guru',
                            'musyrif' => 'Musyrif',
                            'satpam' => 'Satpam',
                            'hrd' => 'HRD',
                            'dapur' => 'Dapur',
                        ];
                    @endphp --}}

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.select name="position" id="position" :label="__('label.position')" :option="$positions" required
                                class="form-control" />
                        </div>
                    </div>

                    @php
                        $shift = [
                            'Y' => 'Ya',
                            'N' => 'Tidak',
                        ];
                    @endphp

                    <div class="row mb-1">
                        <div class="col-md-6" style="margin-bottom: 15px">
                            <label for="shift_works">{{ __('label.shift_work') }}</label>
                            <select name="shift_work" id="shift_work" class="form-select select2" required>
                                <option value="#" disabled selected>Pilih</option>
                                @foreach ($shift as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="non-shift-fields" style="display: none; ">
                        <div class="row mb-1">
                            <div class="col-md-6">
                                <x-form.input-text type="time" name="check_in_time" :label="__('label.check_in_time')" />
                            </div>
                            <div class="col-md-6">
                                <x-form.input-text type="time" name="check_out_time" :label="__('label.check_out_time')" />
                            </div>
                        </div>
                    </div>

                    <div id="shift-fields" style="display: none; ">
                        <div class="row mb-1">
                            <div class="col-md-6">
                                <x-form.input-text type="time" name="shift1_check_in_time" :label="__('label.shift1_check_in_time')" />
                            </div>

                            <div class="col-md-6">
                                <x-form.input-text type="time" name="shift1_check_out_time" :label="__('label.shift1_check_out_time')" />
                            </div>

                            <div class="col-md-6">
                                <x-form.input-text type="time" name="shift2_check_in_time" :label="__('label.shift2_check_in_time')" />
                            </div>

                            <div class="col-md-6">
                                <x-form.input-text type="time" name="shift2_check_out_time" :label="__('label.shift2_check_out_time')" />
                            </div>

                            <div class="col-md-6">
                                <x-form.input-text type="time" name="shift3_check_in_time" :label="__('label.shift3_check_in_time')" />
                            </div>

                            <div class="col-md-6">
                                <x-form.input-text type="time" name="shift3_check_out_time" :label="__('label.shift3_check_out_time')" />
                            </div>
                        </div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <label for="tolerance_in">{{ __('label.attendance_tolerance_in') }}</label>
                            <div class="input-group form-group">
                                <input type="number" name="tolerance_in" id="tolerance_in" class="form-control"
                                    placeholder="Contoh: 10">
                                <span class="input-group-text">Menit</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="tolerance_out">{{ __('label.attendance_tolerance_out') }}</label>
                            <div class="input-group form-group">
                                <input type="number" name="tolerance_out" id="tolerance_out" class="form-control"
                                    placeholder="Contoh: 10">
                                <span class="input-group-text">Menit</span>
                            </div>
                        </div>
                    </div>


                    <div class="row mb-3">
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
        $(document).ready(function() {
            $('#shift_work').select2({
                placeholder: 'Pilih Shift Kerja',
                allowClear: true,
            });

            $('#shift_work').on('change', function() {

                if ($(this).val() === 'N') {
                    $('#non-shift-fields').show();
                    $('#shift-fields').hide();

                } else {
                    $('#shift-fields').show();
                    $('#non-shift-fields').hide();
                }
            });

        });
    </script>

    <script>
        $(document).ready(function() {
            $('#purpose').on('change', function() {
                const value = $(this).val();
                if (value === 'Lainnya') {
                    $('#otherPurposeInput').slideDown();
                } else {
                    $('#otherPurposeInput').slideUp();
                    $('[name="other_purpose_description"]').val('');
                }
            });

            if ($('#purpose').val() === 'Lainnya') {
                $('#otherPurposeInput').show();
            }
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

    .select2-container--default .select2-selection--multiple {
        padding-bottom: 60px !important;
    }
</style>
