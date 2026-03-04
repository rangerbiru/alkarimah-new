@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/report/bill-student"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="row">
            <div class="col-sm-6 col-md-4">
                <x-form.date-picker
                    id-start="start"
                    id-end="end"
                    name-start="start"
                    name-end="end"
                    picker-type="date-range"
                    :old="$filter->start"
                    :old-end="$filter->end"
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="donatur"
                    :option="$donaturs"
                    :data-placeholder="__('label.choose_donatur')"
                />
            </div>
            <div class="col-sm-6 col-md-5">
                <button type="button" id="btn-search" class="btn btn-secondary">
                    <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                </button>
            </div>
        </div>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div id="button-download" class="mb-3">
            <button type="button" id="btn-download-excel" class="btn btn-success label-btn">
                <i class="fa-solid fa-file-excel label-btn-icon me-2"></i>
                DOWNLOAD EXCEL
            </button>
            <button type="button" id="btn-download-pdf" class="btn btn-danger label-btn">
                <i class="fa-solid fa-file-pdf label-btn-icon me-2"></i>
                DOWNLOAD PDF
            </button>
        </div>

        <div class="table-responsive">
            <table class="table" id="table-report">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.donatur_name') }}</th>
                        <th>{{ __('label.nis') }}</th>
                        <th>{{ __('label.student_name') }}</th>
                        <th>{{ __('label.transaction_number') }}</th>
                        <th>{{ __('label.payment_date') }}</th>
                        <th>{{ __('label.scholarship_nominal') }}</th>
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
window.LaravelDataTables = window.LaravelDataTables || {}

let start_date = "{{ $filter->start }}"
let end_date = "{{ $filter->end }}"
let donatur = ""
let datatable = false

$(document).ready(function() {
    $("#button-download").hide()
    load()

    $("#donatur").change(function() {
        donatur = $(this).val()

        load()
    })

    $("#btn-search").click(function() {
        start_date = $("#start").val()
        end_date = $("#end").val()
        donatur = $("#donatur").val()

        load()
    })

    $("#btn-download-excel").click(function() {
        window.location = `{{ route('finance.report.download.excel.donation') }}?start_date=${start_date}&end_date=${end_date}&donatur=${donatur}`
    })

    $("#btn-download-pdf").click(function() {
        window.location = `{{ route('finance.report.download.pdf.donation') }}?start_date=${start_date}&end_date=${end_date}&donatur=${donatur}`
    })
})

function load()
{
    if (start_date == "" || end_date == "")
        return false

    if (datatable) {
        window.LaravelDataTables["table-report"].ajax.reload()
    } else {
        datatable = true
        $("#button-download").show()

        window.LaravelDataTables["table-report"] = $("#table-report").DataTable({
            language: {
                search: "",
                searchPlaceholder: `${label_search}...`,
                lengthMenu: "_MENU_ Data",
                emptyTable: label_nodata
            },
            ajax:
            {
                url: "{{ route('finance.report.datatable.donation') }}",
                type: "POST",
                data: (d) => {
                    d.start_date = start_date
                    d.end_date = end_date
                    d.donatur = donatur

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
                    render: (data, type, row, meta) => htmlEntities(row.donation.name)
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
                    render: (data, type, row, meta) => row.transaction.number
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => dateFormat(row.paid_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.nominal)}`
                },
            ]
        })

        $($.fn.dataTable.tables(true)).css('width', '100%')
    }
}
</script>
@endpush
