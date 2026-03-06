@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/absensi/create" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('employee.tahfidz.store') }}" class="form-block">
                <div class="card">
                    <div class="card-body">
                        @csrf
                        <input type="hidden" name="id_halaqoh" id="id_halaqoh">
                        <input type="hidden" name="id_pegawai" value="{{ $dataHalaqoh->pluck('id_pegawai')->last() }}">

                        <div class="row">
                            <div class="col-md-4">
                                <x-form.input-text name="nama_lembaga_display" :label="__('label.lembaga_name')" :old="'Ponpes Al-Karimah'" readonly
                                    disabled />
                                <input type="hidden" name="nama_lembaga" value="Ponpes Al-Karimah">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                {{-- <x-form.select name="periode_akademik" id="periode_akademik" :label="__('label.kaldik_periode')"
                                    :old="$dataHalaqoh->pluck('periode_kaldik')" :option="$dataPeriode->pluck('nama_semester', 'nama_semester')->toArray()" /> --}}
                                <x-form.input-text name="periode_akademik_display" id="periode_akademik" :label="__('label.kaldik_periode')"
                                    :old="$dataPeriode->last()->nama_semester" readonly disabled />
                                <input type="hidden" name="periode_akademik"
                                    value="{{ $dataPeriode->last()->nama_semester }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <x-form.select id="nama_kaldik" :label="__('label.kaldik_name')" :old="$dataHalaqoh->pluck('nama_kaldik')" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                {{-- <x-form.input-text name="nama_pengajar" id="nama_pengajar" :label="__('label.teacher_name')"
                                    :old="$dataHalaqoh->pluck('nama_pengampu')->last()" readonly /> --}}
                                <input type="hidden" name="nama_pengajar" id="nama_pengajar"
                                    value="{{ $dataHalaqoh->pluck('nama_pengampu')->last() }}" readonly>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                {{-- <x-form.input-text name="nama_halaqoh" id="nama_halaqoh" :label="__('label.halaqoh_name')" :old="'Ustadz ' . $dataHalaqoh->pluck('nama_pengampu')->last()"
                                    readonly /> --}}
                                <input type="hidden" name="nama_halaqoh" id="nama_halaqoh" readonly>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <x-form.select name="pertemuan_kbm" :option="[]" id="pertemuan_kbm" :label="__('label.attendance')"
                                    :old="old('name')" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <x-form.text-area name="materi_kelas" :label="__('label.material')" :old="old('materi_kelas')" />
                            </div>
                        </div>

                        <x-section-form :label="__('label.learning_steps')" icon="bx bxs-graduation" />
                        <div class="row">
                            <x-form.radio name="pembukaan_doa" :label="__('label.opening')" :option="['Ya', 'Tidak']" />
                            <x-form.radio name="apersepsi" :label="__('label.apperception')" :option="['Ya', 'Tidak']" />
                            <x-form.radio name="evaluasi" :label="__('label.evaluation')" :option="['Ya', 'Tidak']" />
                            <x-form.radio name="doa_penutup" :label="__('label.closing')" :option="['Ya', 'Tidak']" />

                            <div class="col-sm-6 col-md-4">
                                <x-form.text-area name="catatan" :label="__('label.note')" :old="old('catatan')" />
                            </div>

                        </div>

                        <x-form.button-submit :cancel-route="route('employee.tahfidz.index')" />
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="siswaTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nomor</th>
                                        <th>Hari</th>
                                        <th>Nama</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>


        </div>
    </div>

    <script>
        const dataHalaqoh = @json($dataHalaqoh);

        function filterKaldik(selectedPeriode) {
            const filteredKaldik = dataHalaqoh.filter(item => item.periode_kaldik === selectedPeriode);

            const namaKaldikSelect = $('#nama_kaldik');
            namaKaldikSelect.empty().append('<option value="">Pilih Kaldik</option>');

            filteredKaldik.forEach(item => {
                namaKaldikSelect.append($('<option>', {
                    value: item.nama_kaldik,
                    text: item.nama_kaldik
                }));
            });

            $('#id_halaqoh').val('');
            $('#nama_halaqoh').val('');
            $('#pertemuan_kbm').empty().append('<option value="">Pilih Pertemuan</option>');
        }

        $(document).ready(function() {
            const selectedPeriode = $('#periode_akademik').val();
            if (selectedPeriode) {
                filterKaldik(selectedPeriode);
            }
        });



        $('#nama_kaldik').on('change', function() {
            const namaKaldik = $(this).val();
            const selectedKaldik = dataHalaqoh.find(item => item.nama_kaldik === namaKaldik);

            if (!selectedKaldik) return;

            $('#id_halaqoh').val(selectedKaldik.id_halaqoh);

            const namaHalaqoh = `${selectedKaldik.nama_pengampu} | ${selectedKaldik.jenis_kaldik}`;
            $('#nama_halaqoh').val(namaHalaqoh);

            const pertemuan = $('#pertemuan_kbm');
            pertemuan.empty().append('<option value="">Pilih Pertemuan</option>');

            const url_pertemuan = `{{ route('employee.tahfidz.pertemuan-terpakai', ':id_halaqoh') }}`.replace(
                ':id_halaqoh', selectedKaldik.id_halaqoh);

            // $.get(url_pertemuan, function(pertemuanTerpakai) {
            //     if (selectedKaldik.aktiv_tm) {
            //         for (let i = 1; i <= selectedKaldik.aktiv_tm; i++) {
            //             const isDisabled = pertemuanTerpakai.includes(i);
            //             pertemuan.append($('<option>', {
            //                 value: i,
            //                 text: `Pertemuan ${i}`,
            //                 disabled: isDisabled
            //             }));
            //         }
            //     }
            // });
            $.get(url_pertemuan, function(pertemuanTerpakai) {
                if (selectedKaldik.aktiv_tm) {
                    for (let i = 1; i <= selectedKaldik.aktiv_tm; i++) {
                        if (pertemuanTerpakai.includes(i)) {
                            continue;
                        }

                        pertemuan.append($('<option>', {
                            value: i,
                            text: `Pertemuan ${i}`
                        }));
                    }
                }
            });

        });
    </script>



    @push('styles')
        <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
        <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
            type="text/javascript"></script>
        <script>
            let pertemuanKe = '';
            let table;

            $(document).ready(function() {
                table = $('#siswaTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('employee.tahfidz.get-siswa-by-pengampu') }}",
                        type: "GET",
                        data: function(d) {
                            d.nama_kaldik = $('#nama_kaldik').val(); // ambil nama_kaldik dari form
                        }
                    },
                    columns: [{
                            data: 'nomor',
                            name: 'nomor'
                        },
                        {
                            data: null,
                            title: 'Hari',
                            render: function() {
                                return `Hari Ke-${pertemuanKe}`;
                            }
                        },
                        {
                            data: 'nama',
                            name: 'nama'
                        },
                        {
                            data: null,
                            title: 'Keterangan',
                            render: function(data, type, row, meta) {
                                return `
                        <select class="form-select keterangan-select" name="keterangan[${meta.row}]">
                            <option value="hadir" selected>Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    `;
                            }
                        }
                    ]
                });

                // Reload DataTable saat pertemuan dipilih
                $('#pertemuan_kbm').on('change', function() {
                    pertemuanKe = $(this).val();
                    table.ajax.reload(null, false);
                });

                // Reload DataTable saat nama_kaldik dipilih
                $('#nama_kaldik').on('change', function() {
                    table.ajax.reload(null, false);
                });
            });
        </script>
    @endpush
@endsection
