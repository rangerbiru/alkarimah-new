@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/student-permit/edit" :breadcrumb-data="$permit->id" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('academic.student-permit.update', $permit->id) }}">
                @csrf
                @method('PUT')
                <div class="container py-2">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="permit_group_name">{{ __('label.permit_group_name') }}</label>
                            <select id="permit_group_name" class="form-select select2">
                                @foreach ($permitGroup as $group)
                                    <option value="{{ $group->id }}"
                                        {{ $permit->student_permit_group_id == $group->id ? 'selected' : '' }}>
                                        {{ $group->group_name }} - Ustadz {{ $group->ustadz->name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="student_permit_group_id" id="student_permit_group_id"
                                value="{{ old('student_permit_group_id', $permit->student_permit_group_id) }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_name">{{ __('label.student_name') }}</label>
                            <select id="student_name" class="form-select select2">
                                {{-- <option value="{{ $permit->student->id }}" selected>{{ $permit->student->name }}</option> --}}
                                @foreach ($studentGroup as $student)
                                    <option value="{{ $student->student_id }}"
                                        {{ $student->student_id == $permit->student_id ? 'selected' : '' }}>
                                        {{ $student->student_name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="student_id" id="student_id"
                                value="{{ old('student_id', $permit->student_id) }}" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <x-form.input-text type="datetime-local" name="permit_start_date" label="Tanggal Mulai Izin"
                                :old="old(
                                    'permit_start_date',
                                    $permit->permit_start_date
                                        ? date('Y-m-d\TH:i', strtotime($permit->permit_start_date))
                                        : '',
                                )" required />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text type="datetime-local" name="permit_end_date" label="Tanggal Selesai Izin"
                                :old="old(
                                    'permit_end_date',
                                    $permit->permit_end_date
                                        ? date('Y-m-d\TH:i', strtotime($permit->permit_end_date))
                                        : '',
                                )" />
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12 mb-2">
                            <label for="purpose">Keperluan Izin</label>
                            <select id="purpose" name="purpose" class="form-select select2" required>
                                @foreach ($purposeOptions as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('purpose', $permit->purpose) == $key ? 'selected' : '' }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="otherPurposeInput" style="display: none; margin-top: 10px">
                            <x-form.input-text name="other_purpose_description" label="Deskripsi Keperluan Lainnya"
                                :old="old('other_purpose_description', $permit->other_purpose_description)" />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <x-form.input-text name="destination" label="Lokasi Tujuan (Opsional)" :old="old('destination', $permit->destination)" />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <x-form.text-area name="notes" label="Catatan (Opsional)" :old="old('notes', $permit->notes)" />
                        </div>
                    </div>

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('academic.student-permit.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
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
            // Select2
            $('#permit_group_name, #student_name, #purpose').select2();

            // Update hidden input
            $('#permit_group_name').on('change', function() {
                $('#student_permit_group_id').val($(this).val());
                $('#student_name').prop('disabled', true).empty().append(
                    `<option value="" disabled selected>-- Pilih Siswa --</option>`);
                if (!$(this).val()) return;

                $.ajax({
                    url: '{{ route('academic.student-permit.students') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        group_id: $(this).val()
                    },
                    success: function(students) {
                        students.forEach(student => {
                            $('#student_name').append(
                                `<option value="${student.student_id}">${student.student_name}</option>`
                            );
                        });
                        $('#student_name').removeAttr('disabled');
                    },
                    error: function() {
                        alert('Gagal mengambil data siswa');
                    }
                });
            });

            $('#student_name').on('change', function() {
                $('#student_id').val($(this).val());
            });

            // Purpose lainnya
            function toggleOtherPurpose() {
                const val = $('#purpose').val();
                if (val === 'Lainnya') {
                    $('#otherPurposeInput').slideDown();
                } else {
                    $('#otherPurposeInput').slideUp();
                    $('[name="other_purpose_description"]').val('');
                }
            }

            $('#purpose').on('change', toggleOtherPurpose);
            toggleOtherPurpose(); // initial check
        });
    </script>
@endpush
