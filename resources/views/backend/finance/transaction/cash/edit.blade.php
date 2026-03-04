@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/cash/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form id="form" method="post" action="{{ route('finance.transaction.update.cash', $deposit->encrypted_id) }}" class="form-block">
            @csrf

            <div class="row mb-1">
                <div class="col-md-4">
                    <x-form.date-picker
                        id-start="start"
                        id-end="end"
                        name-start="start_date"
                        name-end="end_date"
                        picker-type="date-range"
                        :label="__('label.transaction_date')"
                        :old="$deposit->start_date"
                        :old-end="$deposit->end_date"
                    />
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-transaction">
                    <thead>
                        <tr>
                            <th style="max-width: 30px;">
                                <div class="form-check form-check-md">
                                    <input type="checkbox" class="form-check-input" id="check-all">
                                </div>
                            </th>
                            <th>{{ __('label.transaction_number') }}</th>
                            <th>{{ __('label.student_name') }}</th>
                            <th>{{ __('label.total') }}</th>
                            <th>{{ __('label.payment') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-sm-6 col-md-3">
                    <x-form.date-picker
                        id="date"
                        name="dates"
                        picker-type="date"
                        :label="__('label.deposit_date')"
                        :old="old('dates', date('d-m-Y', strtotime($deposit->dates)))"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group
                        id="total"
                        name="total"
                        addon="Rp"
                        :label="__('label.total')"
                        :old="old('total', number_format($deposit->total, 0, '', '.'))"
                        class="bg-light"
                        readonly
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('finance.transaction.cash', 'waiting')" />
        </form>
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
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"
const type_topup = "{{ $type_topup }}"
const type_bill = "{{ $type_bill }}"

let type = ""
let transaction = JSON.parse('{!! json_encode($deposit->transactions) !!}')
let total = parseFloat("{{ $deposit->total }}")
let edit = JSON.parse('{!! json_encode($deposit->transactions) !!}')
let start_date = "{{ $deposit->start_date }}"
let end_date = "{{ $deposit->end_date }}"

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-transaction"] = $("#table-transaction").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.transaction.datatable.paid') }}",
            type: "POST",
            data: (d) => {
                d.selected = transaction
                d.edit = edit
                d.start_date = start_date
                d.end_date = end_date

                return d
            }
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        ordering: false,
        aLengthMenu: [[5, 10, 25, 50, 100],[5, 10, 25, 50, 100]],
        drawCallback: function() {
            $(".set-tooltip").tooltip({
                container: "body"
            })
        },
        columns: [
            {
                class: "align-top text-center",
                searchable: false,
                render: (data, type, row) => `<div class="form-check form-check-md">
                        <input class="form-check-input cb-transaction" type="checkbox" name="transaction[]" value="${row.id}" data-total="${row.total_deposit}" ${row.checked} />
                    </div>`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `<span class="fw-bold">${row.number}</span>
                    <div class="mt-3">
                        <small><b>{{ __('label.transaction_date') }}</b></small><br />
                        ${dateFormat(row.dates, "{dd} {mmm} {yyyy}")}
                    </div>`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `${htmlEntities(row.student.name)}
                    <div class="mt-3">
                        <small><b>{{ __('label.nis') }}</b></small><br />
                        ${htmlEntities(row.student.nis)}
                    </div>`
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
        ]
    })

    $(".date-range-picker").on("apply.daterangepicker", function (ev, picker) {
        start_date = $("#start").val()
        end_date = $("#end").val()
        transaction = []
        total = 0

        $("#check-all").prop("checked", false)
        window.LaravelDataTables["table-transaction"].ajax.reload()
    })

    $("#check-all").click(function() {
        if ($(this).is(":checked")) {
            $("#table-transaction .cb-transaction").prop("checked", true)
            $("#table-transaction .cb-transaction").each(function() {
                transaction.push($(this).val())
                total += parseFloat($(this).data("total"))
            })
        } else {
            $("#table-transaction .cb-transaction").prop("checked", false)
            $("#table-transaction .cb-transaction").each(function() {
                const index = transaction.indexOf($(this).val())
                delete transaction[index]

                total -= parseFloat($(this).data("total"))
            })
        }

        $("#total").val(moneyFormat(total))
    })

    $("#table-transaction").on("click", ".cb-transaction", function() {
        if ($(this).is(":checked")) {
            transaction.push($(this).val())
            total += parseFloat($(this).data("total"))
        } else {
            const index = transaction.indexOf($(this).val())
            delete transaction[index]

            total -= parseFloat($(this).data("total"))
        }

        $("#total").val(moneyFormat(total))
    })

    $("#form").submit(function(e) {
        e.preventDefault()

        const formData = {
            dates: $("#date").val(),
            start_date,
            end_date,
            transaction
        }

        $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            data: formData,
            dataType: "json",
            success: function (response) {
                Swal.fire({
                    icon: "success",
                    title: label_success,
                    text: response.message,
                    showCancelButton: true,
                    confirmButtonText: "{{ __('label.print') }}",
                    cancelButtonText: "OK",
                }).then((result) => {
                    const redirect = "{{ route('finance.transaction.cash', 'waiting') }}"

                    if (result.isConfirmed)
                        window.open(response.data.print, "_blank")

                    window.location = redirect
                })
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxLaravelError(xhr)
            }
        })
    })
})
</script>
@endpush
