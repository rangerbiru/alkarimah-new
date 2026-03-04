@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/bill/discount/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('finance.bill.discount.store') }}" class="form-block">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-form.input-text
                        id="student"
                        name="student"
                        :label="__('label.student')"
                        :placeholder="__('string.type_nis_name_to_search') . '...'"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4 col-md-3">
                    <x-form.select
                        id="year"
                        name="id_year"
                        :label="__('label.school_year')"
                        :option="$years"
                        :old="old('id_year')"
                    />
                </div>
                <div class="col-sm-4 col-md-3">
                    <x-form.select
                        id="bill"
                        name="id_bill"
                        :label="__('label.bill')"
                        :option="[]"
                        :old="old('id_bill')"
                        loading
                    />
                </div>
                <div class="col-sm-4 col-md-3">
                    <x-form.input-group-mask
                        name="nominal"
                        :label="__('label.nominal')"
                        :old="old('nominal')"
                        mask="nominal"
                        addon="Rp"
                    />
                </div>
            </div>

            <div class="row" id="form-period">
                <div class="col-md-6">
                    <x-form.select
                        id="applies"
                        name="applies_to[]"
                        :label="__('label.applies_to')"
                        :option="[]"
                        multiple
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('finance.bill.discount.index')" />
        </form>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.css') }}" rel="stylesheet" />
@endpush

@push('scripts')
<script src="{{ asset('vendors/jquery-ui-autocomplete/jquery-ui.min.js') }}"></script>

<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"
let year = ""

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    $("#form-period").hide()
    $(".nominal-mask").inputmask({ alias: "nominal" })

    $("#student").autocomplete({
        source: `{{ route('academic.student.get.autocomplete') }}`,
        minLength: 2,
        select: (event, ui) => $("#student").blur()
    }).focus(function() {
        $(this).select()
    })

    $("#year").change(function() {
        year = $(this).val()
        optionBill()
    })

    $("#bill").change(function() {
        const formData = { id: $(this).val() }

        $.ajax({
            type: "POST",
            url: "{{ route('finance.bill.get') }}",
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.data.period == "monthly" || response.data.period == "semester") {
                    $("#applies").html(response.data.option).trigger("change")
                    $("#form-period").show()
                } else {
                    $("#applies").val("").trigger("change")
                    $("#form-period").hide()
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
      })
    })
})

function optionBill()
{
    if(year != ""){
        $("#loading-bill").show()

        const formData = {year}

        $.ajax({
            type: "POST",
            url: "{{ route('finance.report.get.option.bill') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-bill").hide()
                $("#bill").html(response.option).trigger("change.select2")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}
</script>
@endpush
