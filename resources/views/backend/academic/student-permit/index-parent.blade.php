@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
    <x-section-page-mobile :label="$title" :icon="$icon" />
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

                    <a href="{{ route('academic.student-permit.create') }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>

                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-student">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.purpose') }}</th>
                            <th>{{ __('label.date_permit') }}</th>
                            <th>{{ __('label.status') }}</th>
                            <th style="width: 35px;text-align: center !important;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($permits as $permit)
        <div class="modal fade" id="detail-{{ $permit->id }}" tabindex="-1"
            aria-labelledby="modalLabel-{{ $permit->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel-{{ $permit->id }}">Detail Grup -
                            {{ $permit->group->group_name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Nama Siswa</h6>
                        <p>{{ $permit->student->name ?? '-' }}</p>

                        <h6>Nama Pengampu</h6>
                        <p>{{ $ustadzName ?? '-' }}</p>

                        <h6>Keperluan</h6>
                        <p>{{ $permit->purpose ?? '-' }}</p>

                        <h6>Mulai Izin</h6>
                        <p>{{ $permit->permit_start_date ?? '-' }}</p>

                        <h6>Selesai Izin</h6>
                        <p>{{ $permit->permit_end_date ?? '-' }}</p>

                        <h6>Catatan</h6>
                        <p>{{ $permit->notes ?? '-' }}</p>

                        <h6>Status</h6>
                        {!! $permit->status === 'approved'
                            ? '<span class="badge bg-success">Approved</span>'
                            : ($permit->status === 'rejected'
                                ? '<span class="badge bg-danger">Rejected</span>'
                                : '<span class="badge bg-warning">Pending</span>') !!}

                    </div>
                </div>
            </div>
        </div>
    @endforeach


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
            window.LaravelDataTables["table-student"] = $("#table-student").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('academic.student-permit.datatable') }}",
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
                        render: (data, type, row, meta) => htmlEntities(row.purpose)

                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            const date = new Date(row.permit_start_date);
                            const tanggal = date.toLocaleDateString('id-ID', {
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            });
                            const jam = date.toLocaleTimeString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            return `${tanggal}, ${jam} WIB`;
                        }
                    },


                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => (row.status === "approved") ?
                            `<span class="badge bg-success">${row.status}</span>` :
                            `<span class="badge bg-warning">${row.status}</span>`
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {

                            let url_destroy =
                                "{{ route('academic.student-permit.destroy', ':id') }}"

                            url_destroy = url_destroy.replace(":id", row.id)

                            return `<div class="dropdown dropdown-link">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#detail-${row.id}">
                                    <i class='bx bxs-check-square me-1'></i> Detail
                                </a>
                            </li>
                            
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item text-danger" onclick="deleteConfirmNew('${url_destroy}', true, 'table-student')">
                                    <i class="bx bx-trash me-2"></i>${label_delete}
                                </a>
                            </li>
                        </ul>
                    </div>

`
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        })

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
