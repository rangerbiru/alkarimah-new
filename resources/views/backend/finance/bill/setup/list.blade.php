@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/bill/setup"
/>
@endsection

@section('content')
<div class="card card-nav">
    <div class="card-body">
        @include($path . 'menu')

        <div class="card-content">
            <div class="row mb-3">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="year"
                        :option="$years"
                        :old="$year->id"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="class"
                        :option="$classes"
                        :data-placeholder="__('label.choose') . ' ' . __('label.class')"
                        data-allow-clear="true"
                    />
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-student">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('label.no') }}</th>
                            <th style="width: 120px;">{{ __('label.nis') }}</th>
                            <th style="width: 250px;">{{ __('label.name') }}</th>
                            <th style="width: 120px;">{{ __('label.class') }}</th>
                            <th>{{ __('label.bill') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
let year = "{{ $year->id }}"
let classroom = ""

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
            url: "{{ route('finance.bill.datatable.list') }}",
            type: "POST",
            data: (d) => {
                d.year = year
                d.class = classroom

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
                render: (data, type, row, meta) => {
                    let html = "<table class='table-padding'>"

                    row.bills.forEach((b) => {
                        html += `<tr>
                            <td class="px-0 pt-1 pb-1" style="width: 20px;"><i class="${b.icon} set-tooltip" title="${b.period}"></i></td>
                            <td class="pt-1 pb-1">
                                ${b.name}
                                <a href="javascript:void(0)" class="btn-delete text-muted set-tooltip" title="{{ __('label.delete') }}" data-bill="${b.id}" data-student="${row.id}">
                                    <small><i class="bx bx-trash"></i></small>
                                </a>
                            </td>
                            <td class="pt-1 pb-1" style="width: 130px;">Rp. ${moneyFormat(b.nominal)}</td>
                        </tr>`
                    })

                    html += "</table>"

                    return html
                }
            },
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#year").change(function() {
        year = $(this).val()
        window.LaravelDataTables["table-student"].ajax.reload()
    })

    $("#class").change(function() {
        classroom = $(this).val()
        window.LaravelDataTables["table-student"].ajax.reload()
    })

    $("#table-student").on("click", ".btn-delete", function() {
        const bill = $(this).data("bill")
        const student = $(this).data("student")

        Swal.fire({
            icon: "warning",
            title: label_confirmation,
            text: string_confirm_delete,
            showCancelButton: true,
            confirmButtonText: label_yes,
            cancelButtonText: label_cancel,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const formData = {year, bill, student }

                $.ajax({
                    type: "POST",
                    url: "{{ route('finance.bill.destroy') }}",
                    data: formData,
                    dataType: "json",
                    success: function (response) {
                        if (response.status) {
                            window.LaravelDataTables["table-student"].ajax.reload()
                            setNotifSuccess(response.message, false)
                        } else
                            setNotifInfo(response.message)
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        ajaxError(xhr.status)
                    }
                })

                return true
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.close()
            }
        })
    })
});
</script>
@endpush
