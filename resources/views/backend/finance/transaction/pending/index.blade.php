@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/pending"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="row">
            <div class="col-sm-6 col-md-4">
                <x-form.select
                    id="type"
                    :option="$types"
                    :data-placeholder="__('label.all_transaction')"
                    data-allow-clear="true"
                />
            </div>
            <div class="col-sm-6 col-md-8">
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
            <table class="table" id="table-pending">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.transaction_number') }}</th>
                        <th>{{ __('label.transaction_type') }}</th>
                        <th>{{ __('label.student_or_parent_name') }}</th>
                        <th>{{ __('label.total') }}</th>
                        <th style="width: 35px;">#</th>
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
const type_topup = "{{ $type_topup }}"
const type_bill = "{{ $type_bill }}"
let type = ""

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-pending"] = $("#table-pending").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.transaction.datatable.pending') }}",
            type: "POST",
            data: (d) => {
                d.type = type

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
                render: (data, type, row, meta) => {
                    let html = ""

                    if (row.flag == type_bill) {
                        let url_show = "{{ route('finance.transaction.bill.show', 0) }}"
                        url_show = url_show.replace("0", row.encrypted_id)

                        html += `<a href="${url_show}" class="fw-bold set-tooltip text-${row.flag_detail.color}" title="{{ __('label.bill_detail') }}">
                            ${row.number}
                        </a>`
                    } else {
                        html += `<span class="fw-bold text-${row.flag_detail.color}">${row.number}</span>`
                    }

                    html += `<div class="mt-3">
                        <small><b>{{ __('label.transaction_date') }}</b></small><br />
                        ${dateFormat(row.dates, "{dd} {mmm} {yyyy}")}
                    </div>`

                    return html
                }
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `${htmlEntities(row.flag_detail.name)}
                    <div class="mt-3">
                        <small><b>{{ __('label.payment_method') }}</b></small><br />
                        ${row.method_name}
                    </div>`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => {
                    if (row.flag == type_topup) {
                        return `${htmlEntities(row.parent.name)}
                            <div class="mt-3">
                                <small><b>{{ __('label.phone_number') }}</b></small><br />
                                ${phoneFormat(row.parent.phone)}
                            </div>`
                    } else {
                        return `${htmlEntities(row.student.name)}
                            <div class="mt-3">
                                <small><b>{{ __('label.nis') }}</b></small><br />
                                ${htmlEntities(row.student.nis)}
                            </div>`
                    }
                }
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => {
                    let html = ""

                    if (row.donation == 0 && row.unique_code == 0) {
                        html = `Rp. ${moneyFormat(row.total)}`
                    } else {
                        html = "<table>"

                        if (row.donation > 0 || row.unique_code > 0) {
                            html += `<tr>
                                <td class="ps-0 pb-0 pt-0">{{ __('label.subtotal') }}</td>
                                <td class="ps-0 pb-0 pt-0">Rp.</td>
                                <td class="ps-0 pb-0 pt-0 text-end">${moneyFormat(row.subtotal)}</td>
                            </tr>`
                        }

                        if (row.donation > 0) {
                            html += `<tr>
                                <td class="ps-0 pb-0">{{ __('label.scholarship') }}</td>
                                <td class="ps-0 pb-0">Rp.</td>
                                <td class="ps-0 pb-0 text-end">${moneyFormat(row.donation)}</td>
                            </tr>`
                        }

                        if (row.unique_code > 0) {
                            html += `<tr>
                                <td class="ps-0 pb-0">{{ __('label.unique_code') }}</td>
                                <td class="ps-0 pb-0">Rp.</td>
                                <td class="ps-0 pb-0 text-end">${moneyFormat(row.unique_code)}</td>
                            </tr>`
                        }

                        html += `<tr>
                            <td class="ps-0 pb-0 fw-bold">{{ __('label.total') }}</td>
                            <td class="ps-0 pb-0 fw-bold">Rp.</td>
                            <td class="ps-0 pb-0 fw-bold text-end">${moneyFormat(row.total)}</td>
                        </tr>`
                    }

                    return html
                }
            },
            {
                class: "align-top text-center",
                searchable: false,
                render: function(data, type, row) {
                    let html = ""

                    if (row.flag == type_bill) {
                        let url_show = "{{ route('finance.transaction.bill.show', 0) }}"
                        url_show = url_show.replace("0", row.encrypted_id)

                        html += `<a href="${url_show}" class="btn btn-secondary btn-xs set-tooltip mb-1" title="{{ __('label.bill_detail') }}">
                            <i class="fa-solid fa-search"></i>
                        </a><br />`
                    }

                    html += `<button type="button" class="btn btn-success btn-xs btn-pay set-tooltip mb-1" title="{{ __('label.already_paid') }}?" data-id="${row.encrypted_id}" data-unique-code="${row.unique_code}">
                        <i class="fa-solid fa-check"></i>
                    </button><br />
                    <button type="button" class="btn btn-danger btn-xs btn-cancel set-tooltip" title="{{ __('label.cancel_it') }}" data-id="${row.encrypted_id}">
                        <i class="fa-solid fa-times"></i>
                    </button>`

                    return html
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#type").change(function() {
        type = $(this).val()
        window.LaravelDataTables["table-pending"].ajax.reload()
    })

    $("#btn-search").click(function() {
        type = $("#type").val()
        window.LaravelDataTables["table-pending"].ajax.reload()
    })

    $("#table-pending").on("click", ".btn-pay", function() {
        updateStatusPaid($(this).data("id"), "paid", $(this).data("unique-code"))
    })

    $("#table-pending").on("click", ".btn-cancel", function() {
        updateStatusCancel($(this).data("id"), "cancel")
    })
})

