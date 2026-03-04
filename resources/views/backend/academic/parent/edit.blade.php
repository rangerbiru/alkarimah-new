@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/parent/edit"
    :breadcrumb-data="$parent->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('academic.parent.update', $parent->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name', $parent->name)"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group-mask
                        name="phone"
                        :label="__('label.phone_number')"
                        :old="old('phone', $parent->phone)"
                        mask="handphone"
                        addon="<i class='bx bx-mobile'></i>"
                    />
                </div>
                <div class="col-md-4">
                    <x-form.radio
                        name="gender"
                        :label="__('label.gender')"
                        :old="old('gender', $parent->gender->value)"
                        :option="$genders"
                    />
                </div>
                <div class="col-md-5">
                    <x-form.input-text
                        type="hidden"
                        name="id_relation"
                        id="relation-id"
                        :old="old('id_relation', @$parent->relation->encrypted_id)"
                        readonly
                    />
                    <x-form.input-group-button
                        name="relation"
                        id="relation"
                        :label="__('label.husband_or_wife')"
                        :old="old('relation', @$parent->relation->name)"
                        button-id="btn-choose-relation"
                        button-label="<i class='fa-solid fa-search'></i>"
                        optional
                        readonly
                    />
                </div>
            </div>

            <x-section-form
                :label="__('label.address')"
                icon="fa-solid fa-location-dot"
            />
            <div class="row">
                <div class="col-md-6">
                    <x-form.text-area
                        name="address"
                        :label="__('label.address')"
                        :old="old('address', $parent->address)"
                        optional
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        label="{{ __('label.province') }}"
                        id="province"
                        name="id_province"
                        :option="$provinces"
                        :old="old('id_province', @$parent->village->parent->parent->parent->id)"
                        optional
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        label="{{ __('label.city') }}"
                        id="city"
                        name="id_city"
                        :option="[]"
                        :old="old('id_city', @$parent->village->parent->parent->id)"
                        optional
                        loading
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        label="{{ __('label.district') }}"
                        id="district"
                        name="id_district"
                        :option="[]"
                        :old="old('id_district', @$parent->village->parent->id)"
                        optional
                        loading
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        label="{{ __('label.village') }}"
                        id="village"
                        name="id_village"
                        :option="[]"
                        :old="old('id_village', $parent->id_village)"
                        optional
                        loading
                    />
                </div>
            </div>

            <x-section-form
                :label="__('label.work')"
                icon="fa-solid fa-briefcase"
            />
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.input-text
                        name="work"
                        :label="__('label.work')"
                        :old="old('work', $parent->work)"
                        optional
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group-mask
                        name="income"
                        :label="__('label.income')"
                        :old="old('income', $parent->income)"
                        mask="nominal"
                        addon="Rp"
                        optional
                    />
                </div>
            </div>

            <x-section-form
                :label="__('label.user_account')"
                icon="fa-solid fa-user-circle"
            />

            @if (empty($parent->id_user))
                <div class="alert alert-info">
                <i class="fa-solid fa-info-circle"></i> <b>{{ __('label.information') }}</b>

                    <div class="mt-1">
                    {{ __('string.fill_password_for_account') }}.
                    </div>
                </div>

                <div class="mt-3 mb-3">
                    <label>{{ __('label.status') }}</label><br />
                    <span class="badge bg-danger">{{ __('string.dont_have_account_yet') }}</span>
                </div>
            @else
                <div class="mt-3 mb-3">
                    <label>{{ __('label.status') }}</label><br />
                    <span class="badge bg-success">{{ __('string.already_have_account') }}</span>
                </div>
            @endif

            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group
                        type="password"
                        name="password"
                        :label="__('label.password')"
                        :old="old('password')"
                        addon="<i class='bx bxs-lock-alt'></i>"
                        autocomplete="new-password"
                        :inf="__('string.info_only_filled')"
                        optional
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group
                        type="password"
                        name="password_confirm"
                        :label="__('label.confirm_password')"
                        :old="old('password_confirm')"
                        addon="<i class='bx bxs-lock-alt'></i>"
                        optional
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('academic.parent.index')" />
        </form>
    </div>
</div>

