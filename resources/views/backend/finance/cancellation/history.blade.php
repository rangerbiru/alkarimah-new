@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="finance/cancellation/history" />
@endsection

@section('content')
    <div class="card custom-card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.date-picker id-start="start" id-end="end" name-start="start" name-end="end"
                        picker-type="date-range" :old="$filter->start" :old-end="$filter->end" />
                </div>
                <div class="col-sm-6 col-md-4">
                    <button type="button" id="btn-search" class="btn btn-secondary">
                        <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                    </button>
                    <a href="{{ route('finance.cancellation.index') }}" class="btn btn-outline-secondary">
                        {{ __('label.transaction_cancellation') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="table-history">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('label.no') }}</th>
                            <th>{{ __('label.transaction_number') }}</th>
                            <th>{{ __('label.transaction_type') }}</th>
                            <th>{{ __('label.student_or_parent_or_person') }}</th>
                            <th>{{ __('label.total') }}</th>
                            <th>{{ __('label.cancellation_reason') }}</th>
                            <th>{{ __('label.cancelled_by') }}</th>
                            <th>{{ __('label.cancelled_at') }}</th>
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
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
        type="text/javascript"></script>

    <script>
        const url_history = "{{ route('finance.cancellation.history') }}";

        let start_date = "{{ $filter->start }}";
        let end_date = "{{ $filter->end }}";

        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {};
            window.LaravelDataTables["table-history"] = $("#table-history").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('finance.cancellation.datatable.history') }}",
                    type: "POST",
                    data: (d) => {
                        d.start_date = start_date;
                        d.end_date = end_date;

                        return d;
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
                columns: [{
                        class: "align-top",
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        class: "align-top",
                        render: (data, type, row) => `<span class="fw-bold">${htmlEntities(row.number)}</span>
                            <span class="badge bg-danger ms-1">{{ strtoupper(__('label.cancelled')) }}</span>
                            <div class="mt-3">
                                <small><b>{{ __('label.payment_date') }}</b></small><br />
                                ${dateFormat(row.paid_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")}
                            </div>`
                    },
                    {
                        class: "align-top",
                        render: (data, type, row) => htmlEntities(row.type_name)
                    },
                    {
                        class: "align-top",
                        render: (data, type, row) => {
                            let html = row.student ? htmlEntities(row.student.name) : (row.parent_name ? htmlEntities(row.parent_name) : "-");

                            if (row.student) {
                                html += `<div class="mt-3"><small><b>{{ __('label.nis') }}</b></small><br />${htmlEntities(row.student.nis)}</div>`;
                            }
                            if (row.student && row.parent_name) {
                                html += `<div class="mt-3"><small><b>{{ __('label.parent') }}</b></small><br />${htmlEntities(row.parent_name)}</div>`;
                            }

                            return html;
                        }
                    },
                    {
                        class: "align-top",
                        render: (data, type, row) => "Rp. " + moneyFormat(row.total)
                    },
                    {
                        class: "align-top",
                        render: (data, type, row) => htmlEntities(row.reason)
                    },
                    {
                        class: "align-top",
                        render: (data, type, row) => htmlEntities(row.cancelled_by ?? "-")
                    },
                    {
                        class: "align-top",
                        render: (data, type, row) => dateFormat(row.cancelled_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")
                    }
                ]
            });

            $($.fn.dataTable.tables(true)).css('width', '100%');

            $("#btn-search").click(function() {
                start_date = $("#start").val();
                end_date = $("#end").val();

                window.history.pushState("", "", `${url_history}?start=${start_date}&end=${end_date}`);
                window.LaravelDataTables["table-history"].ajax.reload();
            });
        });
    </script>
@endpush
