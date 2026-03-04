@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/employee" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-section-page-action :label="$title" :icon="$icon" :count="$count" :create-route="route('hr.employee.create')" />

            <div class="table-responsive">
                <table class="table" id="table-employee">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.nip') }}</th>
                            <th>{{ __('label.nik') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.task_main') }}</th>
                            <th>{{ __('label.phone_number') }}</th>
                            <th>{{ __('label.status') }}</th>
                            <th style="width: 105px;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {}
            window.LaravelDataTables["table-employee"] = $("#table-employee").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('hr.employee.datatable') }}",
                    type: "POST"
                },
                processing: true,
                serverSide: true,
                deferRender: true,
                ordering: false,
                aLengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                drawCallback: function() {
                    $(".set-tooltip").tooltip({
                        container: "body"
                    })
                },
                columns: [{
                        class: "align-middle",
                        width: "50px",
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => (row.nip == null) ? "-" : htmlEntities(row
                            .nip)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => (row.nik == null) ? "-" : htmlEntities(row
                            .nik)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.task_main ? row.task_main :
                            '-')
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => phoneFormat(row.phone)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => row.status_badge
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_edit = "{{ route('hr.employee.edit', 0) }}"
                            let url_destroy = "{{ route('hr.employee.destroy', 0) }}"
                            let url_rights = "{{ route('hr.employee.rights', 0) }}"

                            url_edit = url_edit.replace("0", row.encrypted_id)
                            url_destroy = url_destroy.replace("0", row.encrypted_id)
                            url_rights = url_rights.replace("0", row.encrypted_id)

                            return `<a href="${url_rights}" class="btn btn-info btn-xs set-tooltip" title="{{ __('label.access_rights') }}">
                            <i class="ti ti-device-desktop-cog"></i>
                        </a>
                        <a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                            <i class="bx bx-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', false, 'table-employee')">
                            <i class="bx bx-trash"></i>
                        </a>`
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });
    </script>
@endpush
