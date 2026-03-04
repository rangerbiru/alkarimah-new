<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalDetailLabel">Detail Hafalan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
            <ul style="list-style: none; padding: 0;" class="info-target">
                <li><strong>Periode Akademik</strong> : {{ $item->nama_kaldik }}</li>
                <li><strong>Nama Santri</strong> : {{ $item->nama_santri }}</li>
                <li><strong>Jumlah TM aktif</strong> : {{ $item->jumlah_tm }} Pertemuan</li>
                @if ($slugJenisKaldik == 'ziyadah')
                    <li><strong>Target per-TM</strong> : {{ $item->target_perhari }} Baris</li>
                    <li><strong>Total Target</strong> : {{ $item->total_target }} Baris</li>
                    <li><strong>Mulai Target</strong> : Juz
                        {{ $item->mulai_target_juz }} Halaman {{ $item->mulai_target_halaman }} Baris
                        {{ $item->mulai_target_baris }}</li>
                    <li><strong>Akhir Target</strong> : Juz
                        {{ $item->akhir_target_juz }} Halaman {{ $item->akhir_target_halaman }} Baris
                        {{ $item->akhir_target_baris }}</li>
                @else
                    <li><strong>Target per-TM</strong> : {{ $item->target_perhari }} Halaman</li>
                    <li><strong>Total Target</strong> : {{ $item->total_target }} Halaman</li>
                    <li><strong>Mulai Target</strong> : Juz
                        {{ $item->mulai_target_juz }} Halaman {{ $item->mulai_target_halaman }}</li>
                    <li><strong>Akhir Target</strong> : Juz
                        {{ $item->akhir_target_juz }} Halaman {{ $item->akhir_target_halaman }}</li>
                @endif
            </ul>

            <div class="table-responsive mt-3">
                <table class="table table-bordered" id="hafalan-{{ $item->id }}-{{ $slugJenisKaldik }}">
                    <thead class="table-target">
                        <tr>
                            <th rowspan="2" style="text-align: center;">No.</th>
                            <th rowspan="2" style="text-align: center;">Tanggal</th>
                            @if ($slugJenisKaldik == 'ziyadah')
                                <th colspan="2" style="text-align: center !important;">Target Ziyadah
                                </th>
                            @else
                                <th colspan="2" style="text-align: center !important;">Target Murojaah
                                </th>
                            @endif

                        </tr>
                        <tr>
                            <th style="text-align: center;">Maqra'</th>
                            <th style="text-align: center;" style="width: 20px !important;">Jumlah</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">

    <style>
        .modal-body .info-target {
            background-color: #f0fec1;
            font-size: 14px;
            line-height: 1.5;
            border-radius: 10px;
        }

        .modal-body ul li {
            padding: 10px;
        }

        .table-target tr th {
            background-color: #257f88 !important;
            color: #fff !important;
        }

        table.table-bordered.dataTable {
            width: 100% !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
        type="text/javascript"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/id.min.js"></script>

    <script>
        $(document).ready(function() {
            const slugJenisKaldik = '{{ $slugJenisKaldik }}';

            // Definisikan kolom dasar
            let columns = [{
                    data: null,
                    class: 'align-middle text-center',
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'tanggal',
                    name: 'tanggal',
                    render: function(data, type, row) {
                        return moment(data).format('dddd, DD MMMM YYYY');
                    }
                }
            ];

            // Tambahkan kolom sesuai jenis kaldik
            if (slugJenisKaldik === 'ziyadah') {
                columns.push({
                    data: 'target_ziyadah',
                    name: 'target_ziyadah'
                }, {
                    data: 'target_baris',
                    name: 'target_baris',
                    render: function(data) {
                        return data + ' Baris';
                    }
                });
            } else if (slugJenisKaldik === 'murojaah-sabqi') {
                columns.push({
                    data: 'target_murojaah',
                    name: 'target_murojaah'
                }, {
                    data: 'target_baris',
                    name: 'target_baris',
                    render: function(data) {
                        return data + ' Baris';
                    }
                });
            } else {
                columns.push({
                    data: 'target_murojaah',
                    name: 'target_murojaah'
                }, {
                    data: 'target_halaman',
                    name: 'target_halaman',
                    render: function(data) {
                        return data + ' Halaman';
                    }
                });
            }

            // Inisialisasi DataTable
            $('#hafalan-{{ $item->id }}-{{ $slugJenisKaldik }}').DataTable({
                processing: true,
                serverSide: false,
                ajax: "{{ url('employee/data-hafalan/' . $item->id . '/' . $slugJenisKaldik) }}",
                columns: columns,
            });
        });
    </script>
@endpush
