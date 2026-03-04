@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/committee-activity/edit" :breadcrumb-data="$committeeActivity->id" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('employee.committee-activity.update', $committeeActivity->id) }}"
                class="form-block" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="container py-2">
                    <x-section-form :label="__('Detail Kegiatan')" icon="fa-solid fa-user-circle" />

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text name="activity_date" type="date" :label="__('Tanggal Kegiatan')" :value="$committeeActivity->activity_date?->format('Y-m-d')"
                                :old="old('activity_date', $committeeActivity->activity_date?->format('Y-m-d'))" />
                        </div>
                        <div class="col-md-6">
                            {{-- <x-form.select name="related_field" :option="$relatedFields" :label="__('Bidang Terkait')" :value="$committeeActivity->related_field"
                                :old="old('related_field', $committeeActivity->related_field)" /> --}}
                            <label for="Bidang Terkait">{{ __('Bidang Terkait') }}</label>
                            <select id="related_field" name="related_field" class="form-select select2" required>
                                @foreach ($relatedFields as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('related_field', $committeeActivity->related_field) == $key ? 'selected' : '' }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text name="activity_type" :label="__('Jenis Kegiatan')" value="Kepanitiaan" disabled
                                :old="old('activity_type', $committeeActivity->activity_type)" />
                        </div>
                        <div class="col-md-6">
                            {{-- <x-form.select name="activity_name" :option="$activity->pluck('activity_name', 'activity_name')" :label="__('Nama Kegiatan')" :value="$committeeActivity->activity_name"
                                :old="old('activity_name', $committeeActivity->activity_name)" /> --}}
                            <label for="Nama Kegiatan">{{ __('Nama Kegiatan') }}</label>
                            <select id="activity_name" name="activity_name" class="form-select select2" required>
                                @foreach ($activity->pluck('activity_name', 'activity_name') as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('activity_name', $committeeActivity->activity_name) == $key ? 'selected' : '' }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text :value="$committeeActivity->responsible_person" :label="__('Penanggung Jawab')" :old="old('responsible_person', $committeeActivity->responsible_person)" readonly disabled />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text name="location" :label="__('Lokasi Kegiatan')" :value="$committeeActivity->location" :old="old('location', $committeeActivity->location)" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text type="hidden" name="employee_id" id="selected-employees" :value="$committeeActivity->employees->pluck('id')->join(',')"
                                readonly />
                            <x-form.input-group-button name="selected_employee_id" :label="__('Peserta Kegiatan')"
                                button-id="btn-choose-relation" button-label="<i class='fa-solid fa-search'></i>" readonly
                                data-bs-toggle="modal" data-bs-target="#pegawai" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text type="number" id="display-participant-count" :label="__('Jumlah Peserta')"
                                value="{{ $committeeActivity->participant_count }}" :old="old('participant_count', $committeeActivity->participant_count)" disabled />
                        </div>
                    </div>

                    <x-section-form :label="__('Ringkasan Kegiatan')" icon="fa-solid fa-user-circle" />

                    <div class="row">
                        <div class="col-md-12">
                            <x-form.text-area name="activity_summary" :label="__('Ulasan Kegiatan')" :value="$committeeActivity->activity_summary" rows="3"
                                :old="old('activity_summary', $committeeActivity->activity_summary)" />
                        </div>
                    </div>

                    <x-section-form :label="__('Dokumentasi & Lampiran Kegiatan')" icon="fa-solid fa-user-circle" />

                    <div class="row">
                        <div class="col-md-4">
                            <x-form.input-file name="photos[]" :label="__('Tambah Foto Kegiatan')" multiple id="photos-input" />
                            <small class="text-muted">Foto yang sudah ada tidak dihapus otomatis.</small>

                            @if ($committeeActivity->photos->isNotEmpty())
                                <div class="mt-2">
                                    <strong>Foto yang sudah diupload:</strong>
                                    <div class="mt-2" id="existing-photos-preview">
                                        @foreach ($committeeActivity->photos as $photo)
                                            <div class="d-flex align-items-center mb-1 py-1 px-2 border rounded bg-light"
                                                style="font-size:0.85rem;">
                                                <img src="{{ asset('storage/' . $photo->file_path) }}" class="me-2 border"
                                                    style="width:60px; height:60px; object-fit: cover;">
                                                <span class="flex-grow-1 text-truncate mx-1">{{ $photo->file_name }}</span>
                                                <button type="button" class="btn btn-sm btn-danger p-2 delete-document"
                                                    data-id="{{ $photo->id }}" data-type="photo">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-2" id="photos-preview"></div>
                        </div>

                        <div class="col-md-4">
                            <x-form.input-file name="sk[]" :label="__('Tambah Surat Keputusan (SK)')" multiple id="sk-input" />
                            <small class="text-muted">File lama tetap tersimpan jika tidak diganti.</small>

                            @if ($committeeActivity->skDocuments->isNotEmpty())
                                <div class="mt-2">
                                    <strong>SK yang sudah diupload:</strong>
                                    <div class="mt-2" id="existing-sk-preview">
                                        @foreach ($committeeActivity->skDocuments as $sk)
                                            <div class="d-flex align-items-center mb-1 py-1 px-2 border rounded bg-light"
                                                style="font-size:0.85rem;">
                                                <i class="fas fa-file-pdf me-2 text-danger" style="font-size:1.5rem;"></i>
                                                <span class="flex-grow-1 text-truncate mx-1">{{ $sk->file_name }}</span>
                                                <button type="button" class="btn btn-sm btn-danger p-2 delete-document"
                                                    data-id="{{ $sk->id }}" data-type="sk">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-2" id="sk-preview"></div>
                        </div>

                        <div class="col-md-4">
                            <x-form.input-file name="minutes[]" :label="__('Tambah Berita Acara')" multiple id="minutes-input" />
                            <small class="text-muted">File lama tetap tersimpan jika tidak diganti.</small>

                            @if ($committeeActivity->beritaAcara->isNotEmpty())
                                <div class="mt-2">
                                    <strong>Berita acara yang sudah diupload:</strong>
                                    <div class="mt-2" id="existing-minutes-preview">
                                        @foreach ($committeeActivity->beritaAcara as $minutes)
                                            <div class="d-flex align-items-center mb-1 py-1 px-2 border rounded bg-light"
                                                style="font-size:0.85rem;">
                                                <i class="fas fa-file-alt me-2 text-success"
                                                    style="font-size:1.5rem;"></i>
                                                <span
                                                    class="flex-grow-1 text-truncate mx-1">{{ $minutes->file_name }}</span>
                                                <button type="button" class="btn btn-sm btn-danger p-2 delete-document"
                                                    data-id="{{ $minutes->id }}" data-type="minutes">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-2" id="minutes-preview"></div>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary">{{ __('label.update') }}</button>
                        <a href="{{ route('employee.committee-activity.index') }}"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Pegawai Terpilih --}}
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

    {{-- Modal Pemilihan Pegawai --}}
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#related_field').select2();
            $('#activity_name').select2();
        });

        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset";
        $(document).ready(function() {
            if (error) setNotifInfo(error);
        });
    </script>

    <script>
        $(document).ready(function() {
            $('.delete-document').on('click', function() {
                const button = $(this);
                const documentId = button.data('id');
                const docType = button.data('type');

                if (!confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) return;

                $.ajax({
                    url: "{{ route('employee.committee-activity.document.destroy', ['document' => 'ID_PLACEHOLDER']) }}"
                        .replace('ID_PLACEHOLDER', documentId),
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        // Hapus elemen dari DOM
                        if (docType === 'photo') {
                            button.closest('.position-relative').remove();
                        } else {
                            button.closest('div').remove(); // atau sesuaikan parent
                        }
                        alert('Dokumen berhasil dihapus.');
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.error || 'Gagal menghapus dokumen.');
                    }
                });
            });
        });

        // Simpan file baru yang dipilih (belum diupload)
        let newFiles = {
            photos: [],
            sk: [],
            minutes: []
        };

        function previewNewFiles(inputId, previewId, fileType, key) {
            const container = document.getElementById(previewId);
            container.innerHTML = '';

            (newFiles[key] || []).forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center mb-1 py-1 px-2 border rounded bg-light';
                div.style.fontSize = '0.85rem';

                if (fileType === 'image' && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'me-2 border';
                        img.style.width = '60px';
                        img.style.height = '60px';
                        img.style.objectFit = 'cover';
                        div.insertBefore(img, div.firstChild);
                    };
                    reader.readAsDataURL(file);
                } else {
                    const icon = document.createElement('i');
                    icon.className = fileType === 'sk' ?
                        'fas fa-file-pdf me-2 text-danger' :
                        'fas fa-file-alt me-2 text-success';
                    icon.style.fontSize = '1.5rem';
                    div.appendChild(icon);
                }

                const nameSpan = document.createElement('span');
                nameSpan.textContent = file.name;
                nameSpan.className = 'flex-grow-1 text-truncate mx-1';
                div.appendChild(nameSpan);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-danger p-2';
                removeBtn.innerHTML = '<i class="bx bx-trash"></i>';
                removeBtn.onclick = () => {
                    newFiles[key].splice(index, 1);
                    updateFileInput(inputId, newFiles[key]);
                    previewNewFiles(inputId, previewId, fileType, key);
                };
                div.appendChild(removeBtn);

                container.appendChild(div);
            });
        }

        function updateFileInput(inputId, files) {
            const input = document.getElementById(inputId);
            const dt = new DataTransfer();
            files.forEach(file => dt.items.add(file));
            input.files = dt.files;
        }

        // Pasang event listener
        ['photos', 'sk', 'minutes'].forEach(key => {
            const inputId = `${key}-input`;
            const previewId = `${key}-preview`;
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', function(e) {
                    const newSelected = Array.from(e.target.files);
                    const existing = newFiles[key] || [];
                    const combined = [...existing, ...newSelected].filter((file, i, self) =>
                        i === self.findIndex(f => f.name === file.name && f.size === file.size)
                    );
                    newFiles[key] = combined;
                    previewNewFiles(inputId, previewId, key === 'photos' ? 'image' : key, key);
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

        // Load existing members (including responsible person)
        const existingEmployees = @json($existingEmployees);

        // Populate selectedEmployees and selectedData
        existingEmployees.forEach(emp => {
            selectedEmployees.push(emp.id.toString());
            selectedData[emp.id] = emp;
        });

        $('#selected-employees').val(selectedEmployees.join(','));

        // DataTable untuk modal pegawai
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
            data: existingEmployees,
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

        function refreshSelectedTable() {
            let dataArr = selectedEmployees.map(id => selectedData[id]);
            tableSelected.clear().rows.add(dataArr).draw();
            updateParticipantCount();
        }

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
