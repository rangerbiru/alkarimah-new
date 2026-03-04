@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="setting/year"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Mulai</label>

                            <div class="row">
                                <div class="col-7">
                                    <x-form.select
                                        id="start-month"
                                        name="start_month"
                                        :data-placeholder="__('label.month')"
                                        :option="$months"
                                    />
                                </div>
                                <div class="col-5">
                                    <x-form.select
                                        id="start-year"
                                        name="start_year"
                                        :data-placeholder="__('label.year')"
                                        :option="$years"
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
                                        :option="$months"
                                    />
                                </div>
                                <div class="col-5">
                                    <x-form.select
                                        id="end-year"
                                        name="end_year"
                                        :data-placeholder="__('label.year')"
                                        :option="$years"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-none d-sm-none d-md-block" style="margin-top: 23px;"></div>
                        <button type="button" id="btn-save" class="btn btn-primary btn-submit label-btn" data-loading="{{ __('label.saving') . '...' }}">
                            <i class="bx bxs-paper-plane label-btn-icon me-2"></i>
                            {{ __('label.save') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-none d-sm-none d-md-block" style="margin-top: 23px;"></div>
                <div class="alert alert-outline-info">
                    <div class="clearfix">
                        <div class="float-end"><i class="{{ $icon }}"></i></div>

                        <b>{{ number_format($count, 0, '', '.') }}</b> {{ $title }}
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" id="table-year">
                <thead>
                    <tr>
                        <th>{{ __('label.no') }}</th>
                        <th>{{ __('label.school_year') }}</th>
                        <th>{{ __('label.validity_period') }}</th>
                        <th>{{ __('label.status') }}</th>
                        <th style="width: 70px;">#</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">

<style>
.form-check-input:disabled {
    opacity: 1;
}
.form-check-input:disabled~.form-check-label, .form-check-input[disabled]~.form-check-label {
    opacity: 1;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
let id = ""
let count = parseInt("{{ $count }}")

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-year"] = $("#table-year").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('setting.year.datatable') }}",
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
                render: (data, type, row, meta) => row.year_name
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.month_range
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => {
                    let checked = ""
                    let color = "text-muted"

                    if (row.status) {
                        checked = " checked disabled"
                        color = "text-success"
                    }

                    return `<div class="form-check form-switch">
                        <input class="form-check-input form-checked-success switch-status" type="checkbox" role="switch" id="switch-status-${row.id}" data-id="${row.encrypted_id}"${checked}>
                        <label class="form-check-label ${color}" for="switch-status-${row.id}"><small>${row.status_label}</small></label>
                    </div>`
                }
            },
            {
                class: "align-middle text-center",
                searchable: false,
                render: function(data, type, row) {
                    const disable_delete = (row.status) ? " disabled" : ""
                    let url_destroy = "{{ route('branch.destroy', 0) }}"
                    url_destroy = url_destroy.replace("0", row.encrypted_id)

                    return `<button type="button" class="btn btn-dark btn-xs btn-edit set-tooltip" title="${label_edit}" data-id="${row.encrypted_id}" data-sy="${row.start_year}" data-sm="${row.start_month}" data-ey="${row.end_year}" data-em="${row.end_month}">
                            <i class="bx bx-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', false, 'table-year')"${disable_delete}>
                            <i class="bx bx-trash"></i>
                        </button>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#btn-save").click(function() {
        const formData = {
            start_year: $("#start-year").val(),
            start_month: $("#start-month").val(),
            end_year: $("#end-year").val(),
            end_month: $("#end-month").val(),
        }

        let url = "{{ route('setting.year.store') }}"

        if (id != "") {
            url = "{{ route('setting.year.update', 0) }}"
            url = url.replace("0", id)
        }

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.status) {
                    if (id == "") {
                        count++
                        $("#count").html(count)
                    }

                    id = ""
                    window.LaravelDataTables["table-year"].ajax.reload()
                    setNotifSuccess(response.message, false)
                } else
                    setNotifInfo(response.message)
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxLaravelError(xhr)
            }
        })
    })

    $("#table-year").on("click", ".btn-edit", function() {
        id = $(this).data("id")

        $("#start-year").val($(this).data("sy")).trigger("change")
        $("#start-month").val($(this).data("sm")).trigger("change")
        $("#end-year").val($(this).data("ey")).trigger("change")
        $("#end-month").val($(this).data("em")).trigger("change")
    })

    $("#table-year").on("change", ".switch-status", function() {
        id = $(this).data("id")

        const formData = {
            status: ($(this).is(":checked")) ? 1 : 0
        }

        let url = "{{ route('setting.year.update.status', 0) }}"
        url = url.replace("0", id)

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            dataType: "json",
            success: function (response) {
                id = ""

                if (response.status) {
                    window.LaravelDataTables["table-year"].ajax.reload()
                    setNotifSuccess(response.message, false)
                } else
                    setNotifInfo(response.message)
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxLaravelError(xhr)
            }
        })
    })
});
</script>
@endpush
