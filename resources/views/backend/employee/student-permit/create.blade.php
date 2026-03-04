@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/student-permit/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('employee.student-permit.store') }}">
                @csrf
                <div class="container py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text label="Nama Ustadz" id="ustadz_name" :old="old('ustadz_name', $permitGroup->ustadz->name)" required disabled />
                            <input type="hidden" name="student_permit_group_id" id="student_permit_group_id"
                                value="{{ old('student_permit_group_id', $permitGroup->group_id) }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_name">{{ __('label.student_name') }}</label>
                            <select id="student_name" class="form-select select2">
                                <option value="">-- Pilih Siswa --</option>
                            </select>

                            <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id') }}" />
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <x-form.input-text type="datetime-local" name="permit_start_date" label="Tanggal Mulai Izin"
                                :old="old('permit_start_date')" required />
                        </div>

                        <div class="col-md-6">
                            <x-form.input-text type="datetime-local" name="permit_end_date" label="Tanggal Selesai Izin"
                                :old="old('permit_end_date')" />
                        </div>
                    </div>


                    @php
                        $purposeOptions = [
                            'Sakit' => 'Sakit',
                            'Keluarga Meninggal Dunia' => 'Keluarga Meninggal Dunia',
                            'Acara Keluarga' => 'Acara Keluarga',
                            'Perjalanan Penting' => 'Perjalanan Penting',
                            'Keperluan Darurat' => 'Keperluan Darurat',
                            'Lainnya' => 'Lainnya',
                        ];
                    @endphp

                    <div class="row mb-2">
                        <div class="col-md-12 mb-2">
                            <label for="purpose">Keperluan Izin</label>
                            <select id="purpose" name="purpose" class="form-select select2" required>
                                <option value="">-- Pilih Keperluan Izin --</option>
                                @foreach ($purposeOptions as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="otherPurposeInput" style="display: none; margin-top: 10px">
                            <x-form.input-text name="other_purpose_description" label="Deskripsi Keperluan Lainnya" />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <x-form.input-text name="destination" label="Lokasi Tujuan (Opsional)" :old="old('destination')" />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <x-form.text-area name="notes" label="Catatan (Opsional)" :old="old('notes')" />
                        </div>
                    </div>

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('employee.student-permit.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
        </div>
        </form>
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
            $('#student_name').select2({
                placeholder: 'Pilih Siswa',
                allowClear: true
            });

            $('#purpose').select2({
                placeholder: 'Pilih Keperluan Izin',
                allowClear: true
            });

            $('#student_name').on('change', function() {
                $('#student_id').val($(this).val());
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

    <script>
        $(document).ready(function() {
            const groupId = $('#student_permit_group_id').val();

            if (!groupId) return;

            $.ajax({
                url: '{{ route('employee.student-permit.students') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    group_id: groupId
                },
                success: function(students) {
                    students.forEach(student => {
                        $('#student_name').append(
                            `<option value="${student.student_id}">${student.student_name}</option>`
                        );
                    });
                },
                error: function() {
                    alert('Gagal mengambil data siswa');
                }
            });
        });
    </script>
@endpush
