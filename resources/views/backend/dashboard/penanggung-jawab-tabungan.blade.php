@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="dashboard"
/>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-4 col-md-3">
        <div class="col card-background flex-fill">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <p class="fw-medium mb-1 text-grey">{{ __('label.total') }}</p>
                            <h3 class="mb-0">Rp. {{ number_format($withdrawal->total, 0, '', '.') }}</h3>
                        </div>
                        <div class="avatar avatar-md br-4 bg-primary-transparent ms-auto">
                            <i class="bx bx-money-withdraw fs-20"></i>
                        </div>
                    </div>
                    <div class="d-flex mt-2">
                        <small class="text-muted">{{ __('string.total_savings_withdrawal_applications') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <p class="fw-medium mb-1 text-grey">{{ __('label.amount') }}</p>
                        <h3 class="mb-0">{{ number_format($withdrawal->amount, 0, '', '.') }}</h3>
                    </div>
                    <div class="avatar avatar-md br-4 bg-info-transparent ms-auto">
                        <i class="bx bx-clipboard fs-20"></i>
                    </div>
                </div>
                <div class="d-flex mt-2">
                    <small class="text-muted">{{ __('string.number_of_savings_withdrawal_applications') }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-8 col-md-9">
        <div class="col card-background flex-fill">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="alert alert-info">
                        {!! __('string.withdrawal_request_list') !!} :
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="table-withdrawal">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">{{ __('label.no') }}</th>
                                    <th>{{ __('label.request_number') }}</th>
                                    <th>{{ __('label.student_name') }}</th>
                                    <th>{{ __('label.class') }}</th>
                                    <th>{{ __('label.total') }}</th>
                                    <th class="text-center" style="width: 70px;">#</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
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
$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-withdrawal"] = $("#table-withdrawal").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('dashboard.datatable.withdrawal') }}",
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
                class: "align-top",
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `<span class="text-info fw-bold">${row.number}</span><br />
                    <small>${dateFormat(row.dates)}</small>`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `${htmlEntities(row.student.name)}<br />
                    <small>${htmlEntities(row.student.nis)}</small>`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `${row.student.class.level_education.toUpperCase()} ${htmlEntities(row.student.class.name)}`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
            },
            {
                class: "align-top text-center",
                searchable: false,
                render: function(data, type, row) {
                    let url_edit = "{{ route('finance.savings.edit.withdrawal', 0) }}"
                    let url_destroy = "{{ route('finance.savings.destroy.withdrawal', 0) }}"

                    url_edit = url_edit.replace("0", row.encrypted_id)
                    url_destroy = url_destroy.replace("0", row.encrypted_id)

                    return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                            <i class="bx bx-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', '{{ route('dashboard.index') }}')">
                            <i class="bx bx-trash"></i>
                        </a>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
});
</script>
@endpush
