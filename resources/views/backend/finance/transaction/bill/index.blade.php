@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/bill"
/>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-5 col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center border-bottom pb-3">
                    <div class="me-2">
                        <img src="{{ asset('images/icons/bill.png') }}" style="height: 50px;" />
                    </div>
                    <div>
                        <h5 class="text-grey mb-0">{{ $number }}</h5>
                        <span class="text-muted">
                            &nbsp;{{ __('label.payment_method') }} : {{ __('label.cash') }}
                        </span>
                    </div>
                </div>

                <div class="d-flex mt-3 align-items-center">
                    <div style="width: 140px;">{{ __('label.transaction_date') }}</div>
                    <div>
                        <x-form.date-picker
                            name="dates"
                            id="date"
                            picker-type="date"
                            :old="date('d-m-Y')"
                            class="text-end"
                        />
                    </div>
                </div>
                <div class="d-flex mt-3 align-items-center">
                    <div class="pe-3">{{ __('label.scholarship') }}</div>
                    <div class="ms-auto" id="form-beasiswa">
                        <x-form.radio
                            name="beasiswa"
                            :option="$yesno"
                            :old="0"
                        />
                    </div>
                </div>
                <div class="form-donation">
                    <div class="d-flex mt-3 align-items-center">
                        <div style="width: 133px;">{{ __('label.donatur') }}</div>
                        <div class="ms-auto">
                            <x-form.input-group-button
                                name="id_donation"
                                id="donatur"
                                button-id="btn-choose-donatur"
                                button-label="<i class='fa-solid fa-search'></i>"
                                readonly
                            />
                        </div>
                    </div>
                    <div class="d-flex mt-3 pt-3 align-items-center" style="border-top: 1px dashed var(--input-border);">
                        <div style="width: 140px;">{{ __('label.subtotal') }}</div>
                        <div class="ms-auto">
                            <x-form.input-group-mask
                                id="subtotal"
                                mask="nominal"
                                addon="Rp"
                                class="text-end bg-light"
                                old="0"
                                readonly
                            />
                        </div>
                    </div>
                    <div class="d-flex mt-3 align-items-center">
                        <div style="width: 140px;">{{ __('label.scholarship_total') }}</div>
                        <div class="ms-auto">
                            <x-form.input-group-mask
                                name="donation"
                                id="donation-nominal"
                                mask="nominal"
                                addon="Rp"
                                class="text-end bg-light"
                                readonly
                            />
                        </div>
                    </div>
                </div>

                <div class="d-flex mt-3 pt-2" style="border-top: 1px dashed var(--input-border);">
                    <div class="me-1">
                        <i class="ti ti-receipt text-grey" style="font-size: 40px;"></i>
                    </div>
                    <div class="fw-bold text-grey">
                        {{ __('label.total') }}<br />{{ __('label.transaction') }}
                    </div>
                    <div class="ms-auto">
                        <h3 class="fw-normal text-grey mt-1 mb-0"><sup><small>Rp</small></sup> <span id="total">0</span></h3>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="button" id="btn-submit" class="btn btn-secondary" disabled>
                        <i class="fa-solid fa-check-circle"></i> SUBMIT
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-7 col-md-8">
        <div class="card">
            <div class="card-body">
                <x-form.input-text
                    id="search"
                    :placeholder="__('string.type_nis_name_to_search') . '...'"
                />

                <a href="javascript:void(0)" id="btn-search-clear" class="set-tooltip" title="{{ __('label.clear') }}" style="position: absolute;right: 50px;margin-top: -29px;color: #c5cbd3;">
                    <i class="fa-solid fa-times-circle"></i>
                </a>
                <a href="javascript:void(0)"id="btn-search" class="text-muted" style="position: absolute;right: 25px;margin-top: -27px;">
                    <i class="fa-solid fa-search"></i>
                </a>

                <div id="start" class="text-center my-4">
                    <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                    <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                        <b>Ketik NIS atau Nama Siswa</b><br />pada pencarian diatas untuk mulai melakukan pembayaran
                    </h6>
                </div>
                <div id="loading" class="my-5 text-center">
                    <img src="{{ asset('images/loader.gif') }}" style="height: 40px;" />
                </div>
                <div id="table-bill" class="table-responsive" style="max-height: 420px;overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">
                                    <div class="form-check form-check-md">
                                        <input type="checkbox" class="form-check-input form-check-all">
                                    </div>
                                </th>
                                <th>No</th>
                                <th>Jenis Tagihan</th>
                                <th>Nama</th>
                                <th>Tgl. Jatuh Tempo</th>
                                <th>Nominal</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-donatur" tabindex="-1" aria-labelledby="modal-donaturLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-donaturLabel">
                    <i class="bx bx-donate-heart text-primary"></i> &nbsp;<small>{{ __('label.choose') . ' ' . __('label.donatur') }}</small>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="table-donatur">
                        <thead>
                            <tr>
                                <th style="width: 50px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.name') }}</th>
                                <th>{{ __('label.donation_total') }}</th>
                                <th>{{ __('label.donation_used') }}</th>
                                <th>{{ __('label.remaining') }}</th>
                                <th style="width: 35px;">#</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.css') }}" rel="stylesheet" />
