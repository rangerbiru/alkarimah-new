@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/violation" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-section-page-action :label="$title" :icon="$icon" :count="$count" :create-route="route('academic.violation.create')" />

            <div class="table-responsive">
                <table class="table" id="table-violation">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.violation_name') }}</th>
                            <th>{{ __('label.date') }}</th>
                            <th>{{ __('label.officer') }}</th>
                            <th style="width: 70px;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('backend.academic.violation.modal')
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
            window.LaravelDataTables["table-violation"] = $("#table-violation").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('academic.violation.datatable') }}",
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
                        render: (data, type, row, meta) => htmlEntities(row.student.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.violation.description)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => row.date
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.employee.name)
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_destroy = "{{ route('academic.violation.destroy', ':id') }}"
                                .replace(":id", row.id);
                            let url_edit = "{{ route('academic.violation.edit', ':id') }}".replace(
                                ":id", row.id);

                            // Encode data row ke JSON aman untuk ditaruh di data attribute
                            let rowData = encodeURIComponent(JSON.stringify(row));

                            return `
            <div class="btn-group" role="group">
                <!-- TOMBOL DETAIL (DIPERBAIKI) -->
                <button type="button" 
                    class="btn btn-info btn-xs set-tooltip btn-detail-violation" 
                    title="Lihat Detail" 
                    data-row='${rowData}'>
                    <i class="bx bx-show"></i>
                </button>
                
                <a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                    <i class="bx bx-pencil"></i>
                </a>
                <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" 
                    title="${label_delete}" 
                    onclick="deleteViolation('${url_destroy}', true, 'table-violation')">
                    <i class="bx bx-trash"></i>
                </a>
            </div>
        `;
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });

        function deleteViolation(url, reload = true, tableId) {
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
                            Swal.fire("Berhasil Terhapus!", "Mata pelajaran berhasil dihapus",
                                "success");

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
