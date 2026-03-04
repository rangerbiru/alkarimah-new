@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/attendance" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h5>Bulan {{ \Carbon\Carbon::now()->translatedFormat(' F Y') }}</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex flex-column w-100 bg-success p-2 rounded-2 box-attendance">
                    <i class='bx bx-log-in icon-attendance'></i>
                    <h6 class="fs-18 text-gray-700 font-semibold">Kehadiran</h6>
                    <h6>{{ $hadir }}x/ {{ $hadirPercent }}%</h6>
                </div>
                <div class="d-flex flex-column w-100 bg-warning p-2 rounded-2 box-attendance">
                    <i class='bx bx-user-check icon-attendance'></i>
                    <h6 class="fs-18 text-gray-700 font-semibold">Izin</h6>
                    <h6>{{ $izin }}x/ {{ $izinPercent }}%</h6>
                </div>
                <div class="d-flex flex-column w-100 bg-danger p-2 rounded-2 box-attendance">
                    <i class='bx bxs-user-x icon-attendance'></i>
                    <h6 class="fs-18 text-gray-700 font-semibold">Alfa</h6>
                    <h6>{{ $alfa }}x/ {{ $alfaPercent }}%</h6>
                </div>
            </div>

            <div class="mt-3 d-flex gap-1">
                <a href="{{ route('employee.attendance.export.excel') }}" class="btn btn-success btn-sm">
                    <i class='bx bxs-report'></i> Excel
                </a>
                <a href="{{ route('employee.attendance.export.pdf') }}" class="btn btn-danger btn-sm">
                    <i class='bx bxs-file-pdf'></i> PDF
                </a>
            </div>
        </div>
    </div>
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

                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-attendance">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.day') }}</th>
                            <th>{{ __('label.status') }}</th>
                            <th style="width: 35px;text-align: center !important;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($attendance as $att)
        @include('backend.employee.attendance.modal')
    @endforeach

@endsection

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
        type="text/javascript"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/id.min.js"></script>

    <script>
        let ustadzId = null;

        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {}
            window.LaravelDataTables["table-attendance"] = $("#table-attendance").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('employee.attendance.datatable') }}",
                    type: "POST",
                    dataSrc: function(json) {
                        ustadzId = json.ustadzIdLogin;
                        return json.data;
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
                        render: (data, type, row, meta) => htmlEntities(row.employee.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            return moment(row.date).format('dddd, DD MMMM YYYY');

                        }

                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) =>
                            (row.status === "hadir") ?
                            `<span class="badge bg-success">${row.status}</span>` : (row.status ===
                                "alpha") ? `<span class="badge bg-danger">${row.status}</span>` :
                            `<span class="badge bg-warning">${row.status}</span>`
                    },

                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            if (row.ustadz_id !== ustadzId) {
                                return '';
                            }

                            let url_destroy =
                                "{{ route('academic.student-permit.destroy', ':id') }}";
                            let url_approve =
                                "{{ route('employee.student-permit.approve', ':id') }}";

                            let url_reject =
                                "{{ route('employee.student-permit.reject', ':id') }}";

                            url_destroy = url_destroy.replace(":id", row.id);
                            url_approve = url_approve.replace(":id", row.id);
                            url_reject = url_reject.replace(":id", row.id);

                            return `
                    <div class="dropdown dropdown-link">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a data-bs-toggle="modal" data-bs-target="#detail-${row.id}" class="dropdown-item text-info d-flex gap-2 align-items-center" >
                                    <i class='bx bxs-check-square'></i> Detail
                                </a>
                            </li>
                        </ul>
                    </div>
                `;
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

        function approveConfirm(url, tableId) {
            Swal.fire({
                title: "Apakah Anda yakin?",
                text: "Berikan catatan perizinan",
                input: "textarea",
                inputPlaceholder: "Masukkan catatan perizinan...",
                inputAttributes: {
                    "aria-label": "Masukkan catatan perizinan"
                },
                showCancelButton: true,
                confirmButtonText: "Setujui",
                cancelButtonText: "Batal",
                preConfirm: (note) => {
                    return note;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            _method: 'PUT',
                            permission_note: result.value
                        },
                        success: function(res) {
                            Swal.fire("Berhasil!", res.message, "success").then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire("Gagal!", "Terjadi kesalahan saat menyetujui izin.", "error");
                        }
                    });
                }
            });
        }
    </script>
@endpush

<style>
    .box-attendance {
        position: relative;
        height: 80px;
    }

    .icon-attendance {
        position: absolute;
        font-size: 64px;
        color: #f5f5f5;
        opacity: 0.3;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }
</style>
