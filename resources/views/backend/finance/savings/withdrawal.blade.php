@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/savings/withdrawal"
/>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-5 col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center border-bottom pb-3">
                    <div class="me-2">
                        <img src="{{ asset('images/icons/savings.png') }}" style="height: 50px;" />
                    </div>
                    <div>
                        <h5 class="text-grey mb-0">{{ $number }}</h5>
                        <span class="text-muted">
                            &nbsp;{{ __('label.payment_method') }} : {{ __('label.cash') }}
                        </span>
                    </div>
                </div>

                <div class="d-flex mt-3 align-items-center">
                    <div style="width: 160px;">{{ __('label.withdrawal_date') }}</div>
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
                    <div>{{ __('label.penanggung_jawab_tabungan') }}</div>
                    <div class="ms-auto" style="width: 61%">
                        <x-form.select
                            name="person"
                            id="person"
                            :option="$persons"
                        />
                    </div>
                </div>

                <div class="d-flex mt-3 pt-2" style="border-top: 1px dashed var(--input-border);">
                    <div class="me-1">
                        <i class="ti ti-receipt text-grey" style="font-size: 40px;"></i>
                    </div>
                    <div class="fw-bold text-grey">
                        {{ __('label.total') }}<br />{{ __('label.withdrawal') }}
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
                <div id="start" class="text-center my-4">
                    <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                    <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                        <b>Pilih Penanggung Jawab</b><br />pada form disamping untuk mulai memproses pengambilan tabungan
                    </h6>
                </div>
                <div id="loading" class="my-5 text-center">
                    <img src="{{ asset('images/loader.gif') }}" style="height: 40px;" />
                </div>
                <div id="table-withdrawal" class="table-responsive" style="max-height: 420px;overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">
                                    <div class="form-check form-check-md">
                                        <input type="checkbox" class="form-check-input form-check-all">
                                    </div>
                                </th>
                                <th>{{ __('label.request_number') }}</th>
                                <th>{{ __('label.student_name') }}</th>
                                <th>{{ __('label.class') }}</th>
                                <th>{{ __('label.total') }}</th>
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

@push('scripts')
<script>
let withdrawals = {}
let withdrawals_selected = {}
let total = 0

$(document).ready(function() {
    $("#loading, #table-withdrawal").hide()

    $("#person").change(function() {
        const formData = {person: $(this).val()}

        $("#table-withdrawal, #start").hide()
        $("#loading").show()

        $.ajax({
            type: "POST",
            url: "{{ route('finance.savings.get.withdrawal') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                withdrawals = response.data.withdrawals

                $("#table-withdrawal tbody").html(response.data.table)
                $("#loading").hide()
                $("#table-withdrawal").show()
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    })

    $("#table-withdrawal .form-check-all").click(function() {
        total = 0
        withdrawals_selected = {}

        if ($(this).is(":checked")) {
            $("#table-withdrawal tbody .form-check-withdrawal").prop("checked", true)

            Object.keys(withdrawals).map((i) => {
                total += withdrawals[i]
                withdrawals_selected[i] = withdrawals[i]
            })
        } else {
            $("#table-withdrawal tbody .form-check-withdrawal").prop("checked", false)
        }

        setTotal()
    })

    $("#table-withdrawal").on("click", ".form-check-withdrawal", function() {
        const id = $(this).val()

        if ($(this).is(":checked")) {
            total += withdrawals[id]
            withdrawals_selected[id] = withdrawals[id]
        } else {
            total -= withdrawals[id]
            delete withdrawals_selected[id]
        }

        setTotal()
    })

    $("#btn-submit").click(function() {
        const btn = $(this)
        const formData = {
            id_parent: $("#person").val(),
            dates: $("#date").val(),
            bills: withdrawals_selected,
            total
        }

        btn.removeClass("btn-primary").addClass("btn-secondary btn-loader").html("<span class='loading'><i class='ri-refresh-line fs-16'></i></span> &nbsp;&nbsp;PROCESSING").attr("disabled", true)

        $.ajax({
            type: "POST",
            url: "{{ route('finance.savings.process.withdrawal') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        icon: "success",
                        title: label_success,
                        text: response.message,
                        showCancelButton: true,
                        confirmButtonText: "{{ __('label.print_proof') }}",
                        cancelButtonText: "OK",
                    }).then((result) => {
                        if (result.isConfirmed)
                            window.open(response.data.print, "_blank")

                        window.location.reload()
                    })
                } else {
                    btn.removeClass("btn-secondary btn-loader").addClass("btn-primary").removeAttr("disabled").html('<i class="fa-solid fa-check-circle"></i> SUBMIT')
                    setNotifInfo(response.message)
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                btn.removeClass("btn-secondary btn-loader").addClass("btn-primary").removeAttr("disabled").html('<i class="fa-solid fa-check-circle"></i> SUBMIT')
                ajaxLaravelError(xhr)
            }
        })
    })
})

function setTotal()
{
    if (total > 0)
        $("#btn-submit").removeClass("btn-secondary").addClass("btn-primary").removeAttr("disabled")
    else
        $("#btn-submit").removeClass("btn-primary").addClass("btn-secondary").attr("disabled", true)

    $("#total").html((total > 0) ? moneyFormat(total) : "0")
}
</script>
@endpush
