@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/student/edit"
    :breadcrumb-data="$student->encrypted_id"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-1">
        <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#form-identity" aria-current="page" href="#form-identity" aria-selected="true" role="tab">
                    <i class="fa-solid fa-address-card"></i> &nbsp;{{ __('label.identity') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#form-academic" href="#form-academic" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fa-solid fa-award"></i> &nbsp;{{ __('label.academic') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#form-excul" href="#form-excul" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fa-solid fa-universal-access"></i> &nbsp;{{ __('label.excul') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#form-other" href="#form-other" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fa-solid fa-bars"></i> &nbsp;{{ __('label.other') }}
                </a>
            </li>
        </ul>

        <form method="post" action="{{ route('academic.student.update', $student->encrypted_id) }}" class="form-block" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="tab-content">
                <div class="tab-pane active border-0 pt-2" id="form-identity" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-text
                                name="nis"
                                :label="__('label.nis')"
                                :old="old('nis', $student->nis)"
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-text
                                name="nik"
                                :label="__('label.nik')"
                                :old="old('nik', $student->nik)"
                                optional
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-text
                                name="nisn"
                                :label="__('label.nisn')"
                                :old="old('nisn', $student->nisn)"
                                optional
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-text
                                name="nis_local"
                                :label="__('label.nis_local')"
                                :old="old('nis_local', $student->nis_local)"
                                optional
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <x-form.input-text
                                name="name"
                                :label="__('label.name')"
                                :old="old('name', $student->name)"
                            />
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <x-form.radio
                                name="gender"
                                :label="__('label.gender')"
                                :old="old('gender', $student->gender->value)"
                                :option="$genders"
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-text
                                type="hidden"
                                name="id_parent"
                                id="parent-id"
                                :old="old('id_parent', @$student->parent->encrypted_id)"
                                readonly
                            />
                            <x-form.input-group-button
                                name="parent"
                                id="parent"
                                :label="__('label.parent')"
                                :old="old('parent', @$student->parent->name)"
                                button-id="btn-choose-parent"
                                button-label="<i class='fa-solid fa-search'></i>"
                                readonly
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.select
                                name="religion"
                                :label="__('label.religion')"
                                :option="$religions"
                                :old="old('religion', $student->religion->value)"
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.date-picker
                                name="birthdate"
                                picker-type="date"
                                :label="__('label.birthdate')"
                                :old="old('birthdate', $student->birthdate)"
                                optional
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-text
                                name="birthplace"
                                :label="__('label.birthplace')"
                                :old="old('birthplace', $student->birthplace)"
                                optional
                            />
                        </div>
                    </div>

                    <x-form.text-area
                        name="address"
                        :label="__('label.address')"
                        :old="old('address', $student->address)"
                        optional
                    />

                    <div class="row">
                        <div class="col-sm-6 col-md-2">
                            <x-form.input-mask
                                name="child"
                                :label="__('label.child_ke')"
                                mask="nominal"
                                :old="old('child', $student->child)"
                                :info="__('string.fill_in_with_numbers')"
                                optional
                            />
                        </div>
                    </div>
                </div>
                <div class="tab-pane border-0 pt-2" id="form-academic" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-text
                                name="card_number"
                                :label="__('label.student_card_number')"
                                :old="old('card_number', $student->card_number)"
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.select
                                name="id_asrama"
                                :label="__('label.asrama')"
                                :option="$asramas"
                                :old="old('id_asrama', $student->id_asrama)"
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.select
                                name="id_halaqah"
                                :label="__('label.halaqah')"
                                :option="$halaqahs"
                                :old="old('id_halaqah', $student->id_halaqah)"
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.select
                                id="education-level"
                                name="level_education"
                                :label="__('label.level_education')"
                                :old="old('level_education', $student->class->level_education->value)"
                                :option="$educations"
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.select
                                id="class"
                                name="id_class"
                                :label="__('label.class')"
                                :old="old('id_class', $student->id_class)"
                                :option="[]"
                                loading
                            />
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <x-form.input-text
                                name="school_from"
                                :label="__('label.school_from')"
                                :old="old('school_from', $student->school_from)"
                                optional
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.radio
                                name="beasiswa"
                                :label="__('label.scholarship')"
                                :old="old('beasiswa', $student->beasiswa)"
                                :option="$yesno"
                            />
                        </div>
                    </div>
                </div>
                <div class="tab-pane border-0 pt-2" id="form-excul" role="tabpanel">
                    <x-form.select
                        name="exculs[]"
                        id="excul"
                        :option="$exculs"
                        :old="old('exculs')"
                        multiple
                    />
                </div>
                <div class="tab-pane border-0 pt-2" id="form-other" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-6 col-md-4">
                            <x-form.input-file
                                name="photo"
                                id="photo"
                                :label="__('label.photo')"
                                accept-file="image"
                                image-height="100px"
                                :image-default="$student->file_photo"
                                :info="__('string.info_photo') . '. ' . __('string.info_only_filled')"
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <x-form.date-picker
                                name="entry_date"
                                picker-type="date"
                                :label="__('label.entry_date')"
                                :old="old('entry_date', $student->entry_date)"
                            />
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <x-form.input-group-mask
                                name="spp"
                                :label="__('label.spp')"
                                :old="old('spp', $student->spp)"
                                mask="nominal"
                                addon="Rp"
                                optional
                            />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text
                                name="location"
                                :label="__('label.location')"
                                :old="old('location', $student->location)"
                                optional
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-4">
                            <x-form.radio
                                name="status"
                                :label="__('label.status')"
                                :old="old('status', $student->status)"
                                :option="$activation"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 pt-0">
                <x-form.button-submit :cancel-route="route('academic.student.index')" />
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-parent" tabindex="-1" aria-labelledby="modal-parentLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-parentLabel">
                    <i class="bx bx-link text-primary"></i>&nbsp;
                    <small>{{ __('label.choose') . ' ' . __('label.parent') }}</small>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="table-parent">
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
let education_level = "{{ old('level_education', $student->class->level_education) }}"
let id_class = "{{ old('id_class', $student->id_class) }}"
let exculs = JSON.parse('{!! json_encode(old('exculs', $student->exculs)) !!}')
let datatable_parent = false

window.LaravelDataTables = window.LaravelDataTables || {}

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    optionClass()

    $(".nominal-mask").inputmask({ alias: "nominal" })
    $("#excul").val(exculs).trigger("change")

    $("#btn-choose-parent").click(function() {
        chooseParent()
    })

    $("#parent").click(function() {
        chooseParent()
    })

    $("#table-parent").on("click", ".btn-choose", function() {
        const parent = atob($(this).data("parent")).split("|")

        $("#parent-id").val(parent[0])
        $("#parent").val(parent[1])
        $("#modal-parent").modal("hide")
    })

    $("#education-level").change(function() {
        education_level = $(this).val()
        optionClass()
    })
})

$(document).on('shown.bs.modal', function(e) {
    $($.fn.dataTable.tables(true)).css('width', '100%')
})

function optionClass() {
    if (education_level != "") {
        $("#loading-class").show()

        const formData = {level: education_level}

        $.ajax({
            type: "POST",
            url: "{{ route('academic.class.get.option') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-class").hide()
                $("#class").html(response.option).trigger("change.select2")

                if(id_class != "")
                    $("#class").val(id_class).trigger("change.select2")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}

function chooseParent()
{
    if (datatable_parent) {
        window.LaravelDataTables["table-parent"].ajax.reload()
    } else {
        datatable_parent = true

        window.LaravelDataTables["table-parent"] = $("#table-parent").DataTable({
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
                    render: (data, type, row) => `<button type="button" class="btn btn-info btn-xs btn-choose set-tooltip" title="{{ __('label.choose') }}" data-parent="${btoa(`${row.encrypted_id}|${row.name}`)}">
                            <i class="fa-solid fa-check"></i>
                        </button>`
                }
            ]
        })
    }

    $("#modal-parent").modal("show")
}
</script>
@endpush
