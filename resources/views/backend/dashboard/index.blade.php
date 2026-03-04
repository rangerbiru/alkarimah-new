@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page label="Dashboard" :icon="$icon" breadcrumb="dashboard" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="container mt-2">
                <div class="d-flex justify-content-between align-items-center p-2 mb-1">
                    <h5 class="mb-0">Total Pegawai</h5>
                    <h5 class="mb-0">{{ $totalEmployees }} Orang</h5>
                </div>
                <div class="row mb-3 g-2">
                    <div class="col-12 col-sm-6 col-lg-6">
                        <div
                            class="p-3 border rounded-2 bg-secondary-subtle h-100 d-flex align-items-center justify-content-between">
                            <h6 class="mb-0">Pegawai Tetap</h6>
                            <h6 class="mb-0">{{ $stayEmployees }} Orang</h6>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-6">
                        <div
                            class="p-3 border rounded-2 bg-secondary-subtle h-100 d-flex align-items-center justify-content-between">
                            <h6 class="mb-0">Pegawai Honorer</h6>
                            <h6 class="mb-0">{{ $honorerEmployees }} Orang</h6>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mb-2">
                    <div class="col-6">
                        <h5 class="mb-0">Absensi Hari Ini</h5>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="p-3 border rounded-2 bg-success-subtle h-100">
                            <h4>Hadir: <span>{{ $totalHadir }}</span></h4>
                            <p class="mb-0">Terlambat: <span>{{ $totalHadirTerlambat ?? 0 }}</span></p>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="p-3 border rounded-2 bg-info-subtle h-100">
                            <h4>Izin: <span>{{ $totalIzin }}</span></h4>
                            <p class="mb-0">Izin/Cuti</p>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="p-3 border rounded-2 bg-primary-subtle h-100">
                            <h4>Sakit: <span>{{ $totalSakit }}</span></h4>
                            <p class="mb-0">&nbsp;</p>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="p-3 border rounded-2 bg-danger-subtle h-100">
                            <h4>Tidak Hadir: <span>{{ $totalTidakHadir }}</span></h4>
                            <p class="mb-0">Tidak/Belum Hadir</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table mt-4 table-responsive">
                <table class="table table-bordered" id="table-attendance">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.nip') }}</th>
                            <th>{{ __('label.position') }}</th>
                            <th>{{ __('label.status') }}</th>
                            <th>{{ __('label.check_in_time') }}</th>
                            <th>{{ __('label.check_out_time') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('backend.dashboard.summary-attendance')
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
            window.LaravelDataTables["table-attendance"] = $("#table-attendance").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('dashboard.datatable.attendance') }}",
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
                        render: (data, type, row, meta) => htmlEntities(row.employee.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.employee.nip)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.group?.group_name ?? '-')
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => row.status
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => row.check_in_time
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => row.check_out_time
                    },
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });
    </script>
@endpush
