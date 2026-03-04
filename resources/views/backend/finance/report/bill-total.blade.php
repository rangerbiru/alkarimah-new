@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/report/bill-total"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="year"
                    :label="__('label.school_year')"
                    :option="$years"
                    :data-placeholder="__('label.choose_school_year')"
                    :old="$year->id"
                />
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="education-level"
                    :label="__('label.level_education')"
                    :option="$education_levels"
                    :data-placeholder="__('label.choose_education_level')"
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="class-level"
                    :label="__('label.level_class')"
                    :option="[]"
                    :data-placeholder="__('label.choose_class_level')"
                    loading
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="class"
                    :label="__('label.class')"
                    :option="[]"
                    :data-placeholder="__('label.choose_class')"
                    loading
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
            <div class="col-sm-6 col-md-3">
                <div class="alert alert-outline-info">
                    <div class="clearfix">
                        <div class="float-end"><i class="bx bx-credit-card-front" style="font-size: 16px;"></i></div>

                        <b>Total : Rp. <span id="total">0</span></b>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" id="table-report">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.nis') }}</th>
                        <th>{{ __('label.student_name') }}</th>
                        <th>{{ __('label.class') }}</th>
                        <th>{{ __('label.total') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <div id="start" class="text-center my-4">
                <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                    <b>{{ __('string.search_education_class') }}</b><br />{{ __('string.search_bill_report_info') }}
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

let year = "{{ $year->id }}"
let education_level = ""
let class_level = ""
let classroom = ""
let datatable = false

$(document).ready(function() {
    $("#card-info").hide()

    $("#year").change(function() {
        year = $(this).val()
        load()
    })

    $("#education-level").change(function() {
        education_level = $(this).val()
        class_level = $("#class-level").val()

        optionClassLevel()
        load()
    })

    $("#class-level").change(function() {
        class_level = $(this).val()

        optionClass()
        load()
    })

    $("#class").change(function() {
        classroom = $(this).val()
        load()
    })

    $("#btn-search").click(function() {
        year = $("#year").val()
        education_level = $("#education-level").val()
        class_level = $("#class-level").val()
        classroom = $("#class").val()

        load()
    })

    $("#btn-download-excel").click(function() {
        window.location = `{{ route('finance.report.download.excel.bill-total') }}?year=${year}&education=${education_level}&class=${class_level}&classroom=${classroom}`
    })

    $("#btn-download-pdf").click(function() {
        window.location = `{{ route('finance.report.download.pdf.bill-total') }}?year=${year}&education=${education_level}&class=${class_level}&classroom=${classroom}`
    })
})

function load()
{
    if (class_level == "")
        return false

    const formData = {year, education: education_level, class: class_level, classroom}

    $.ajax({
        type: "POST",
        url: "{{ route('finance.report.get.total-bill') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            $("#total").html(moneyFormat(response.data.total))
        }
    })

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
                url: "{{ route('finance.report.datatable.bill-total') }}",
                type: "POST",
                data: (d) => {
                    d.year = year
                    d.education = education_level
                    d.class = class_level
                    d.classroom = classroom

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
                    render: (data, type, row, meta) => htmlEntities(row.nis)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.name)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.class.name)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
                },
            ]
        })

        $($.fn.dataTable.tables(true)).css('width', '100%')
    }
}

function optionClassLevel()
{
    if (education_level != "") {
        $("#loading-class-level").show()

        const formData = {level: education_level}

        $.ajax({
            type: "POST",
            url: "{{ route('academic.class.get.option.level') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-class-level").hide()
                $("#class-level").html(response.option).trigger("change.select2")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}

function optionClass()
{
    if (class_level != "") {
        $("#loading-class").show()

        const formData = {level: class_level}

        $.ajax({
            type: "POST",
            url: "{{ route('academic.class.get.option.level-class') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-class").hide()
                $("#class").html(response.option).trigger("change.select2")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}
</script>
@endpush