<link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
let bills = {}
let bills_selected = {}
let subtotal = 0
let donation = 0
let datatable_donatur = false
let id_donatur = ""
let id_student = ""

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}

    $("#loading, #table-bill, #btn-search-clear, .form-donation").hide()
    $(".nominal-mask").inputmask({ alias: "nominal" })

    $("#form-beasiswa input").click(function() {
        $(".form-donation input").val("")

        if ($(this).val() == "1") {
            $("#subtotal").val(subtotal)
            $(".form-donation").show()
        } else
            $(".form-donation").hide()

        donation = 0
        id_donatur = ""

        $("#donatur").val("")
        $("#donatur-nominal").val("")
    })

    $("#search").autocomplete({
        source: `{{ route('academic.student.get.autocomplete') }}`,
        minLength: 2,
        select: (event, ui) => search(ui.item.value)
    }).keyup(function() {
        const keyboard = event.which || event.keyCode

        if (keyboard == 13) {
            search($("#search").val())
        }
    })

    $("#btn-search").click(function() {
        search($("#search").val())
    })

    $("#btn-search-clear").click(function() {
        id_student = ""
        bills = {}
        bills_selected = {}
        subtotal = 0
        donation = 0

        setTotal()

        $("#search").val("").focus()
        $("#subtotal").val("")
        $("#donation-nominal").val("")
        $("#table-bill, #student").hide()
        $("#start").show()
        $("#btn-submit").attr("disabled", true).addClass("btn-secondary").removeClass("btn-primary")
    })

    $("#table-bill").on("click", ".form-check-bill", function() {
        const id = $(this).val()

        if ($(this).is(":checked")) {
            subtotal += bills[id]
            bills_selected[id] = bills[id]
        } else {
            subtotal -= bills[id]
            delete bills_selected[id]
        }

        $("#subtotal").val(subtotal)
        setTotal()
    })

    $("#table-bill .form-check-all").click(function() {
        subtotal = 0
        bills_selected = {}

        if ($(this).is(":checked")) {
            $("#table-bill tbody .form-check-bill").prop("checked", true)

            Object.keys(bills).map((i) => {
                subtotal += bills[i]
                bills_selected[i] = bills[i]
            })
        } else {
            $("#table-bill tbody .form-check-bill").prop("checked", false)
        }

        setTotal()
    })

    $("#btn-choose-donatur").click(function() {
        chooseDonatur()
    })

    $("#donatur").click(function() {
        chooseDonatur()
    })

    $("#table-donatur").on("click", ".btn-choose", function() {
        const donatur = atob($(this).data("donatur")).split("|")
        id_donatur = donatur[0]

        $("#donatur").val(donatur[1])
        $("#modal-donatur").modal("hide")
        $("#donation-nominal").removeAttr("readonly").removeClass("bg-light").focus()
        $("#subtotal").val(subtotal)
    })

    $("#donation-nominal").keyup(function() {
        const keyboard = event.which || event.keyCode

        if (keyboard == 13) {
            let nominal = $(this).val()
            donation = (nominal == "") ? 0 : parseFloat(nominal.replace(/\./g, ""))

            setTotal()
        }
    }).blur(function() {
        let nominal = $(this).val()
        donation = (nominal == "") ? 0 : parseFloat(nominal.replace(/\./g, ""))

        setTotal()
    })

    $("#btn-submit").click(function() {
        const btn = $(this)
        const formData = {
            id_student,
            id_donation: id_donatur,
            dates: $("#date").val(),
            bills: bills_selected,
            donation,
        }

        btn.removeClass("btn-primary").addClass("btn-secondary btn-loader").html("<span class='loading'><i class='ri-refresh-line fs-16'></i></span> &nbsp;&nbsp;PROCESSING").attr("disabled", true)

        $.ajax({
            type: "POST",
            url: "{{ route('finance.transaction.store') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.status)
                    setNotifSuccess(response.message, "reload")
                else {
                    btn.removeClass("btn-secondary btn-loader").addClass("btn-primary").removeAttr("disabled").html('<i class="fa-solid fa-check-circle"></i> SUBMIT')
                    setNotifInfo(response.message)
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                btn.removeClass("btn-secondary btn-loader").addClass("btn-primary").removeAttr("disabled").html('<i class="fa-solid fa-check-circle"></i> SUBMIT')
                ajaxError(xhr.status)
            }
        })
    })
})

