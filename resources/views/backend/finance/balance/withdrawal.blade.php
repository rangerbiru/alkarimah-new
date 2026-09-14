@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="finance/balance/withdrawal" />
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-sm-5 col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center border-bottom pb-3">
                        <div class="me-2">
                            <img src="{{ asset('images/icons/savings.png') }}" style="height: 50px;" />
                        </div>
                        <div>
                            <h5 id="number" class="text-grey mb-0">{{ $number }}</h5>
                            <span class="text-muted">
                                &nbsp;{{ __('label.payment_method') }} : {{ __('label.cash') }}
                            </span>
                        </div>
                    </div>

                    <div class="d-flex mt-3 align-items-center">
                        <div style="width: 140px;">{{ __('label.withdrawal_date') }}</div>
                        <div class="flex-fill">
                            <x-form.date-picker name="dates" id="date" picker-type="date" :old="date('d-m-Y')"
                                class="text-end" />
                        </div>
                    </div>
                    <div class="d-flex mt-3 align-items-center">
                        <div style="width: 140px;">{{ __('label.withdrawal_amount') }}</div>
                        <div class="flex-fill">
                            <x-form.input-group-mask id="total" name="total" mask="nominal" addon="Rp"
                                class="text-end bg-light" readonly />
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
        <div class="col-12 col-sm-7 col-md-8">
            <div class="card">
                <div class="card-body">
                    <x-form.input-text id="search" :placeholder="__('string.type_name_phone_to_search') . '...'" />

                    <a href="javascript:void(0)" id="btn-search-clear" class="set-tooltip" title="{{ __('label.clear') }}"
                        style="position: absolute;right: 50px;margin-top: -29px;color: #c5cbd3;">
                        <i class="fa-solid fa-times-circle"></i>
                    </a>
                    <a href="javascript:void(0)" id="btn-search" class="text-muted"
                        style="position: absolute;right: 25px;margin-top: -29px;">
                        <i class="fa-solid fa-search"></i>
                    </a>

                    <div id="start" class="text-center my-4">
                        <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                        <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                            <b>{{ __('string.type_name_phone_to_search') }}</b>
                        </h6>
                    </div>
                    <div id="loading" class="my-5 text-center">
                        <img src="{{ asset('images/loader.gif') }}" style="height: 40px;" />
                    </div>
                    <div id="student" class="mt-4"></div>
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
        const string_confirm_store = "{{ __('string.confirm_balance_withdrawal', ['total' => '__total__', 'parent' => '__parent__']) }}"
        const string_nominal_zero = "{{ __('string.balance_more_then_zero') }}"
        const string_balance_insufficient = "{{ __('string.parent_balance_insufficient', ['balance' => '__balance__']) }}"

        let id_parent = ""
        let parent_name = ""
        let parent_balance = 0

        $(document).ready(function() {
            $("#loading, #student, #btn-search-clear").hide()
            $(".nominal-mask").inputmask({
                alias: "nominal"
            })

            $("#search").autocomplete({
                source: `{{ route('finance.balance.get.autocomplete.parent') }}`,
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
                id_parent = ""
                parent_name = ""
                parent_balance = 0
                $("#search").val("").focus()
                $("#student").hide()
                $("#start").show()
                $("#total").val("").attr("readonly", true).addClass("bg-light")
                $("#btn-submit").attr("disabled", true).addClass("btn-secondary").removeClass("btn-primary")
            })

            $("#total").keyup(function() {
                const keyboard = event.which || event.keyCode
                if (keyboard == 13) {
                    confirmStore()
                }
            })

            $("#btn-submit").click(function() {
                confirmStore()
            })
        })

        function search(value) {
            const formData = {
                parent: value
            }

            id_parent = ""
            $("#student, #start").hide()
            $("#total").attr("readonly", true).addClass("bg-light")
            $("#btn-submit").attr("disabled", true).addClass("btn-secondary").removeClass("btn-primary")
            $("#loading").show()

            $.ajax({
                type: "POST",
                url: "{{ route('finance.balance.get.parent') }}",
                data: formData,
                dataType: "json",
                success: function(response) {
                    id_parent = response.data.id
                    if (id_parent != null) {
                        parent_name = value.split(" - ")[0]
                        parent_balance = parseFloat(response.data.balance)
                        $("#total").removeAttr("readonly").removeClass("bg-light").focus()
                        $("#btn-submit").removeAttr("disabled").addClass("btn-primary").removeClass(
                            "btn-secondary")
                    }
                    $("#loading").hide()
                    $("#btn-search-clear").show()
                    $("#student").html(response.data.html).show()
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    $("#loading").hide()
                    ajaxError(xhr.status)
                }
            })
        }

        function confirmStore() {
            if (!id_parent) return false

            let nominal = $("#total").val()
            nominal = (nominal == "") ? 0 : parseFloat(nominal.replace(/\./g, ""))

            if (nominal < 1) {
                setNotifInfo(string_nominal_zero)
                return false
            }

            if (nominal > parent_balance) {
                setNotifInfo(string_balance_insufficient.replace("__balance__", moneyFormat(parent_balance)))
                return false
            }

            Swal.fire({
                icon: "question",
                title: label_confirmation,
                text: string_confirm_store.replace("__total__", moneyFormat(nominal)).replace("__parent__", parent_name),
                showCancelButton: true,
                confirmButtonText: label_yes,
                cancelButtonText: label_cancel,
            }).then((result) => {
                if (result.isConfirmed) {
                    store()
                }
            })
        }

        function store() {
            const btn = $("#btn-submit")
            const formData = {
                id_parent: id_parent,
                dates: $("#date").val(),
                nominal: $("#total").val(),
            }

            btn.removeClass("btn-primary").addClass("btn-secondary btn-loader").html(
                "<span class='loading'><i class='ri-refresh-line fs-16'></i></span> &nbsp;&nbsp;PROCESSING").attr(
                "disabled", true)

            $.ajax({
                type: "POST",
                url: "{{ route('finance.balance.store.withdrawal') }}",
                data: formData,
                dataType: "json",
                success: function(response) {
                    btn.removeClass("btn-secondary btn-loader").addClass("btn-primary").removeAttr("disabled")
                        .html('<i class="fa-solid fa-check-circle"></i> SUBMIT')

                    if (response.status) {
                        parent_balance = parseFloat(response.data.balance)
                        $("#student .balance").html(`Rp. ${moneyFormat(parent_balance)}`)
                        $("#number").html(response.data.number)
                        $("#total").val("")

                        setNotifSuccess(response.message, false)
                    } else {
                        setNotifInfo(response.message)
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    btn.removeClass("btn-secondary btn-loader").addClass("btn-primary").removeAttr("disabled")
                        .html('<i class="fa-solid fa-check-circle"></i> SUBMIT')
                    ajaxLaravelError(xhr)
                }
            })
        }
    </script>
@endpush
