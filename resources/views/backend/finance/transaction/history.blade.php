@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/history"
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
            <div class="col-sm-6 col-md-4">
                <x-form.select
                    id="type"
                    :option="$types"
                    :data-placeholder="__('label.all_transaction')"
                    data-allow-clear="true"
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
            <table class="table" id="table-history">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.transaction_number') }}</th>
                        <th>{{ __('label.transaction_type') }}</th>
                        <th>{{ __('label.student_or_parent_or_person') }}</th>
                        <th>{{ __('label.total') }}</th>
                        <th>{{ __('label.payment') }}</th>
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
const type_withdrawal = "{{ $type_withdrawal }}"

let start_date = "{{ $filter->start }}"
let end_date = "{{ $filter->end }}"
let type = ""

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-history"] = $("#table-history").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.transaction.datatable.history') }}",
            type: "POST",
            data: (d) => {
                d.start_date = start_date
                d.end_date = end_date
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
                    let url_show = "{{ route('finance.transaction.bill.show', 0) }}"
                    url_show = url_show.replace("0", row.encrypted_id)

                    return `<a href="${url_show}" class="fw-bold set-tooltip text-${row.flag_detail.color}" title="{{ __('label.bill_detail') }}">
                        ${row.number}
                    </a>
                    <div class="mt-3">
                        <small><b>{{ __('label.transaction_date') }}</b></small><br />
                        ${dateFormat(row.dates, "{dd} {mmm} {yyyy}")}
                    </div>`
                }
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => htmlEntities(row.flag_detail.name)
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
                    } else if (row.flag == type_withdrawal) {
                        return `${htmlEntities(row.person_responsible.name)}
                            <div class="mt-3">
                                <small><b>{{ __('label.phone_number') }}</b></small><br />
                                ${phoneFormat(row.person_responsible.phone)}
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
                class: "align-top",
                render: (data, type, row, meta) => `${htmlEntities(row.method_name)}
                    <div class="mt-3">
                        <small><b>{{ __('label.payment_date') }}</b></small><br />
                        ${dateFormat(row.paid_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")}
                    </div>`
            },
            {
                class: "align-top text-center",
                searchable: false,
                render: function(data, type, row) {
                    let url_show = "{{ route('finance.transaction.bill.show', 0) }}"
                    let url_print = "{{ route('finance.transaction.print', 0) }}"

                    url_show = url_show.replace("0", row.encrypted_id)
                    url_print = url_print.replace("0", row.encrypted_id)

                    return `<a href="${url_show}" class="btn btn-secondary btn-xs set-tooltip mb-1" title="{{ __('label.bill_detail') }}" target="_blank">
                            <i class="bx bx-search"></i>
                        </a>
                        <a href="${url_print}" class="btn btn-info btn-xs set-tooltip" title="{{ __('label.print_proof_payment') }}" target="_blank">
                            <i class="bx bxs-printer"></i>
                        </a>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#btn-search").click(function() {
        start_date = $("#start").val()
        end_date = $("#end").val()
        type = $("#type").val()

        const formData = {start: start_date, end: end_date, type }
        window.history.pushState("", "", `{{ route('finance.transaction.history') }}?start=${start_date}&end=${end_date}&type=${type}`)
        window.LaravelDataTables["table-history"].ajax.reload()
    })
});
</script>
@endpush
