@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/violation/edit" :breadcrumb-data="$violation->id" />
@endsection

@section('content')
    <form method="post" action="{{ route('academic.violation.update', $violation->id) }}" class="form-block"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Header Info (Tanggal & Petugas) -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark small">Tanggal</label>
                        <input type="date" name="date" class="form-control"
                            value="{{ old('date', $violation->date) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark small">Petugas</label>
                        <input type="text" class="form-control"
                            value="{{ $violation->employee->name ?? auth()->user()->name }}" disabled>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 1: DATA SANTRI -->
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-header bg-white p-3">
                <h6 class="mb-0 fw-bold"><i class="bx bx-user me-2"></i> Data Santri</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="student_select" class="form-label text-dark">NIS / Nama</label>
                        <select id="student_select" name="student_id" class="form-select select2-student"
                            style="width: 100%;">
                            @if ($violation)
                                <option value="{{ $violation->student->id }}" selected>
                                    {{ $violation->student->nis }} | {{ $violation->student->name }}
                                </option>
                            @endif
                        </select>
                        <input type="hidden" name="student_id" id="student_id_hidden"
                            value="{{ old('student_id', $violation->student_id) }}">
                    </div>

                    <input type="text" id="display_gender" class="form-control bg-light" readonly
                        value="{{ $violation->student->gender_name ?? '-' }}">

                    <input type="text" id="display_class" class="form-control bg-light" readonly
                        value="{{ optional($violation->student->class)->name ?? '-' }}">

                    <input type="text" id="display_asrama" class="form-control bg-light" readonly
                        value="{{ optional($violation->student->asrama)->name ?? '-' }}">
                </div>
            </div>
        </div>

        <!-- CARD 2: DATA PELANGGARAN -->
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-header bg-white p-3">
                <h6 class="mb-0 fw-bold"><i class="bx bx-error-circle me-2"></i> {{ __('label.data_violation') }}</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <x-form.select id="violation_type" name="violation_type_id" :label="__('label.violation_type')" :option="['' => '-- Memuat Data... --']"
                            required />
                    </div>

                    <div class="col-md-4">
                        <label>{{ __('label.impact_level') }}</label>
                        <div class="input-group form-group">
                            <input type="text" name="impact_level" id="auto_impact" class="form-control" required
                                readonly>
                            <span class="input-group-text bg-light text-muted small">otomatis</span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label>{{ __('label.points') }}</label>
                        <div class="input-group form-group">
                            <input type="text" name="points" id="auto_points" class="form-control" required readonly>
                            <span class="input-group-text bg-light text-muted small">otomatis</span>
                        </div>
                        <input type="hidden" id="hidden_points" name="points_snapshot">
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: DETAIL TAMBAHAN & STATUS -->
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-header bg-white p-3">
                <h6 class="mb-0 fw-bold"><i class="bx bx-detail me-2"></i> Detail Tambahan</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        @php
                            $location = [
                                'masjid' => 'Masjid',
                                'asrama' => 'Asrama',
                                'kelas' => 'Kelas',
                                'kantin' => 'Kantin',
                                'kantor' => 'Kantor',
                                'lainnya' => 'Lainnya',
                            ];
                        @endphp
                        <x-form.select id="location" name="location" :label="__('label.location')" :option="$location" :old="old('location', $violation->location)"
                            required />
                    </div>
                    <div class="col-md-4">
                        @php
                            $defaultTime = old('time', $violation->time);
                        @endphp
                        <x-form.time-picker name="time" id="time" :label="__('label.time_incident')" :optional="false"
                            :old="$defaultTime" required />
                    </div>
                    <div class="col-md-4 mb-2">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="document.getElementById('time').value = '{{ date('H:i') }}'">
                            <i class="bx bx-time-five"></i> Set Sekarang
                        </button>
                    </div>

                    <div class="col-md-12">
                        <x-form.text-area name="notes" :label="__('label.description')" :old="old('notes', $violation->notes)" />
                    </div>

                    <div class="col-sm-6 col-md-12">
                        <x-form.input-file name="proof" id="photo" :label="__('label.proof_upload')" accept-file="image"
                            image-height="100px" optional :old="$violation->proof" />
                        @if ($violation->proof)
                            <small class="text-muted d-block mt-1">File saat ini:
                                {{ basename($violation->proof) }}</small>
                        @endif
                    </div>
                </div>

                <hr class="my-4">

                <!-- Status Verifikasi -->
                <div class="row">
                    <div class="col-md-4">
                        @php
                            $status = [
                                'draft' => 'Draft',
                                'tabayyun' => 'Menunggu Tabayyun',
                                'approved' => 'Disahkan',
                            ];
                        @endphp
                        <x-form.radio name="status" :label="__('label.status')" :old="old('status', $violation->status)" :option="$status" />
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 my-4">
            <a href="{{ route('academic.violation.index') }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary px-4">Update Pelanggaran</button>
        </div>

    </form>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset";

        // Data Lama dari Database untuk inisialisasi
        const oldStudentId = "{{ old('student_id', $violation->student_id) }}";
        const oldViolationId = "{{ old('violation_type_id', $violation->violation_id) }}";

        $(document).ready(function() {
            if (error != "" && typeof setNotifInfo === 'function') {
                setNotifInfo(error);
            }

            // --- A. LOAD SEMUA JENIS PELANGGARAN ---
            var $typeSelect = $('#violation_type');

            $.ajax({
                url: "{{ route('academic.violation.types') }}",
                type: 'GET',
                success: function(response) {
                    let options = '<option value="">-- Pilih Jenis Pelanggaran --</option>';
                    let selectedFound = false;

                    if (response.length > 0) {
                        response.forEach(function(item) {
                            let label = item.description;
                            if (item.group && item.group.trim() !== '') {
                                label =
                                    `[${item.group}] ${item.description} (${item.points} Poin)`;
                            } else {
                                label = `${item.description} (${item.points} Poin)`;
                            }

                            let isSelected = (item.id == oldViolationId) ? 'selected' : '';
                            if (isSelected) selectedFound = true;

                            options += `<option value="${item.id}" ${isSelected}
                                data-impact="${item.impact_level}" 
                                data-points="${item.points}">
                                ${label}
                            </option>`;
                        });
                    }

                    $typeSelect.html(options);

                    // Jika ada nilai lama, trigger change untuk mengisi poin/dampak
                    if (selectedFound) {
                        $typeSelect.trigger('change');
                    }
                },
                error: function() {
                    $typeSelect.html('<option value="">Gagal memuat data</option>');
                }
            });

            // --- B. LOGIKA SELECT2 SISWA ---
            $('.select2-student').select2({
                ajax: {
                    url: "{{ route('academic.violation.students') }}",
                    dataType: 'json',
                    type: 'POST',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            _token: '{{ csrf_token() }}'
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                placeholder: 'Cari NIS atau Nama...',
                templateResult: formatStudent,
                templateSelection: formatStudent
            });

            function formatStudent(student) {
                if (!student.id) return student.text;
                return $('<div class="fw-bold">' + student.text + '</div>');
            }

            $('.select2-student').on('select2:select', function(e) {
                var data = e.params.data;

                $('#student_id_hidden').val(data.id);
                $('#display_gender').val(data.gender);
                $('#display_class').val(data.class);
                $('#display_asrama').val(data.asrama);
            });

            $('.select2-student').on('select2:unselect', function(e) {
                $('#student_id_hidden').val('');
                $('#display_gender').val('');
                $('#display_class').val('');
                $('#display_asrama').val('');
            });

            $('#violation_type').on('change', function() {
                var option = $(this).find(':selected');
                var impact = option.data('impact');
                var points = option.data('points');

                $('#auto_impact').val(impact || '-');
                $('#auto_points').val(points || 0);
                $('#hidden_points').val(points || 0);

                // Reset kelas warna
                $('#auto_impact').removeClass(
                    'bg-info bg-warning-subtle bg-warning bg-danger-subtle bg-danger text-dark text-white'
                );

                // Logika warna
                if (impact === 'rendah') {
                    $('#auto_impact').addClass('bg-info text-dark');
                } else if (impact === 'menengah') {
                    $('#auto_impact').addClass('bg-warning-subtle text-dark');
                } else if (impact === 'tinggi') {
                    $('#auto_impact').addClass('bg-warning text-dark');
                } else if (impact === 'sangat tinggi') {
                    $('#auto_impact').addClass('bg-danger-subtle text-danger');
                } else if (impact === 'fatal') {
                    $('#auto_impact').addClass('bg-danger text-white');
                }
            });
        });
    </script>
@endpush