$(document).on('shown.bs.modal', function(e) {
    $($.fn.dataTable.tables(true)).css('width', '100%')
})

function search(value)
{
    const formData = {search: value}

    $("#table-bill, #start").hide()
    $("#loading").show()

    $.ajax({
        type: "POST",
        url: "{{ route('finance.transaction.get.bill') }}",
        data: formData,
        dataType: "json",
        success: function(response) {
            bills = response.data.bills
            id_student = response.data.student

            $("#table-bill tbody").html(response.data.table)
            $("#loading").hide()
            $("#table-bill, #btn-search-clear").show()
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajaxError(xhr.status)
        }
    })
}

function chooseDonatur()
{
    if (datatable_donatur) {
        window.LaravelDataTables["table-donatur"].ajax.reload()
    } else {
        datatable_donatur = true

        window.LaravelDataTables["table-donatur"] = $("#table-donatur").DataTable({
            language: {
                search: "",
                searchPlaceholder: `${label_search}...`,
                lengthMenu: "_MENU_ Data",
                emptyTable: label_nodata
            },
            ajax:
            {
                url: "{{ route('finance.transaction.datatable.donatur') }}",
                type: "POST"
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
                    class: "align-middle",
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => htmlEntities(row.name)
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.used)}`
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => `Rp. ${moneyFormat(row.remaining)}`
                },
                {
                    class: "align-middle text-center",
                    searchable: false,
                    render: (data, type, row) => `<button type="button" class="btn btn-info btn-xs btn-choose set-tooltip" title="{{ __('label.choose') }}" data-donatur="${btoa(`${row.encrypted_id}|${row.name}|${row.remaining}`)}">
                            <i class="fa-solid fa-check"></i>
                        </button>`
                }
            ]
        })
    }

    $("#modal-donatur").modal("show")
}

function setTotal()
{
    const total = subtotal - donation

    if (subtotal > 0)
        $("#btn-submit").removeClass("btn-secondary").addClass("btn-primary").removeAttr("disabled")
    else
        $("#btn-submit").removeClass("btn-primary").addClass("btn-secondary").attr("disabled", true)

    $("#total").html((total > 0) ? moneyFormat(total) : "0")
}
</script>
@endpush
