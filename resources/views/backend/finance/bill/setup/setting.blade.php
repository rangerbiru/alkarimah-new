@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="finance/bill/setup/setting" />
@endsection

@section('content')
    <div class="card card-nav">
        <div class="card-body">
            @include($path . 'menu')

            <div class="card-content">
                <form id="form-setting">
                    <div class="row gx-5">
                        <div class="col-sm-4 col-md-3">
                            <div class="card">
                                <div class="card-body p-3 bg-light">
                                    <div class="mb-4">
                                        <span class="pb-2" style="border-bottom: 1px solid #d7dee1;">
                                            <i class="fa-solid fa-clipboard-list text-info"></i>&nbsp;
                                            <b class="text-muted">[ 1 ] {{ __('label.bill') }}</b>
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <x-form.select id="year" name="id_year" :option="$years" :old="$year->id" />
                                    </div>
                                    <x-form.select id="bill" name="id_bill" :option="[]" :data-placeholder="__('label.choose_bill')"
                                        loading />

                                    <div class="mt-4 mb-4">
                                        <span class="pb-2" style="border-bottom: 1px solid #d7dee1;">
                                            <i class="fa-solid fa-notes-medical text-info"></i>&nbsp;
                                            <b class="text-muted">[ 2 ] {{ __('label.method') }}</b>
                                        </span>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="method" id="method-class"
                                            value="class" style="margin-top: .5em;" checked>
                                        <div class="form-check-label form-check-method d-flex" data-target="method-class">
                                            <div class="me-1"><i class="bx bx-buildings bx-sm text-muted"></i></div>
                                            <div class="pt-1">Per Kelas</div>
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="method" id="method-student"
                                            value="student" style="margin-top: .5em;">
                                        <div class="form-check-label form-check-method d-flex" data-target="method-student">
                                            <div class="me-1"><i class="bx bx-user bx-sm text-muted"></i></div>
                                            <div class="pt-1">Per Siswa</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-8 col-md-9">
                            <div class="mb-4">
                                <span class="pb-2" style="border-bottom: 1px solid #d7dee1;">
                                    <i class="fa-solid fa-chalkboard text-info"></i>&nbsp;
                                    <b class="text-muted">[ 3 ] {{ __('label.choose_class') }}</b>
                                </span>
                            </div>

                            <div class="row">
                                <div class="col-sm-6 col-md-4">
                                    <x-form.select id="education-level" :option="$educations" :data-placeholder="__('label.level_education')" />
                                </div>
                                <div class="col-sm-6 col-md-4 form-filter-class">
                                    <x-form.select id="class-level" :option="[]" :data-placeholder="__('label.level_class') . ' (' . __('label.optional') . ')'" loading />
                                </div>
                                <div class="col-sm-6 col-md-4 form-filter-student">
                                    <x-form.select id="class" :option="[]" :data-placeholder="__('label.class')" />
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <button type="button" id="btn-save" class="btn btn-primary"
                                        style="padding-bottom: 6px;">
                                        <i class="fa-solid fa-refresh"></i> &nbsp;{{ __('label.generate') }}
                                    </button>
                                </div>
                            </div>

                            <div id="form-loading" class="mt-5 text-center">
                                <img src="{{ asset('images/loader.gif') }}" style="height: 40px;" />
                            </div>
                            <div id="form-class" class="mt-4"></div>
                            <div id="form-student" class="mt-4">
                                <div class="table-responsive">
                                    <table class="table" id="table-student">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px;">
                                                    <div class="form-check form-check-md">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="check-all-student">
                                                    </div>
                                                </th>
                                                <th style="width: 50px;">{{ __('label.no') }}</th>
                                                <th style="width: 120px;">{{ __('label.nis') }}</th>
                                                <th>{{ __('label.name') }}</th>
                                                <th>{{ __('label.spp') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">

    <style>
        .form-box-class {
            border: 1px solid var(--input-border);
            border-radius: 5px;
            padding: 0.5rem 0.5rem;
        }

        .form-box-class .form-check-md .form-check-input {
            margin-top: 10px;
        }

        .form-box-class .form-check-text {
            padding-top: 8px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
        type="text/javascript"></script>

    <script>
        let id_year = "{{ $year->id }}"
        let education_level = ""
        let class_level = ""
        let classes = ""
        let method = "class"
        let datatable_student = false

        window.LaravelDataTables = window.LaravelDataTables || {}

        $(document).ready(function() {
            $("#form-loading, #form-student, .form-filter-student").hide()

            optionBill()

            $("#year").change(function() {
                id_year = $(this).val()
                optionBill()
            })

            $("input[name=method]").change(function() {
                method = $(this).val()
                getTarget()
            })

            $("#education-level").change(function() {
                education_level = $(this).val()

                if (method == "class") {
                    optionClassLevel()
                    getClass()
                } else {
                    optionClass()
                }
            })

            $("#class-level").change(function() {
                class_level = $(this).val()
                getClass()
            })

            $("#class").change(function() {
                classes = $(this).val()

                if (datatable_student) {
                    window.LaravelDataTables["table-student"].ajax.reload()
                } else {
                    datatable_student = true

                    window.LaravelDataTables["table-student"] = $("#table-student").DataTable({
                        language: {
                            search: "",
                            searchPlaceholder: `${label_search}...`,
                            lengthMenu: "_MENU_ Data",
                            emptyTable: label_nodata
                        },
                        ajax: {
                            url: "{{ route('finance.bill.datatable.student') }}",
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
                        aLengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        pageLength: 50,
                        drawCallback: function() {
                            $(".set-tooltip").tooltip({
                                container: "body"
                            })
                        },
                        columns: [{
                                class: "align-middle text-center",
                                searchable: false,
                                render: (data, type, row) => `<div class="form-check form-check-md">
                                <input class="form-check-input" type="checkbox" name="student[]" value="${row.id}">
                            </div>`
                            },
                            {
                                class: "align-middle",
                                searchable: false,
                                render: (data, type, row, meta) => meta.row + meta.settings
                                    ._iDisplayStart + 1
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
                                render: (data, type, row, meta) => htmlEntities(row.spp ?
                                    moneyFormat(row
                                        .spp) : "-")
                            },
                        ]
                    })

                    $($.fn.dataTable.tables(true)).css('width', '100%')
                }
            })

            $(".form-check-method").click(function() {
                const target = $(`#${$(this).data("target")}`)

                target.prop("checked", true).click()
                method = target.val()

                if (method == "class") {
                    $("#form-student, .form-filter-student").hide()
                    $(".form-filter-class").show()

                    if (education_level != "") {
                        optionClassLevel()
                        getClass()
                    }
                } else {
                    $("#form-class, #form-loading, .form-filter-class").hide()
                    $("#form-student, .form-filter-student").show()

                    if (education_level != "")
                        optionClass()
                }
            })

            $("#form-class").on("click", ".form-check-class", function() {
                const target = $(`#${$(this).data("target")}`)

                if (target.is(":checked"))
                    target.prop("checked", false)
                else
                    target.prop("checked", true)
            })

            $("#check-all-student").click(function() {
                if ($(this).is(":checked"))
                    $("#table-student input[type=checkbox]").prop("checked", true)
                else
                    $("#table-student input[type=checkbox]").prop("checked", false)
            })

            $("#btn-save").click(function() {
                const btn = $(this)
                const formData = $("#form-setting").serializeArray()

                btn.addClass("btn-loader").html(
                    "<span class='loading'><i class='ri-refresh-line fs-16'></i></span> &nbsp;&nbsp;{{ __('label.generating') }}..."
                ).attr("disabled", true)

                $.ajax({
                    type: "POST",
                    url: "{{ route('finance.bill.generate') }}",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        btn.removeClass("btn-loader").html(
                            "<i class='fa-solid fa-refresh'></i> &nbsp;{{ __('label.generate') }}"
                        ).removeAttr("disabled")
                        setNotifSuccess(response.message, false)
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        btn.removeClass("btn-loader").html(
                            "<i class='fa-solid fa-refresh'></i> &nbsp;{{ __('label.generate') }}"
                        ).removeAttr("disabled")
                        ajaxLaravelError(xhr)
                    }
                })
            })
        })

        function getClass() {
            const formData = {
                education: education_level,
                class: class_level
            }

            $("#form-class").hide()
            $("#form-loading").show()

            $.ajax({
                type: "POST",
                url: "{{ route('finance.bill.get.class') }}",
                data: formData,
                dataType: "json",
                success: function(response) {
                    $("#form-loading").hide()
                    $("#form-class").html(response.data.view).show()
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    ajaxError(xhr.status)
                }
            })
        }

        function optionBill() {
            if (id_year != "") {
                $("#loading-bill").show()

                const formData = {
                    id_year
                }

                $.ajax({
                    type: "POST",
                    url: "{{ route('finance.bill.get.option') }}",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        $("#loading-bill").hide()
                        $("#bill").html(response.option).trigger("change.select2")
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        ajaxError(xhr.status)
                    }
                })
            }
        }

        function optionClassLevel() {
            if (education_level != "") {
                $("#loading-class-level").show()

                const formData = {
                    level: education_level
                }

                $.ajax({
                    type: "POST",
                    url: "{{ route('academic.class.get.option.level') }}",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        $("#loading-class-level").hide()
                        $("#class-level").html(response.option).trigger("change.select2")
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        ajaxError(xhr.status)
                    }
                })
            }
        }

        function optionClass() {
            if (education_level != "") {
                $("#loading-class").show()

                const formData = {
                    level: education_level
                }

                $.ajax({
                    type: "POST",
                    url: "{{ route('academic.class.get.option') }}",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        $("#loading-class").hide()
                        $("#class").html(response.option).trigger("change.select2")
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        ajaxError(xhr.status)
                    }
                })
            }
        }
    </script>
@endpush
