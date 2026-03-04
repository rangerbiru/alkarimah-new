@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/savings/mutation"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="row">
            <div class="col-sm-6 col-md-4">
                <x-form.input-text
                    id="student"
                    :placeholder="__('string.type_nis_name_to_search') . '...'"
                />
            </div>
            <div class="col-sm-6 col-md-4">
                <x-form.date-picker
                    id-start="start-date"
                    id-end="end-date"
                    name-start="start"
                    name-end="end"
                    picker-type="date-range"
                />
            </div>
            <div class="col-sm-6 col-md-4">
                <button type="button" id="btn-search" class="btn btn-secondary">
                    <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                </button>
            </div>
        </div>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="table-mutation">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th style="width: 160px;">{{ __('label.date') }}</th>
                        <th>{{ __('label.description') }}</th>
                        <th>{{ __('label.debit') }}</th>
                        <th>{{ __('label.credit') }}</th>
                        <th>{{ __('label.balance') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <div id="start" class="text-center my-4">
                <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                    <b>{{ __('string.type_nis_name_to_search') }}</b><br />{{ __('string.search_savings_mutation_info') }}
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

let student = ""
let start_date = ""
let end_date = ""
let datatable = false

$(document).ready(function() {
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

    $("#btn-search").click(function() {
        student = $("#student").val()
        start_date = $("#start-date").val()
        end_date = $("#end-date").val()

        load()
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
        window.LaravelDataTables["table-mutation"].ajax.reload()
    } else {
        datatable = true
        $("#start").hide()
        $("#button-download").show()

        window.LaravelDataTables["table-mutation"] = $("#table-mutation").DataTable({
            language: {
                search: "",
                searchPlaceholder: `${label_search}...`,
                lengthMenu: "_MENU_ Data",
                emptyTable: label_nodata
            },
            ajax:
            {
                url: "{{ route('finance.savings.datatable.mutation') }}",
                type: "POST",
                data: (d) => {
                    d.student = student
                    d.start_date = start_date
                    d.end_date = end_date

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
                    render: (data, type, row, meta) => dateFormat(row.created_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => htmlEntities(row.flag_name)
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.debit)}`
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.credit)}`
                },
                {
                    class: "align-top",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.balance)}`
                },
            ]
        })

        $($.fn.dataTable.tables(true)).css('width', '100%')
    }
}
</script>
@endpush
