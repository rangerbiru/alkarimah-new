@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
    <x-section-page-mobile :label="$title" :icon="$icon" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('academic.student-permit.store') }}">
                @csrf
                <div class="container py-2">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_name">{{ __('label.student_name') }}</label>
                            <select id="student_name" name="student_id" class="form-select select2">
                                <option value="" selected disabled>-- Pilih Siswa --</option>
                                @foreach ($student as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>

                            <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id') }}"
                                required>

                            <input type="hidden" name="student_names" id="student_names">

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
                        <a href="{{ route('academic.student-permit.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
        </div>
        </form>

        <div class="card-body">
            <h5 class="text-center">Daftar Siswa Yang Diajukan Izin</h5>
            <div class="table-responsive">
                <table id="studentTable" class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>{{ __('label.permit_group_name') }}</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
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
        let studentTable;

        $(document).ready(function() {
            studentTable = $('#studentTable').DataTable({
                columnDefs: [{
                    targets: -1,
                    orderable: false,
                    searchable: false
                }],
                dom: 'tip'
            });

            $('#student_name').select2({
                placeholder: 'Pilih Siswa',
                allowClear: true
            });

            $('#purpose').select2({
                placeholder: 'Pilih Keperluan Izin',
                allowClear: true
            });

            $('#student_name').on('change', function() {
                const studentId = $(this).val();
                const studentName = $('#student_name option:selected').text();

                if (!studentId) return;

                $.ajax({
                    url: '{{ route('academic.student-permit.groups') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        student_id: studentId
                    },
                    success: function(groups) {
                        if (!groups.length) {
                            alert('Grup tidak ditemukan untuk siswa ini.');
                            return;
                        }

                        const group = groups[0];
                        const groupName = group.group_name;
                        const ustadzName = group.ustadz?.name || '-';
                        const displayGroup =
                            `${groupName} - ${group.ustadz?.gender == 'male' ? 'Ustadz' : 'Ustadzah'} ${ustadzName}`;

                        const exists = studentTable
                            .rows()
                            .data()
                            .toArray()
                            .some(row => $(row[2]).data('student-id') == studentId);

                        if (exists) {
                            alert('Siswa sudah ditambahkan.');
                            $('#student_name').val('').trigger('change');
                            return;
                        }

                        const row = [
                            studentName,
                            displayGroup,
                            `<button class="btn btn-sm btn-danger btn-remove" data-student-id="${studentId}">Hapus</button>`
                        ];

                        studentTable.row.add(row).draw();
                        updateLocalStorage();

                        // Reset select
                        $('#student_name').val('').trigger('change');
                    },
                    error: function() {
                        alert('Gagal mengambil data grup siswa.');
                    }
                });
            });

            // Hapus baris dari DataTable
            $('#studentTable tbody').on('click', '.btn-remove', function() {
                const row = $(this).closest('tr');
                studentTable.row(row).remove().draw();
                updateLocalStorage();
            });

            // Simpan data ke localStorage
            function updateLocalStorage() {
                const data = [];
                studentTable.rows().every(function() {
                    const studentName = this.data()[0];
                    const groupName = this.data()[1];
                    const studentId = $(this.data()[2]).data('student-id');

                    data.push({
                        id: studentId,
                        name: studentName,
                        group: groupName
                    });
                });

                localStorage.setItem('student_names', JSON.stringify(data));
                $('#student_names').val(JSON.stringify(data));
            }

            // OtherPurpose toggle
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