<div class="modal fade" id="modal-relation" tabindex="-1" aria-labelledby="modal-relationLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-relationLabel">
                    <i class="bx bx-link text-primary"></i>&nbsp;
                    <small>{{ __('label.choose') . ' ' . __('label.husband_or_wife') }}</small>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="table-relation">
                        <thead>
                            <tr>
                                <th style="width: 50px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.name') }}</th>
                                <th>{{ __('label.phone_number') }}</th>
                                <th>{{ __('label.gender') }}</th>
                                <th>{{ __('label.address') }}</th>
                                <th>{{ __('label.husband_or_wife') }}</th>
                                <th class="text-center" style="width: 35px;">#</th>
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
<link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"
let id_province = "{{ old('id_province', @$parent->village->parent->parent->parent->id) }}"
let id_city = "{{ old('id_city', @$parent->village->parent->parent->id) }}"
let id_district = "{{ old('id_district', @$parent->village->parent->id) }}"
let id_village = "{{ old('id_village', $parent->id_village) }}"
let datatable_relation = false

window.LaravelDataTables = window.LaravelDataTables || {}

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    $(".handphone-mask").inputmask({ alias: "handphone" })
    $(".email-mask").inputmask({ alias: "email" })
    $(".nominal-mask").inputmask({ alias: "nominal" })

    optionCity()
    optionDistrict()
    optionVillage()

    $("#btn-choose-relation").click(function() {
        chooseRelation()
    })

    $("#relation").click(function() {
        chooseRelation()
    })

    $("#table-relation").on("click", ".btn-choose", function() {
        const relation = atob($(this).data("relation")).split("|")

        $("#relation-id").val(relation[0])
        $("#relation").val(relation[1])
        $("#modal-relation").modal("hide")
    })

    $("#province").change(function() {
        id_province = $(this).val()
        optionCity()
    })

    $("#city").change(function(){
        id_city = $(this).val()
        optionDistrict()
    })

    $("#district").change(function(){
        id_district = $(this).val()
        optionVillage()
    })
})

$(document).on('shown.bs.modal', function(e) {
    $($.fn.dataTable.tables(true)).css('width', '100%')
})

function optionCity() {
    if (id_province != "") {
        $("#loading-city").show()

        const formData = {id_parent: id_province}

        $.ajax({
            type: "POST",
            url: "{{ route('region.option') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-city").hide()
                $("#city").html(response.option).trigger("change.select2")

                if(id_city != "") {
                    $("#city").val(id_city).trigger("change.select2")
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}

function optionDistrict() {
    if (id_city != "") {
        $("#loading-district").show()

        const formData = {id_parent: id_city}

        $.ajax({
            type: "POST",
            url: "{{ route('region.option') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-district").hide()
                $("#district").html(response.option).trigger("change.select2")

                if(id_district != "") {
                    $("#district").val(id_district).trigger("change.select2")
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}

function optionVillage() {
    if (id_district != "") {
        $("#loading-village").show()

        const formData = {id_parent: id_district}

        $.ajax({
            type: "POST",
            url: "{{ route('region.option') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-village").hide()
                $("#village").html(response.option).trigger("change.select2")

                if(id_village != "") {
                    $("#village").val(id_village).trigger("change.select2")
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}

function chooseRelation()
{
    if (datatable_relation) {
        window.LaravelDataTables["table-relation"].ajax.reload()
    } else {
        datatable_relation = true

        window.LaravelDataTables["table-relation"] = $("#table-relation").DataTable({
            language: {
                search: "",
                searchPlaceholder: `${label_search}...`,
                lengthMenu: "_MENU_ Data",
                emptyTable: label_nodata
            },
            ajax:
            {
                url: "{{ route('academic.parent.datatable') }}",
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
                    width: "50px",
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => htmlEntities(row.name)
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => (row.phone == "") ? "-" : phoneFormat(row.phone)
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => row.gender_name
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => row.address_full
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => (row.id_relation == null) ? "-" : row.relation.name
                },
                {
                    class: "align-middle text-center",
                    searchable: false,
                    render: (data, type, row) => `<button type="button" class="btn btn-info btn-xs btn-choose set-tooltip" title="{{ __('label.choose') }}" data-relation="${btoa(`${row.encrypted_id}|${row.name}`)}">
                            <i class="fa-solid fa-check"></i>
                        </button>`
                }
            ]
        })
    }

    $("#modal-relation").modal("show")
}
</script>
@endpush
