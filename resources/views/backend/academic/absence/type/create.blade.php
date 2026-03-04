@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/absence/type/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('academic.absence.type.store') }}" class="form-block">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name')"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <x-form.input-text
                        :label="__('label.type2')"
                        :old="__('label.general')"
                        class="bg-light"
                        readonly
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('label.icon') }}</label>
                        <div class="d-flex" style="border: 1px solid var(--input-border);padding: 0.44rem 0.75rem;border-radius: 0.35rem;">
                            <div><i class="{{ old('icon', 'ti ti-info-circle') }}" id="icon-class" style="font-size: 20px;"></i></div>
                            <div class="ps-2" id="icon-name">{{ old('icon', 'ti ti-info-circle') }}</div>
                            <div class="ms-auto">
                                <a href="javascript:void(0)" id="btn-choose-icon" class="set-tooltip" title="{{ __('label.choose_icon') }}">
                                    <i class="fa-solid fa-search"></i>
                                </a>
                            </div>
                        </div>

                        <x-form.input-text
                            type="hidden"
                            name="icon"
                            id="icon"
                            :old="old('icon', 'ti ti-info-circle')"
                        />
                    </div>
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('academic.absence.type.index')" />
        </form>
    </div>
</div>

<div class="modal fade" id="modal-icon" tabindex="-1" aria-labelledby="modal-iconLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-iconLabel">
                    <i class="bx bx-donate-heart text-primary"></i> &nbsp;<small>{{ __('label.choose') . ' ' . __('label.icon') }}</small>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="table-icon">
                        <thead>
                            <tr>
                                <th style="width: 50px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.icon') }}</th>
                                <th>{{ __('label.name') }}</th>
                                <th style="width: 35px;">#</th>
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
let datatable_icon = false

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}

    if (error != "")
        setNotifInfo(error)

    $("#btn-choose-icon").click(function() {
        chooseIcon()
    })

    $("#table-icon").on("click", ".btn-choose", function() {
        const icon = atob($(this).data("icon"))

        $("#icon").val(icon)
        $("#icon-class").removeClass("{{ old('icon', 'ti ti-info-circle') }}").addClass(icon)
        $("#icon-name").html(icon)
        $("#modal-icon").modal("hide")
    })
})

$(document).on('shown.bs.modal', function(e) {
    $($.fn.dataTable.tables(true)).css('width', '100%')
})

function chooseIcon()
{
    if (datatable_icon) {
        window.LaravelDataTables["table-icon"].ajax.reload()
    } else {
        datatable_icon = true

        window.LaravelDataTables["table-icon"] = $("#table-icon").DataTable({
            language: {
                search: "",
                searchPlaceholder: `${label_search}...`,
                lengthMenu: "_MENU_ Data",
                emptyTable: label_nodata
            },
            ajax:
            {
                url: "{{ route('icon.datatable') }}",
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
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => `<i class="${row.class}" style="font-size: 20px;"></i>`
                },
                {
                    class: "align-middle",
                    render: (data, type, row, meta) => htmlEntities(row.class)
                },
                {
                    class: "align-middle text-center",
                    searchable: false,
                    render: (data, type, row) => `<button type="button" class="btn btn-info btn-xs btn-choose set-tooltip" title="{{ __('label.choose') }}" data-icon="${btoa(row.class)}">
                            <i class="fa-solid fa-check"></i>
                        </button>`
                }
            ]
        })
    }

    $("#modal-icon").modal("show")
}
</script>
@endpush
