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
                <table class="table" id="table-tahfidz">
                    <thead>
                        <tr>
                            <th rowspan="2" style="text-align: center;">No.</th>
                            <th rowspan="2" style="text-align: center; width: 20%;">Nama Santri</th>
                            <th colspan="2" style="text-align: center !important; width: 25%;">Target</th>
                            <th colspan="2" style="text-align: center !important; width: 25%;">Perolehan</th>
                            <th rowspan="2" style="text-align: center;">% Ketercapaian Target</th>
                            <th rowspan="2" style="text-align: center;">Keterangan On-Target</th>
                            <th rowspan="2" style="width: 35px;text-align: center !important;">#</th>

                        </tr>
                        <tr>
                            <th style="text-align: center;">Maqra'</th>
                            <th style="text-align: center;">Jumlah</th>

                            <th style="text-align: center;">Maqra'</th>
                            <th style="text-align: center;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- @foreach ($targets as $item)
        <div class="modal fade" id="detail-tahfidz-{{ $item->id }}">
            @include('backend.academic.tahfidz.modal')
        </div>
    @endforeach --}}

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
            window.LaravelDataTables["table-tahfidz"] = $("#table-tahfidz").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('academic.tahfidz.datatable') }}",
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
                        width: "10px",
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        class: "align-middle",
                        width: "150px",
                        data: "nama_santri"
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            if (row.jenis_kaldik === 'Ziyadah' || row.jenis_kaldik ===
                                'Murojaah Sabqi') {
                                return 'Juz ' + row.mulai_target_juz + '/Halaman ' + row
                                    .mulai_target_halaman +
                                    ' Baris ke ' + row.mulai_target_baris + ' sd Juz ' + row
                                    .akhir_target_juz +
                                    '/Halaman ' + row.akhir_target_halaman + ' Baris ke ' + row
                                    .akhir_target_baris;
                            } else {
                                return 'Juz ' + row.mulai_target_juz + '/Halaman ' + row
                                    .mulai_target_halaman + ' sd Juz ' + row.akhir_target_juz +
                                    '/Halaman ' + row.akhir_target_halaman;
                            }

                            return '';
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            if (row.jenis_kaldik === 'Ziyadah' || row.jenis_kaldik ===
                                'Murojaah Sabqi') {
                                return row.total_target + ' Baris';
                            } else {
                                return row.total_target + ' Halaman';
                            }
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => {
                            if (!row.proses) return '-'; // langsung kembalikan "-" kalau null

                            if (row.jenis_kaldik === 'Ziyadah' || row.jenis_kaldik ===
                                'Murojaah Sabqi') {
                                return 'Juz ' + row.mulai_target_juz + '/Halaman ' + row
                                    .mulai_target_halaman +
                                    ' Baris ke ' + row.mulai_target_baris + ' sd Juz ' + row.proses
                                    .capaian_target_juz +
                                    '/Halaman ' + row.proses.capaian_target_halaman + ' Baris ke ' +
                                    row.proses.capaian_target_baris;
                            } else {
                                return 'Juz ' + row.mulai_target_juz + '/Halaman ' + row
                                    .mulai_target_halaman +
                                    ' sd Juz ' + row.proses.capaian_target_juz + '/Halaman ' + row
                                    .proses.capaian_target_halaman;
                            }
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => {
                            if (!row.proses) return '-';

                            if (row.jenis_kaldik === 'Ziyadah' || row.jenis_kaldik ===
                                'Murojaah Sabqi') {
                                return row.total_capaian_target + ' Baris';
                            } else {
                                return row.total_capaian_target + ' Halaman';
                            }
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => {
                            if (!row.proses) return '-';

                            let persentase = (row.total_capaian_target / row.total_target) * 100;
                            return Math.ceil(persentase) + '%';
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => {
                            if (!row.proses) return '-';

                            let persentase = (row.total_capaian_target / row.total_target) * 100;
                            return persentase >= 100 ? 'Tercapai' : 'Tidak Tercapai';
                        }
                    },

                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_print =
                                "{{ route('academic.tahfidz.print', [':id', ':jenis_kaldik']) }}"
                                .replace(':id', row.id)
                                .replace(':jenis_kaldik', encodeURIComponent(row.jenis_kaldik));

                            return `
                                <div class="dropdown dropdown-link">
                                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="${url_print}" class="dropdown-item text-success">
                                                <i class='bx bxs-printer me-1'></i> Cetak
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
    </script>
@endpush
