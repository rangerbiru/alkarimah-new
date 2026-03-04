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
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="year"
                    :option="$years"
                    :data-placeholder="__('label.choose_school_year')"
                    :old="$year->id"
                />
            </div>
            <div class="col-sm-6 col-md-4">
                <x-form.input-text
                    id="student"
                    :placeholder="__('string.type_nis_name_to_search') . '...'"
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="bill"
                    :option="[]"
                    :data-placeholder="__('label.choose_bill')"
                    loading
                />
            </div>
            <div class="col-sm-6 col-md-2">
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
                        <th style="width: 120px;">{{ __('label.school_year') }}</th>
                        <th>{{ __('label.bill_name') }}</th>
                        <th>{{ __('label.student_name') }}</th>
                        <th>{{ __('label.class') }}</th>
                        <th>{{ __('label.nominal') }}</th>
                        <th>{{ __('label.status') }}</th>
                        <th>{{ __('label.payment_date') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <div id="start" class="text-center my-4">
                <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                    <b>{{ __('string.type_nis_name_to_search') }}</b><br />{{ __('string.search_bill_report_info') }}
                </h6>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.css') }}" rel="stylesheet" />
@endpush

@push('scripts')
<script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.js') }}"></script>

<script>
window.LaravelDataTables = window.LaravelDataTables || {}

const status_paid = "{{ $status_paid }}"
let year = "{{ $year->id }}"
let student = ""
let bill = ""
let datatable = false

$(document).ready(function() {
    optionBill()

    $("#button-download").hide()

    $("#student").autocomplete({
        source: `{{ route('academic.student.get.autocomplete') }}`,
        minLength: 2,
        select: (event, ui) => search(ui.item.value)
    }).keyup(function() {
        const keyboard = event.which || event.keyCode

        if (keyboard == 13) {
            student = $("#student").val()
            load()
        }
    }).focus(function() {
        $(this).select()
    })

    $("#year").change(function() {
        year = $(this).val()
        student = $("#student").val()

        optionBill()
        load()
    })

    $("#bill").change(function() {
        student = $("#student").val()
        bill = $("#bill").val()

        load()
    })

    $("#btn-search").click(function() {
        year = $("#year").val()
        student = $("#student").val()
        bill = $("#bill").val()

        load()
    })

    $("#btn-download-excel").click(function() {
        window.location = `{{ route('finance.report.download.excel.bill-student') }}?year=${year}&student=${student}&bill=${bill}`
    })

    $("#btn-download-pdf").click(function() {
        window.location = `{{ route('finance.report.download.pdf.bill-student') }}?year=${year}&student=${student}&bill=${bill}`
    })
})

function search(value)
{
    $("#student").blur()
    student = value
    load()
}

function load()
{
    if (student == "")
        return false

    if (datatable) {
        window.LaravelDataTables["table-report"].ajax.reload()
    } else {
        datatable = true
        $("#start").hide()
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
                url: "{{ route('finance.report.datatable.bill-student') }}",
                type: "POST",
                data: (d) => {
                    d.year = year
                    d.student = student
                    d.bill = bill

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
                    render: (data, type, row, meta) => row.year
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.bill_name)
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
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => row.status_badge
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => (row.status == status_paid) ? dateFormat(row.transaction.paid_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB") : "-"
                },
            ]
        })

        $($.fn.dataTable.tables(true)).css('width', '100%')
    }
}

function optionBill()
{
    if(year != ""){
        $("#loading-bill").show()

        const formData = {year}

        $.ajax({
            type: "POST",
            url: "{{ route('finance.report.get.option.bill') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-bill").hide()
                $("#bill").html(response.option).trigger("change.select2")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}
</script>
@endpush
