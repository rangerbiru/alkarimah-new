@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<h4 class="fw-medium mb-0">
    <i class="{{ $icon }}"></i> Ahlan Wa Sahlan
</h4>

<div class="ms-sm-1 ms-0" style="min-width: 200px;">
    <x-form.select
        id="year"
        :option="$years"
        :old="$year->id"
    />
</div>
@endsection

@section('content')
<div class="row row-cols-xxl-5 row-cols-xl-3 row-cols-md-2">
    <div class="col card-background flex-fill">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <p class="fw-medium mb-1 text-muted">{{ __('label.savings_balance') }}</p>
                        <h3 class="mb-0" id="total-savings-balance">
                            <img src="{{ asset('images/loader.gif') }}" style="height: 25px;" />
                        </h3>
                    </div>
                    <div class="avatar avatar-md br-4 bg-primary-transparent ms-auto">
                        <i class="bx bx-wallet fs-20"></i>
                    </div>
                </div>

                <div class="mt-1">
                    <small class="text-muted">{{ __('string.total_savings_balance_all_student') }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col card-background flex-fill">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <p class="fw-medium mb-1 text-muted">{{ __('label.topup_balance') }}</p>
                        <h3 class="mb-0" id="total-topup-balance">
                            <img src="{{ asset('images/loader.gif') }}" style="height: 25px;" />
                        </h3>
                    </div>
                    <div class="avatar avatar-md br-4 bg-success-transparent ms-auto">
                        <i class="bx bx-plus fs-20"></i>
                    </div>
                </div>

                <div class="mt-1">
                    <small class="text-muted">{{ __('string.total_topup_balance_has_not_been_used') }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col card-background flex-fill">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <p class="fw-medium mb-1 text-muted">{{ __('label.cash') }}</p>
                        <h3 class="mb-0" id="total-cash">
                            <img src="{{ asset('images/loader.gif') }}" style="height: 25px;" />
                        </h3>
                    </div>
                    <div class="avatar avatar-md br-4 bg-info-transparent ms-auto">
                        <i class="bx bx-receipt fs-20"></i>
                    </div>
                </div>

                <div class="mt-1">
                    <small class="text-muted">{{ __('string.total_cash_not_yet_deposited') }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col card-background flex-fill">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <p class="fw-medium mb-1 text-muted">{{ __('label.unique_code') }}</p>
                        <h3 class="mb-0" id="total-unique-code">
                            <img src="{{ asset('images/loader.gif') }}" style="height: 25px;" />
                        </h3>
                    </div>
                    <div class="avatar avatar-md br-4 bg-danger-transparent ms-auto">
                        <i class="bx bx-receipt fs-20"></i>
                    </div>
                </div>

                <div class="mt-1">
                    <small class="text-muted">{{ __('string.total_unique_code_cash_not_yet_deposited') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card custom-card">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="me-2"><i class="bx bxs-credit-card-front text-primary" style="font-size: 22px;"></i></div>
            <div><h6 class="mt-0 mb-1">{{ __('label.payment_progress') }}</h6></div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="table-responsive" style="max-height: 320px;overflow-y: auto;">
                    <table class="table" id="table-progress">
                        <thead>
                            <tr>
                                <th class="ps-0" style="width: 40px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.level') }}</th>
                                <th class="text-end">{{ __('label.liability') }}</th>
                                <th class="text-end">{{ __('label.paid_off2') }}</th>
                                <th class="text-end">{{ __('label.less') }}</th>
                                <th colspan="2">{{ __('label.progress') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <div id="chart-payment-progress"></div>

                <div class="row mb-3">
                    <div class="col-xl-6 col-sm-6 col-6">
                        <div class="d-sm-flex align-items-center flex-wrap">
                            <div class="me-sm-2 mb-2 mb-sm-0">
                                <div class="avatar bg-success-transparent tx-primary br-5">
                                    <i class="ti ti-clipboard-check fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <span id="progress-paid" class="text-md mb-1 fw-semibold">...</span>
                                <p class="mb-0 fs-12  text-muted">{{ __('label.already_paid') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-sm-6 col-6">
                        <div class="d-sm-flex align-items-center flex-wrap">
                            <div class="me-sm-2 mb-2 mb-sm-0">
                                <div class="avatar bg-danger-transparent tx-primary br-5">
                                    <i class="ti ti-clipboard-text fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <span id="progress-unpaid" class="text-md mb-1 fw-semibold">...</span>
                                <p class="mb-0 fs-12  text-muted">{{ __('label.not_paid') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-2"><i class="bx bx-credit-card text-primary" style="font-size: 22px;"></i></div>
                    <div><h6 class="mt-0 mb-1">{{ __('string.todays_receipts_per_recipient') }}</h6></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-receipt" id="table-receipt-recipient">
                        <thead>
                            <tr>
                                <th class="ps-0" style="width: 40px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.recipient') }}</th>
                                <th class="text-end">{{ __('label.total') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-2"><i class="bx bx-chalkboard text-primary" style="font-size: 22px;"></i></div>
                    <div><h6 class="mt-0 mb-1">{{ __('string.todays_receipts_per_class') }}</h6></div>
                </div>

                <div class="table-responsive" style="max-height: 320px;overflow-y: auto;">
                    <table class="table table-receipt" id="table-receipt-class">
                        <thead>
                            <tr>
                                <th class="ps-0" style="width: 40px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.class') }}</th>
                                <th>{{ __('label.wali_kelas') }}</th>
                                <th class="text-end">{{ __('label.total') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-2"><i class="bx bx-credit-card-alt text-primary" style="font-size: 22px;"></i></div>
                    <div><h6 class="mt-0 mb-1">{{ __('string.todays_receipts_per_type') }}</h6></div>
                </div>

                <div class="table-responsive" style="max-height: 670px;overflow-y: auto;">
                    <table class="table table-receipt" id="table-receipt-type">
                        <thead>
                            <tr>
                                <th class="ps-0" style="width: 40px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.payment_type') }}</th>
                                <th class="text-end">{{ __('label.total') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card custom-card">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <div class="me-2"><i class="bx bxs-receipt text-primary" style="font-size: 22px;"></i></div>
            <div><h6 class="mt-0 mb-1">{{ __('label.todays_transaction') }}</h6></div>
        </div>

        <div class="table-responsive">
            <table class="table" id="table-transaction-today">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.transaction_number') }}</th>
                        <th>{{ __('label.transaction_type') }}</th>
                        <th>{{ __('label.student_or_parent_or_person') }}</th>
                        <th>{{ __('label.total') }}</th>
                        <th>{{ __('label.payment') }}</th>
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
<script src="{{ asset('vendors/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
const type_topup = "{{ $type_topup }}"
const type_bill = "{{ $type_bill }}"
const type_withdrawal = "{{ $type_withdrawal }}"

let id_year = "{{ $year->id }}"
let chart_progress
let progress = 0

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-transaction-today"] = $("#table-transaction-today").DataTable({
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
                d.start_date = "{{ date('Y-m-d') }}"
                d.end_date = "{{ date('Y-m-d') }}"

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
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#year").change(function() {
        id_year = $(this).val()

        getPaymentProgress()
        getReceipt()
    })

    count()
    setChartPaymentProgress()
    getPaymentProgress()
    getReceipt()
})

function count()
{
    $.ajax({
        type: "POST",
        url: "{{ route('dashboard.get.count') }}",
        success: function (response) {
            $("#total-savings-balance").html(moneyFormat(response.data.savings))
            $("#total-topup-balance").html(moneyFormat(response.data.topup))
            $("#total-cash").html(moneyFormat(response.data.cash))
            $("#total-unique-code").html(moneyFormat(response.data.unique_code))
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajaxError(xhr.status)
        }
    })
}

function setChartPaymentProgress()
{
    if (chart_progress != undefined)
        chart_progress.destroy()

    chart_progress = new ApexCharts(document.querySelector("#chart-payment-progress"), {
        series: [progress],
        chart: {
            id: "chart-payment-progress",
            height: 295,
            type: "radialBar",
        },
        plotOptions: {
        radialBar: {
            hollow: {
            size: "53%",
            },
        },
        },
        labels: ["{{ __('label.progress') }}"],
        colors: ["rgba(252, 171, 21, 0.95)"],
        // colors: ["rgba(73, 182, 245, 0.95)"],
    })

    chart_progress.render()
}

function getPaymentProgress()
{
    const formData = { year: id_year }

    $("#progress-paid").html('<img src="{{ asset('images/loader.gif') }}" style="height: 20px;" />')
    $("#progress-unpaid").html('<img src="{{ asset('images/loader.gif') }}" style="height: 20px;" />')
    $("#table-progress tbody, #table-progress tfoot").remove()
    $("#table-progress").append(`<tr class="loading">
        <td colspan="6" class="text-center py-5"><img src="{{ asset('images/loader.gif') }}" style="height: 50px;" /></td>
    </tr>`)

    $.ajax({
        type: "POST",
        url: "{{ route('dashboard.get.payment-progress') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            progress = Math.round(response.data.progress)

            $("#table-progress .loading").remove()
            $("#table-progress").append(response.data.report)
            $("#progress-paid").html(moneyFormat(response.data.total.paid))
            $("#progress-unpaid").html(moneyFormat(response.data.total.remaining))

            setChartPaymentProgress()
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajaxError(xhr.status)
        }
    })
}

function getReceipt()
{
    const formData = { year: id_year }

    $(".table-receipt tbody, .table-receipt tfoot").remove()
    $("#table-receipt-recipient").append(`<tr class="loading">
        <td colspan="3" class="text-center"><img src="{{ asset('images/loader.gif') }}" style="height: 50px;" /></td>
    </tr>`)

    $("#table-receipt-class").append(`<tr class="loading">
        <td colspan="4" class="text-center"><img src="{{ asset('images/loader.gif') }}" style="height: 50px;" /></td>
    </tr>`)

    $("#table-receipt-type").append(`<tr class="loading">
        <td colspan="3" class="text-center"><img src="{{ asset('images/loader.gif') }}" style="height: 50px;" /></td>
    </tr>`)

    $.ajax({
        type: "POST",
        url: "{{ route('dashboard.get.receipt') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            $(".table-receipt .loading").remove()
            $("#table-receipt-recipient").append(response.data.recipient)
            $("#table-receipt-class").append(response.data.class)
            $("#table-receipt-type").append(response.data.type)
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajaxError(xhr.status)
        }
    })
}
</script>
@endpush