function updateStatusPaid(id, status, unique_code)
{
    Swal.fire({
        icon: "warning",
        title: label_confirmation,
        text: "{{ __('string.confirm_already_paid') }}",
        showCancelButton: true,
        confirmButtonText: label_yes,
        cancelButtonText: label_cancel,
        showLoaderOnConfirm: true,
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                 html: `<h5 class="mt-3">{{ __('label.unique_code') }}</h5>
                    <span style="font-size: 16px;">Apakah Orang Tua melakukan Transfer beserta Kode Unik nya?</span>`,
                input: "radio",
                inputOptions: {
                    1: "YA",
                    0: "TIDAK",
                },
                inputValue: 1,
                showCancelButton: true,
                confirmButtonText: "<i class='fa-solid fa-check-circle'></i> &nbsp;SUBMIT",
                cancelButtonText: label_cancel,
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = { id, status, unique_code: result.value }

                    $.ajax({
                        type: "POST",
                        url: "{{ route('finance.transaction.update.status') }}",
                        data: formData,
                        dataType: "json",
                        success: function (response) {
                            window.LaravelDataTables["table-pending"].ajax.reload()
                            setNotifSuccess(response.message, false)
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            ajaxError(xhr.status)
                        }
                    })

                    Swal.close()
                }
            })
        }
    })
}

function updateStatusCancel(id, status)
{
    Swal.fire({
        icon: "warning",
        title: label_confirmation,
        text: "{{ __('string.confirm_cancel_transaction') }}",
        showCancelButton: true,
        confirmButtonText: label_yes,
        cancelButtonText: label_cancel,
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const formData = { id, status }

            $.ajax({
                type: "POST",
                url: "{{ route('finance.transaction.update.status') }}",
                data: formData,
                dataType: "json",
                success: function (response) {
                    window.LaravelDataTables["table-pending"].ajax.reload()
                    setNotifSuccess(response.message, false)
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    ajaxError(xhr.status)
                }
            })
        }
    }).then((result) => {
        if (result.isConfirmed)
            Swal.close()
    })
}
</script>
@endpush
