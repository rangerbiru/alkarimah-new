@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/savings/withdrawal"
    :breadcrumb-data="$withdrawal->encrypted_id"
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
                        <h5 class="text-grey mb-0">{{ $withdrawal->number }}</h5>
                        <span class="text-muted">
                            &nbsp;{{ __('label.payment_method') }} : {{ __('label.cash') }}
                        </span>
                    </div>
                </div>

                <div class="d-flex mt-3 align-items-center">
                    <div style="width: 140px;">{{ __('label.request_date') }}</div>
                    <div>
                        <x-form.date-picker
                            name="dates"
                            id="date"
                            picker-type="date"
                            :old="date('d-m-Y', strtotime($withdrawal->dates))"
                            class="text-end"
                        />
                    </div>
                </div>
                <div class="d-flex mt-3 align-items-center">
                    <div style="width: 140px;">{{ __('label.amount') }}</div>
                    <div class="ms-auto">
                        <x-form.input-group-mask
                            id="total"
                            name="total"
                            mask="nominal"
                            addon="Rp"
                            class="text-end"
                            :old="$withdrawal->total"
                        />
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="button" id="btn-submit" class="btn btn-primary">
                        <i class="fa-solid fa-check-circle"></i> UPDATE
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
                        <b>Ketik NIS atau Nama Siswa</b><br />pada pencarian diatas untuk mulai mengajukan pengambilan
                    </h6>
                </div>
                <div id="loading" class="my-5 text-center">
                    <img src="{{ asset('images/loader.gif') }}" style="height: 40px;" />
                </div>
                <div id="student"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.css') }}" rel="stylesheet" />
@endpush

@push('scripts')
<script src="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.js') }}"></script>

<script>
const nis = "{{ $withdrawal->student->nis }}"
let id_student = ""
let balance = 0

$(document).ready(function() {
    $("#loading, #student").hide()
    $(".nominal-mask").inputmask({ alias: "nominal" })

    search()

    $("#total").keyup(function() {
        const keyboard = event.which || event.keyCode

        if (keyboard == 13) {
            store()
        }
    })

    $("#btn-submit").click(function() {
        store()
    })
})

function search()
{
    const formData = { nis }

    $("#student, #start").hide()
    $("#total").attr("readonly", true).addClass("bg-light")
    $("#btn-submit").attr("disabled", true).addClass("btn-secondary").removeClass("btn-primary")
    $("#loading").show()

    $.ajax({
        type: "POST",
        url: "{{ route('finance.savings.get.student') }}",
        data: formData,
        dataType: "json",
        success: function(response) {
            id_student = response.data.id

            if (id_student != null) {
                balance = response.data.balance

                $("#total").removeAttr("readonly").removeClass("bg-light").focus()
                $("#btn-submit").removeAttr("disabled").addClass("btn-primary").removeClass("btn-secondary")
            }

            $("#loading").hide()
            $("#student").html(response.data.student).show()
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajaxError(xhr.status)
        }
    })
}

function store()
{
    const btn = $("#btn-submit")
    const formData = {
        id_student,
        dates: $("#date").val(),
        total: $("#total").val(),
    }

    btn.removeClass("btn-primary").addClass("btn-secondary btn-loader").html("<span class='loading'><i class='ri-refresh-line fs-16'></i></span> &nbsp;&nbsp;PROCESSING").attr("disabled", true)

    $.ajax({
        type: "PUT",
        url: "{{ route('finance.savings.update.withdrawal', $withdrawal->encrypted_id) }}",
        data: formData,
        dataType: "json",
        success: function(response) {
            setNotifSuccess(response.message, "{{ route('dashboard.index') }}")
        },
        error: function (xhr, ajaxOptions, thrownError) {
            btn.removeClass("btn-secondary btn-loader").addClass("btn-primary").removeAttr("disabled").html('<i class="fa-solid fa-check-circle"></i> SUBMIT')
            ajaxError(xhr.status)
        }
    })
}
</script>
@endpush
