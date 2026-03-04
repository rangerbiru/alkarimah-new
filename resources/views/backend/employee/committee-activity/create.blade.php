@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/committee-activity/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('employee.committee-activity.store') }}" class="form-block"
                enctype="multipart/form-data">
                @csrf
                <div class="container py-2">
                    <x-section-form :label="__('Detail Kegiatan')" icon="fa-solid fa-user-circle" />

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text name="activity_date" type="date" :label="__('Tanggal Kegiatan')" :old="old('activity_date')" />
                        </div>
                        <div class="col-md-6">
                            <x-form.select name="related_field" :option="$relatedFields" :label="__('Bidang Terkait')" :old="old('related_field')" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text name="activity_type" :label="__('Jenis Kegiatan')" :value="old('activity_type', 'Kepanitiaan')" disabled
                                :old="old('activity_type', 'Kepanitiaan')" />
                        </div>
                        <div class="col-md-6">
                            <x-form.select name="activity_name" :option="$activity->pluck('activity_name', 'activity_name')" :label="__('Nama Kegiatan')" :old="old('activity_name')" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text :value="old('responsible_person', $employee->name)" :label="__('Penanggung Jawab')" :old="old('responsible_person', $employee->name)" readonly disabled />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text name="location" :label="__('Lokasi Kegiatan')" :old="old('location')" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text type="hidden" name="employee_id" id="selected-employees" :old="old('employee_id')"
                                readonly />
                            <x-form.input-group-button name="selected_employee_id" :label="__('Peserta Kegiatan')" :old="old('selected_employee_id')"
                                button-id="btn-choose-relation" button-label="<i class='fa-solid fa-search'></i>" readonly
                                data-bs-toggle="modal" data-bs-target="#pegawai" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text type="number" id="display-participant-count" :label="__('Jumlah Peserta')"
                                :old="old('participant_count')" disabled />
                        </div>
                    </div>

                    <x-section-form :label="__('Ringkasan Kegiatan')" icon="fa-solid fa-user-circle" />

                    <div class="row">
                        <div class="col-md-12">
                            <x-form.text-area name="activity_summary" :label="__('Ulasan Kegiatan')" :old="old('activity_summary')"
                                rows="3" />
                        </div>
                    </div>

                    <x-section-form :label="__('Dokumentasi & Lampiran Kegiatan')" icon="fa-solid fa-user-circle" />

                    <div class="row">
                        <div class="col-md-4">
                            <x-form.input-file name="photos[]" :label="__('Foto Kegiatan')" multiple id="photos-input"
                                :old="old('photos')" />
                            <div class="mt-2 mb-3" id="photos-preview"></div>
                        </div>
                        <div class="col-md-4">
                            <x-form.input-file name="sk[]" :label="__('Surat Keputusan (SK)')" multiple id="sk-input"
                                :old="old('sk')" />
                            <div class="mt-2 mb-3" id="sk-preview"></div>
                        </div>
                        <div class="col-md-4">
                            <x-form.input-file name="minutes[]" :label="__('Berita Acara')" multiple id="minutes-input"
                                :old="old('minutes')" />
                            <div class="mt-2 mb-3" id="minutes-preview"></div>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('employee.committee-activity.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
        </div>
        </form>
    </div>

    <div class="card">
        <div class="row p-3">
            <div class="col-md-12">
                <h5 class="py-2">Pegawai yang dipilih</h5>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered" id="table-selected">
                        <thead>
                            <tr>
                                <th style="width: 10px !important">No</th>
                                <th>Nama</th>
                                <th>Tugas Utama</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="pegawai" tabindex="-1" aria-labelledby="pegawaiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="pegawaiLabel">Data Pegawai</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table w-100" id="table-employee">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="form-check-input" id="select-all"></th>
                                    <th style="width: 50px;">{{ __('label.no') }}</th>
                                    <th>{{ __('label.name') }}</th>
                                    <th>{{ __('label.phone_number') }}</th>
                                    <th>{{ __('label.gender') }}</th>
                                    <th>{{ __('label.main_task') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>



@endsection

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
        type="text/javascript"></script>

    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

        $(document).ready(function() {
            if (error != "") setNotifInfo(error)
        })

        // Simpan file sementara per input
        let selectedFiles = {
            photos: [],
            sk: [],
            minutes: []
        };

        function previewFiles(inputId, previewId, fileType, key) {
            const input = document.getElementById(inputId);
            const previewContainer = document.getElementById(previewId);
            previewContainer.innerHTML = '';

            // Ambil file yang sudah dipilih (dari state global)
            const files = selectedFiles[key];

            if (!files || files.length === 0) return;

            files.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center mb-1 py-1 px-2 border rounded bg-light';
                div.style.fontSize = '0.85rem';
                div.style.width = '100%';

                if (fileType === 'image' && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '60px';
                        img.style.height = '60px';
                        img.style.objectFit = 'cover';
                        img.className = 'me-2 border';
                        div.insertBefore(img, div.firstChild);
                    };
                    reader.readAsDataURL(file);
                } else {
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-file me-2 text-primary';
                    icon.style.fontSize = '1.5rem';
                    div.appendChild(icon);
                }

                // Nama file
                const nameSpan = document.createElement('span');
                nameSpan.textContent = file.name;
                nameSpan.className = 'flex-grow-1 text-truncate mx-1';
                div.appendChild(nameSpan);

                // Tombol hapus
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-danger p-2';
                removeBtn.innerHTML = '<i class="bx bx-trash"></i>';
                removeBtn.onclick = function() {
                    // Hapus dari array
                    selectedFiles[key].splice(index, 1);
                    // Perbarui input (secara virtual)
                    updateFileInput(inputId, selectedFiles[key]);
                    // Perbarui preview
                    previewFiles(inputId, previewId, fileType, key);
                };
                div.appendChild(removeBtn);

                previewContainer.appendChild(div);
            });
        }

        function updateFileInput(inputId, files) {
            const input = document.getElementById(inputId);
            const dt = new DataTransfer();
            files.forEach(file => dt.items.add(file));
            input.files = dt.files;
        }

        ['photos', 'sk', 'minutes'].forEach(key => {
            const inputId = `${key}-input`;
            const previewId = `${key}-preview`;
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', function(e) {
                    const newFiles = Array.from(e.target.files);
                    const existingFiles = selectedFiles[key] || [];

                    // Gabung dan hapus duplikat berdasarkan nama + ukuran (opsional)
                    const combined = [...existingFiles, ...newFiles].filter((file, index, self) =>
                        index === self.findIndex(f =>
                            f.name === file.name && f.size === file.size
                        )
                    );

                    selectedFiles[key] = combined;
                    previewFiles(inputId, previewId, key === 'photos' ? 'image' : 'document', key);
                });
            }
        });
    </script>

    <script>
        const RESPONSIBLE_EMPLOYEE_ID = {{ $employee->id }};
        const RESPONSIBLE_EMPLOYEE_NAME = "{{ addslashes($employee->name) }}";
        const RESPONSIBLE_EMPLOYEE_TASK = "{{ addslashes($employee->task_main ?? '-') }}";

        let selectedEmployees = [];
        let selectedData = {};

        // Tambahkan penanggung jawab sebagai peserta wajib
        if (!selectedEmployees.includes(RESPONSIBLE_EMPLOYEE_ID.toString())) {
            selectedEmployees.push(RESPONSIBLE_EMPLOYEE_ID.toString());
            selectedData[RESPONSIBLE_EMPLOYEE_ID] = {
                id: RESPONSIBLE_EMPLOYEE_ID,
                name: RESPONSIBLE_EMPLOYEE_NAME,
                task_main: RESPONSIBLE_EMPLOYEE_TASK
            };
            $('#selected-employees').val(selectedEmployees.join(','));
        }

        // DataTable modal
        let table = $("#table-employee").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('employee.committee-activity.data-employee') }}",
                type: "POST",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                }
            },
            columns: [{
                    class: "align-middle text-center",
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        const isChecked = selectedEmployees.includes(row.id.toString());
                        const isResponsible = row.id == RESPONSIBLE_EMPLOYEE_ID;
                        const disabledAttr = isResponsible ? 'disabled checked' : (isChecked ? 'checked' :
                            '');
                        return `<input type="checkbox" class="employee-checkbox form-check-input" value="${row.id}" ${disabledAttr}>`;
                    }
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    data: "name",
                    class: "align-middle"
                },
                {
                    data: "phone",
                    class: "align-middle"
                },
                {
                    data: "gender",
                    class: "align-middle"
                },
                {
                    data: "task_main",
                    class: "align-middle"
                },
            ]
        });

        let tableSelected = $("#table-selected").DataTable({
            data: [],
            columns: [{
                    data: null,
                    render: (d, t, r, m) => m.row + 1
                },
                {
                    data: "name"
                },
                {
                    data: "task_main",
                    render: function(data, type, row) {
                        return data && data.trim() !== "" ? data : "-";
                    }
                },
                {
                    data: null,
                    render: (d, t, r) => {
                        if (r.id == RESPONSIBLE_EMPLOYEE_ID) {
                            return '<span class="text-muted"><i>Penanggung Jawab</i></span>';
                        }
                        return `<button class="btn btn-sm btn-danger btn-remove" data-id="${r.id}">Hapus</button>`;
                    }
                }
            ]
        });

        $('#table-employee').on('change', '.employee-checkbox', function() {
            let id = $(this).val();
            let rowData = table.row($(this).closest('tr')).data();

            if ($(this).is(':checked')) {
                if (!selectedEmployees.includes(id)) {
                    selectedEmployees.push(id);
                    selectedData[id] = rowData;
                }
            } else {
                selectedEmployees = selectedEmployees.filter(e => e !== id);
                delete selectedData[id];
            }

            $('#selected-employees').val(selectedEmployees.join(','));
            refreshSelectedTable();
        });

        // Refresh DataTable hasil pilihan
        function refreshSelectedTable() {
            let dataArr = selectedEmployees.map(id => selectedData[id]);
            tableSelected.clear().rows.add(dataArr).draw();
            updateParticipantCount();
        }

        // Hapus dari daftar pilihan via tombol
        $('#table-selected').on('click', '.btn-remove', function() {
            let id = $(this).data('id').toString();

            if (id == RESPONSIBLE_EMPLOYEE_ID) return;

            selectedEmployees = selectedEmployees.filter(e => e !== id);
            delete selectedData[id];
            $('#selected-employees').val(selectedEmployees.join(','));
            $('#table-employee .employee-checkbox[value="' + id + '"]').prop('checked', false);
            refreshSelectedTable();
        });

        $('#table-employee tbody').on('click', 'tr', function(e) {
            if ($(e.target).is('input[type="checkbox"]')) return;

            let checkbox = $(this).find('.employee-checkbox');
            if (!checkbox.prop('disabled')) {
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });

        function updateParticipantCount() {
            const ids = $('#selected-employees').val();
            const count = ids ? ids.split(',').filter(id => id.trim() !== '').length : 0;
            $('#display-participant-count').val(count);
        }
    </script>
@endpush

<style>
    .page {
        min-height: 0 !important;
    }
</style>
