@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/student/history-displacement"
    :breadcrumb-data="$student->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <table class="table-padding mb-2">
            <tr>
                <td class="fw-bold">{{ __('label.name') }}</td>
                <td class="divide">:</td>
                <td>{{ $student->name }}</td>
            </tr>
            <tr>
                <td class="fw-bold">{{ __('label.nis') }}</td>
                <td class="divide">:</td>
                <td>{{ $student->nis }}</td>
            </tr>
            <tr>
                <td class="fw-bold">{{ __('label.class') }}</td>
                <td class="divide">:</td>
                <td>{{ $student->class->name }}</td>
            </tr>
        </table>

        <x-section-page-action
            :label="$title"
            :icon="$icon"
            :count="$count"
        />

        <div class="table-responsive">
            <table class="table table-bordered" id="table-student">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center">{{ __('label.no') }}</th>
                        <th colspan="2" class="text-center">{{ __('label.before') }}</th>
                        <th colspan="2" class="text-center">{{ __('label.after') }}</th>
                        <th rowspan="2">{{ __('label.updated_date') }}</th>
                        <th rowspan="2">{{ __('label.updated_by') }}</th>
                    </tr>
                    <tr>
                        <th class="text-center">{{ __('label.class') }}</th>
                        <th class="text-center">{{ __('label.nis') }}</th>
                        <th class="text-center">{{ __('label.class') }}</th>
                        <th class="text-center">{{ __('label.nis') }}</th>
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
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-student"] = $("#table-student").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('academic.student.datatable.history-displacement') }}",
            type: "POST",
            data: (d) => {
                d.student = "{{ $student->id }}"
                return d
            }
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        ordering: false,
        aLengthMenu: [[10, 25, 50, 100],[10, 25, 50, 100]],
        drawCallback: function() {
            $(".set-tooltip").tooltip({
                container: "body"
            })
        },
        columns: [
            {
                class: "align-middle text-center",
                width: "50px",
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-middle text-center",
                render: (data, type, row, meta) => htmlEntities(row.class_before.name)
            },
            {
                class: "align-middle text-center",
                render: (data, type, row, meta) => htmlEntities(row.before_nis)
            },
            {
                class: "align-middle text-center",
                render: (data, type, row, meta) => htmlEntities(row.class_after.name)
            },
            {
                class: "align-middle text-center",
                render: (data, type, row, meta) => htmlEntities(row.after_nis)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => dateFormat(row.created_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.creator.name)
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
})
</script>
@endpush
