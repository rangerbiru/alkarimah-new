@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/bill/setup/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('finance.bill.setup.store') }}" class="form-block">
            @csrf
            <x-form.input-text type="hidden" name="period" id="period" />

            <div class="row">
                <div class="col-md-6">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name')"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        name="id_year"
                        :label="__('label.year')"
                        :option="$years"
                        :old="old('id_year', $year->id)"
                    />
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>{{ __('label.type') }}</label>

                        <select id="type" name="id_type" class="set-select2">
                            <option value=""></option>

                            @foreach ($types as $t)
                                <option value="{{ $t->id }}" data-period="{{ $t->period }}"{{ ($t->id == old('id_type')) ? ' selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4 col-md-3">
                    <x-form.input-group-mask
                        name="nominal"
                        mask="nominal"
                        addon="Rp"
                        :label="__('label.nominal')"
                        :old="str_replace('.', '', old('nominal'))"
                    />
                </div>
                <div class="col-sm-4 col-md-3">
                    <div id="form-date-picker">
                        <x-form.date-picker
                            name="billing_date1"
                            picker-type="date"
                            :label="__('label.billing_date')"
                            :old="old('billing_date1')"
                        />
                    </div>
                    <div id="form-date-select">
                        <x-form.select
                            name="billing_date2"
                            :label="__('label.billing_date')"
                            :option="$dates"
                            :old="old('billing_date2')"
                        />
                    </div>
                </div>
                <div class="col-sm-4 col-md-3">
                    <div id="form-duedate-picker">
                        <x-form.date-picker
                            name="due_date1"
                            picker-type="date"
                            :label="__('label.due_date')"
                            :old="old('due_date1')"
                        />
                    </div>
                    <div id="form-duedate-select">
                        <x-form.select
                            name="due_date2"
                            :label="__('label.due_date')"
                            :option="$dates"
                            :old="old('due_date2')"
                        />
                    </div>
                </div>
            </div>

            <div id="form-validty-period" class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Mulai</label>

                        <div class="row">
                            <div class="col-7">
                                <x-form.select
                                    id="start-month"
                                    name="start_month"
                                    :data-placeholder="__('label.month')"
                                    :option="$validity_months"
                                    :old="old('start_month')"
                                />
                            </div>
                            <div class="col-5">
                                <x-form.select
                                    id="start-year"
                                    name="start_year"
                                    :data-placeholder="__('label.year')"
                                    :option="$validity_years"
                                    :old="old('start_year')"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Akhir</label>

                        <div class="row">
                            <div class="col-7">
                                <x-form.select
                                    id="end-month"
                                    name="end_month"
                                    :data-placeholder="__('label.month')"
                                    :option="$validity_months"
                                    :old="old('end_month')"
                                />
                            </div>
                            <div class="col-5">
                                <x-form.select
                                    id="end-year"
                                    name="end_year"
                                    :data-placeholder="__('label.year')"
                                    :option="$validity_years"
                                    :old="old('end_year')"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <x-form.text-area
                name="description"
                :label="__('label.information')"
                :old="old('description')"
            />

            <x-form.button-submit :cancel-route="route('finance.bill.setup.index')" />
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    $("#form-date-select, #form-duedate-select, #form-validty-period").hide()
    $(".nominal-mask").inputmask({ alias: "nominal" })

    setType()

    $("#type").change(function() {
        setType()
    })
})

function setType()
{
    const period = $("#type option:selected").data("period")

    if (period == "{{ $period_onetime }}") {
        $("#form-date-select, #form-duedate-select, #form-validty-period").hide()
        $("#form-date-picker, #form-duedate-picker").show()
    } else {
        $("#form-date-picker, #form-duedate-picker").hide()
        $("#form-date-select, #form-duedate-select").show()

        if (period == "{{ $period_monthly }}")
            $("#form-validty-period").show()
        else
            $("#form-validty-period").hide()
    }

    $("#period").val(period)
}
</script>
@endpush
