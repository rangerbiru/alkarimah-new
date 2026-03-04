@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/student/sync"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form id="form" method="post" action="{{ route('academic.student.store.sync') }}" class="form-block">
            @csrf

            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="asrama"
                        name="id_asrama"
                        :label="__('label.asrama')"
                        :option="$asramas"
                        :old="old('id_asrama')"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="halaqah"
                        name="id_halaqah"
                        :label="__('label.halaqah')"
                        :option="$halaqahs"
                        :old="old('id_halaqah')"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="education-level"
                        name="level_education"
                        :label="__('label.level_education')"
                        :old="old('level_education')"
                        :option="$educations"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="class"
                        name="id_class"
                        :label="__('label.class')"
                        :old="old('id_class')"
                        :option="[]"
                        loading
                    />
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-student">
                    <thead>
                        <tr>
                            <th style="max-width: 30px;">
                                <div class="form-check form-check-md">
                                    <input type="checkbox" class="form-check-input" id="check-all">
                                </div>
                            </th>
                            <th>{{ __('label.nis') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.class') }}</th>
                            <th>{{ __('label.asrama') }}</th>
                            <th>{{ __('label.halaqah') }}</th>
                            <th>{{ __('label.status') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <x-form.button-submit :cancel-route="route('academic.student.index')" />
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
let education_level = "{{ old('level_education') }}"
let id_class = "{{ old('id_class') }}"
let student = []

window.LaravelDataTables = window.LaravelDataTables || {}

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    optionClass()
    datatableStudent()

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#education-level").change(function() {
        education_level = $(this).val()
        optionClass()
    })

    $("#check-all").click(function() {
        if ($(this).is(":checked")) {
            $("#table-student .cb-student").prop("checked", true)
            $("#table-student .cb-student").each(function() {
                student.push($(this).val())
            })
        } else {
            $("#table-student .cb-student").prop("checked", false)
            $("#table-student .cb-student").each(function() {
                const index = student.indexOf($(this).val())
                delete student[index]
            })
        }
    })

    $("#table-student").on("click", ".cb-student", function() {
        if ($(this).is(":checked")) {
            student.push($(this).val())
        } else {
            const index = student.indexOf($(this).val())
            delete student[index]
        }
    })

    $("#form").submit(function(e) {
        e.preventDefault()

        const formData = {
            asrama: $("#asrama").val(),
            halaqah: $("#halaqah").val(),
            class: $("#class").val(),
            student
        }

        $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            data: formData,
            dataType: "json",
            success: function (response) {
                setNotifSuccess(response.message, "{{ route('academic.student.index') }}")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxLaravelError(xhr)
            }
        })
    })
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

function datatableStudent()
{
    window.LaravelDataTables["table-student"] = $("#table-student").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('academic.student.datatable.sync') }}",
            type: "POST",
            data: (d) => {
                d.selected = student

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
                class: "align-top text-center",
                searchable: false,
                render: (data, type, row) => `<div class="form-check form-check-md">
                        <input class="form-check-input cb-student" type="checkbox" name="student[]" value="${row.id}" ${row.checked} />
                    </div>`
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.nis)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.class.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.asrama == null) ? "-" : htmlEntities(row.asrama.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.halaqah == null) ? "-" : htmlEntities(row.halaqah.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.status_badge
            },
        ]
    })
}
</script>
@endpush
