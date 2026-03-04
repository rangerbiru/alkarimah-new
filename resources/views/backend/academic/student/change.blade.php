@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/student/change"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form id="form" method="post" action="{{ route('academic.student.store.change') }}" class="form-block">
            @csrf

            <x-section-form
                :label="__('label.change_from')"
                style="margin-top: 0 !important;"
            />
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="from-education"
                        name="from_education"
                        :label="__('label.level_education')"
                        :old="old('from_education')"
                        :option="$educations"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="from-class"
                        name="from_class"
                        :label="__('label.class')"
                        :old="old('from_class')"
                        :option="[]"
                        loading
                    />
                </div>
            </div>

            <x-section-form
                :label="__('label.change_to')"
                style="margin-top: 15px !important;"
            />
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="to-education"
                        name="to_education"
                        :label="__('label.level_education')"
                        :old="old('to_education')"
                        :option="$educations"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="to-class"
                        name="to_class"
                        :label="__('label.class')"
                        :old="old('to_class')"
                        :option="[]"
                        loading
                    />
                </div>
            </div>

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
                            <th>{{ __('label.class') }}</th>
                            <th>{{ __('label.parent') }}</th>
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
let from_education = "{{ old('from_education') }}"
let from_class = "{{ old('from_class') }}"
let to_education = "{{ old('to_education') }}"
let to_class = "{{ old('to_class') }}"

window.LaravelDataTables = window.LaravelDataTables || {}

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    optionFromClass()
    optionToClass()
    datatableStudent()

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#from-education").change(function() {
        from_education = $(this).val()
        optionFromClass()
    })

    $("#from-class").change(function() {
        from_class = $(this).val()
        window.LaravelDataTables["table-student"].ajax.reload()
    })

    $("#to-education").change(function() {
        to_education = $(this).val()
        optionToClass()
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

    $("#table-student").on("click", ".cb-student", function() {
        const tr = $(this).closest("tr")

        if ($(this).is(":checked"))
            tr.find(".nis").removeClass("bg-light").removeAttr("readonly").focus().select()
        else
            tr.find(".nis").addClass("bg-light").attr("readonly", true)
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
                    setNotifSuccess(response.message, "reload")
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

function optionFromClass() {
    if (from_education != "") {
        $("#loading-from-class").show()

        const formData = {level: from_education}

        $.ajax({
            type: "POST",
            url: "{{ route('academic.class.get.option') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-from-class").hide()
                $("#from-class").html(response.option).trigger("change.select2")

                if (from_class != "")
                    $("#from-class").val(from_class).trigger("change.select2")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}

function optionToClass() {
    if (to_education != "") {
        $("#loading-to-class").show()

        const formData = {level: to_education}

        $.ajax({
            type: "POST",
            url: "{{ route('academic.class.get.option') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-to-class").hide()
                $("#to-class").html(response.option).trigger("change.select2")

                if (to_class != "")
                    $("#to-class").val(to_class).trigger("change.select2")
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
            url: "{{ route('academic.student.datatable.change') }}",
            type: "POST",
            data: (d) => {
                d.class = from_class

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
                render: (data, type, row, meta) => `<x-form.input-text name="nis[]" old="${row.nis}" class="nis bg-light" readonly />`
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
                render: (data, type, row, meta) => htmlEntities(row.class.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.parent == null) ? "-" : htmlEntities(row.parent.name)
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
