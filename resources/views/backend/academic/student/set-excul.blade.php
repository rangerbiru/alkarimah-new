@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/student/set/excul"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form id="form" method="post" action="{{ route('academic.student.store.set-excul') }}" class="form-block">
            @csrf

            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="education"
                        name="education"
                        :label="__('label.level_education')"
                        :old="old('education')"
                        :option="$educations"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="class"
                        name="class"
                        :label="__('label.class')"
                        :old="old('class')"
                        :option="[]"
                        loading
                    />
                </div>
            </div>

            <x-form.select
                name="exculs[]"
                :label="__('label.excul')"
                :old="old('exculs')"
                :option="$exculs"
                multiple
            />

            <div class="table-responsive mt-4">
                <table class="table" id="table-student">
                    <thead>
                        <tr>
                            <th style="max-width: 30px;">
                                <div class="form-check form-check-md">
                                    <input type="checkbox" class="form-check-input" id="check-all">
                                </div>
                            </th>
                            <th style="width: 150px;">{{ __('label.nis') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.gender') }}</th>
                            <th>{{ __('label.excul') }}</th>
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
let education = "{{ old('education') }}"
let classes = "{{ old('class') }}"

window.LaravelDataTables = window.LaravelDataTables || {}

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    optionClass()
    datatableStudent()

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#education").change(function() {
        education = $(this).val()
        optionClass()
    })

    $("#class").change(function() {
        classes = $(this).val()
        window.LaravelDataTables["table-student"].ajax.reload()
    })

    $("#check-all").click(function() {
        if ($(this).is(":checked")) {
            $("#table-student .cb-student").prop("checked", true)
            $("#table-student .cb-student").each(function() {
                const tr = $(this).closest("tr")
                tr.find(".nis").removeClass("bg-light").removeAttr("readonly")
            })
        } else {
            $("#table-student .cb-student").prop("checked", false)
            $("#table-student .cb-student").each(function() {
                const tr = $(this).closest("tr")
                tr.find(".nis").addClass("bg-light").attr("readonly", true)
            })
        }
    })

    $("#form").submit(function(e) {
        e.preventDefault()

        const formData = $("#form").serializeArray()

        $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.status)
                    setNotifSuccess(response.message, "{{ route('academic.student.index') }}")
                else {
                    setNotifInfo(response.message)
                    $("#form .btn-submit").html("<i class='fa-solid fa-paper-plane'></i> &nbsp;{{ __('label.save') }}").removeClass("btn-loader").removeAttr("disabled")
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxLaravelError(xhr)
                $("#form .btn-submit").html("<i class='fa-solid fa-paper-plane'></i> &nbsp;{{ __('label.save') }}").removeClass("btn-loader").removeAttr("disabled")
            }
        })
    })
})

function optionClass() {
    if (education != "") {
        $("#loading-class").show()

        const formData = {level: education}

        $.ajax({
            type: "POST",
            url: "{{ route('academic.class.get.option') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-class").hide()
                $("#class").html(response.option).trigger("change.select2")

                if (classes != "")
                    $("#class").val(classes).trigger("change.select2")
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
            url: "{{ route('academic.student.datatable.set-excul') }}",
            type: "POST",
            data: (d) => {
                d.class = classes

                return d
            }
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        ordering: false,
        aLengthMenu: [[100],[100]],
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
                render: (data, type, row, meta) => row.gender_name
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => {
                    let html = ""
                    row.exculs.map((e) => html += `<span class="badge bg-info">${e.name}</span> `)

                    return html
                }
            },
        ]
    })
}
</script>
@endpush
