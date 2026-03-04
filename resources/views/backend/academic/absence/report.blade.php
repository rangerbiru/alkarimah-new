@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/absence/report"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="month"
                    :label="__('label.month')"
                    :option="$months"
                    :old="date('n')"
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="year"
                    :label="__('label.year')"
                    :option="$years"
                    :old="date('Y')"
                />
            </div>
        </div>

        <button type="button" id="btn-search" class="btn btn-secondary">
            <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
        </button>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div id="card-info" class="row mb-3">
            <div class="col-sm-6 col-md-9">
                <button type="button" id="btn-download-excel" class="btn btn-success label-btn">
                    <i class="fa-solid fa-file-excel label-btn-icon me-2"></i>
                    DOWNLOAD EXCEL
                </button>
                <button type="button" id="btn-download-pdf" class="btn btn-danger label-btn">
                    <i class="fa-solid fa-file-pdf label-btn-icon me-2"></i>
                    DOWNLOAD PDF
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" id="table-report">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th style="width: 120px;">{{ __('label.nis') }}</th>
                        <th>{{ __('label.name') }}</th>
                        <th style="width: 120px;">{{ __('label.class') }}</th>
                        <th>{{ __('label.absence_type') }}</th>
                        <th style="width: 160px;">{{ __('label.date') }}</th>
                        <th style="width: 70px;">{{ __('label.status') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <div id="start" class="text-center my-4">
                <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                    <b>{{ __('string.choose_month_year') }}</b><br />{{ __('string.search_absence_report_info') }}
                </h6>
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
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
window.LaravelDataTables = window.LaravelDataTables || {}
let year = "{{ date('Y') }}"
let month = "{{ date('n') }}"
let datatable = false

$(document).ready(function() {
    $("#card-info").hide()
    load()

    $("#month").change(function() {
        month = $(this).val()
        load()
    })

    $("#year").change(function() {
        year = $(this).val()
        load()
    })

    $("#btn-search").click(function() {
        year = $("#year").val()
        month = $("#month").val()

        load()
    })

    $("#btn-download-excel").click(function() {
        window.location = `{{ route('academic.absence.download.excel.report') }}?year=${year}&month=${month}`
    })

    $("#btn-download-pdf").click(function() {
        window.location = `{{ route('academic.absence.download.pdf.report') }}?year=${year}&month=${month}`
    })
})

function load()
{
    if (year == "" || month == "")
        return false

    if (datatable) {
        window.LaravelDataTables["table-report"].ajax.reload()
    } else {
        datatable = true
        $("#start").hide()
        $("#card-info").show()

        window.LaravelDataTables["table-report"] = $("#table-report").DataTable({
            language: {
                search: "",
                searchPlaceholder: `${label_search}...`,
                lengthMenu: "_MENU_ Data",
                emptyTable: label_nodata
            },
            ajax:
            {
                url: "{{ route('academic.absence.datatable.report') }}",
                type: "POST",
                data: (d) => {
                    d.year = year
                    d.month = month

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
                    class: "align-top",
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.student.nis)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.student.name)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.student.class.name)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.absence.type.name)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => `${dateFormat(row.absence.dates, "{dd} {mmm} {yyyy}")}, ${dateFormat(row.absence.created_at, "{hh}:{ii} WIB")}`
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => row.status_badge
                },
            ]
        })

        $($.fn.dataTable.tables(true)).css('width', '100%')
    }
}
</script>
@endpush
