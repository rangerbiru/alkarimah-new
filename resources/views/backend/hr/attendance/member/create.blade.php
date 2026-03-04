@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/attendance/group/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('hr.attendance.member.store') }}">
                @csrf
                <div class="container py-2">
                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.input-text type="hidden" name="employee_id" id="selected-employees" :old="old('employee_id')"
                                readonly />
                            <x-form.input-group-button name="selected_employee_id" :label="__('label.name')" :old="old('selected_employee_id')"
                                button-id="btn-choose-relation" button-label="<i class='fa-solid fa-search'></i>" readonly
                                data-bs-toggle="modal" data-bs-target="#pegawai" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <x-form.select label="{{ __('label.group_name') }}" id="groupName" name="attendance_group_id"
                                :option="$groupAttendance->pluck('group_name', 'id')" :old="old('attendance_group_id')" />
                        </div>
                    </div>

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('hr.attendance.member.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
        </div>
        </form>
    </div>

    <div class="card">
        <div class="row p-2">
            <div class="col-md-12">
                <h5 class="text-center py-2">Pegawai yang dipilih</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="table-selected">
                        <thead>
                            <tr>
                                <th>No</th>
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
                        <table class="table" id="table-employee">
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
        let selectedEmployees = [];
        let selectedData = {};

        // DataTable modal
        let table = $("#table-employee").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hr.attendance.member.data-employee') }}",
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
                        return `<input type="checkbox" class="employee-checkbox form-check-input" value="${row.id}">`;
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
                    render: (d, t, r) =>
                        `<button class="btn btn-sm btn-danger btn-remove" data-id="${r.id}">Hapus</button>`
                }
            ]
        });

        // Event ketika checkbox berubah
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
        }

        // Hapus dari daftar pilihan via tombol
        $('#table-selected').on('click', '.btn-remove', function() {
            let id = $(this).data('id').toString();
            selectedEmployees = selectedEmployees.filter(e => e !== id);
            delete selectedData[id];

            $('#selected-employees').val(selectedEmployees.join(','));

            // Uncheck di modal juga
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
    </script>
@endpush

<style>
    .page {
        min-height: 0 !important;
    }
</style>
