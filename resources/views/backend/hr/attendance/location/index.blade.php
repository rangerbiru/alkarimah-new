@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/attendance/location" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-sm-7 col-md-3">
                    <div class="alert alert-outline-info">
                        <div class="clearfix">
                            <div class="float-end"><i class="{{ $icon }}" style="font-size: 16px;"></i></div>

                            <b id="count-display">{{ number_format($count, 0, '', '.') }}</b> {{ $title }}
                        </div>
                    </div>
                </div>
                <div class="col-sm-5 col-md-9">
                    <div class="d-block d-sm-none mt-3"></div>

                    <a href="{{ route('hr.attendance.location.create') }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>

                </div>
            </div>
            <div class="table-responsive">
                <table class="table" id="table-attendance-group">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.location_name') }}</th>
                            <th>{{ __('label.group_name') }}</th>
                            <th>{{ __('label.location_coordinate') }}</th>
                            <th>{{ __('label.location_exact') }}</th>
                            <th>{{ __('label.location_radius') }}</th>
                            {{-- <th>{{ __('label.qrcode') }}</th> --}}
                            <th class="text-center" style="width: 70px;">#</th>
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
        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {}
            window.LaravelDataTables["table-attendance-group"] = $("#table-attendance-group").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('hr.attendance.location.datatable') }}",
                    type: "POST"
                },
                processing: true,
                serverSide: true,
                deferRender: true,
                ordering: false,
                aLengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                drawCallback: function() {
                    $(".set-tooltip").tooltip({
                        container: "body"
                    })
                },
                columns: [{
                        class: "align-middle",
                        width: "50px",
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.location_name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            return '<div class="text-capitalize">' + htmlEntities(row.group
                                    ?.group_name) +
                                '</div>'
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.coordinate)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.attendance_location)
                    },

                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            return '<div class="text-capitalize">' + htmlEntities(row
                                    .attendance_radius) +
                                ' meter</div>'
                        }
                    },

                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_edit = "{{ route('hr.attendance.location.edit', ':id') }}";
                            let url_destroy =
                                "{{ route('hr.attendance.location.destroy', ':id') }}";

                            url_edit = url_edit.replace(':id', row.id);
                            url_destroy = url_destroy.replace(':id', row.id);


                            return `<div class='group-btn-action'>
                                <a href="${url_edit}" class="d-flex align-items-center gap-1 btn w-100 btn-info btn-xs set-tooltip" >
                            <i class='bx bx-pencil'></i> Edit
                        </a>
                        <a href="javascript:void(0)" class="d-flex align-items-center gap-1 btn w-100 btn-danger btn-xs set-tooltip"  onclick="deleteConfirmNew('${url_destroy}', true, 'table-attendance-group')">
                            <i class="bx bx-trash"></i> Delete
                        </a></div>`
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });

        function deleteConfirmNew(url, reload = true, tableId) {
            Swal.fire({
                title: "Apakah Anda yakin?",
                text: "Data yang dihapus tidak dapat dikembalikan.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, hapus",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            _method: 'DELETE'
                        },
                        success: function(res) {
                            Swal.fire("Berhasil Terhapus!", res.message, "success");

                            let countElement = document.getElementById('count-display');
                            if (countElement) {
                                let currentCount = parseInt(countElement.textContent.replace(/\./g,
                                    ''));
                                if (!isNaN(currentCount) && currentCount > 0) {
                                    let newCount = currentCount - 1;
                                    countElement.textContent = newCount.toString().replace(
                                        /\B(?=(\d{3})+(?!\d))/g, ".");
                                }
                            }

                            if (reload && tableId && window.LaravelDataTables[tableId]) {
                                window.LaravelDataTables[tableId].ajax.reload(null, true);
                            }
                        },
                        error: function() {
                            Swal.fire("Gagal!", "Terjadi kesalahan saat menghapus data", "error");
                        }
                    });
                }
            });
        }
    </script>
@endpush

<style>
    .group-btn-action {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
</style>
