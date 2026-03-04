@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/department/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('hr.department.store') }}" class="form-block">
                @csrf
                <input type="hidden" name="employee_id" id="selected-employees">
                <div class="row mb-1">
                    <div class="col-md-12">
                        <x-form.input-group-button name="selected_employee_id" :label="__('label.choose_name')" :old="old('selected_employee_id')"
                            button-id="btn-choose-relation" button-label="<i class='fa-solid fa-search'></i>" readonly
                            data-bs-toggle="modal" data-bs-target="#pegawai" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <x-form.input-text label="{{ __('label.name') }}" id="selected-employee-name" readonly disabled />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <x-form.select label="{{ __('label.position') }}" name="position_id" :option="$position->pluck('name', 'id')"
                            :old="old('position_id')" />
                    </div>
                </div>

                <x-form.button-submit :cancel-route="route('hr.department.index')" />
            </form>
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
            if (error != "")
                setNotifInfo(error)
        })
    </script>

    <script>
        let selectedEmployees = [];
        let selectedData = {};

        // DataTable modal
        let table = $("#table-employee").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hr.department.data-employee') }}",
                type: "POST",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                }
            },
            columns: [{
                    class: "align-middle",
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    data: "name",
                    class: "align-middle clickable"
                },
                {
                    data: "phone",
                    class: "align-middle clickable"
                },
                {
                    data: "gender",
                    class: "align-middle clickable"
                },
                {
                    data: "task_main",
                    class: "align-middle clickable"
                },
            ],
            createdRow: function(row, data, dataIndex) {
                $(row).attr('data-id', data.id);
            }
        });

        $('#table-employee tbody').on('click', '.clickable', function() {
            const row = $(this).closest('tr');
            const id = row.data('id');
            const name = table.row(row).data().name;

            $('#selected-employees').val(id);

            $('#selected-employee-name').val(name);

            $('#pegawai').modal('hide');
        });
    </script>
@endpush
