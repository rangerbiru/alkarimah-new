@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/absensi/process" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('employee.tahfidz.process.store', $absensi->id) }}" class="form-block">
                <div class="card">
                    <div class="card-body">
                        @csrf

                        @include('backend.employee.tahfidz.form-process')

                        <x-form.button-submit :cancel-route="route('employee.tahfidz.index')" />
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="table-process" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nomor</th>
                                        <th>Nama</th>
                                        <th>Hari</th>
                                        <th>Target Ziyadah</th>
                                        <th>Capaian Ziyadah</th>
                                        <th>Capaian Target Baris / Halaman</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
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

    @push('styles')
        <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    @endpush

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/id.min.js"></script>



    @push('scripts')
        <script>
            moment.locale('id');
            $(document).ready(function() {
                const anyDataProses = "{{ route('employee.tahfidz.process.datatable', $absensi->id) }}";

                $('#table-process').DataTable({
                    ajax: anyDataProses,
                    processing: true,
                    responsive: true,
                    columns: [{
                            data: null,
                            className: "align-middle text-center",
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: "student.name",
                            className: "align-middle"
                        },
                        {
                            data: null,
                            name: 'pertemuan',
                            className: "align-middle",
                            render: function(data, type, row) {
                                return 'Ke-' + row.pertemuan;
                            }
                        },
                        @if ($jenisKaldik === 'Ziyadah' || $jenisKaldik === 'Murojaah Sabqi')
                            {
                                data: null,
                                name: 'mulai_target',
                                className: "align-middle",
                                render: function(data, type, row) {
                                    return 'Juz ' + row.mulai_proses_juz + ' Halaman ' + row
                                        .mulai_proses_halaman + ' Baris ' + row.mulai_proses_baris;
                                }
                            }, {
                                data: null,
                                name: 'capaian_target',
                                className: "align-middle",
                                render: function(data, type, row) {
                                    return 'Juz ' + row.capaian_target_juz + ' Halaman ' + row
                                        .capaian_target_halaman + ' Baris ' + row.capaian_target_baris;
                                }
                            }, {
                                data: 'capaian_target',
                                name: 'capaian_target',
                                className: "align-middle",
                                render: function(data, type, row) {
                                    return row.capaian_target + ' Baris';
                                }
                            },
                        @else
                            {
                                data: null,
                                name: 'mulai_target',
                                className: "align-middle",
                                render: function(data, type, row) {
                                    return 'Juz ' + row.mulai_proses_juz + ' Halaman ' + row
                                        .mulai_proses_halaman;
                                }
                            }, {
                                data: null,
                                name: 'capaian_target',
                                className: "align-middle",
                                render: function(data, type, row) {
                                    return 'Juz ' + row.capaian_target_juz + ' Halaman ' + row
                                        .capaian_target_halaman;
                                }
                            }, {
                                data: 'capaian_target',
                                name: 'capaian_target',
                                className: "align-middle",
                                render: function(data, type, row) {
                                    return row.capaian_target + ' Halaman';
                                }
                            },
                        @endif {
                            data: 'tanggal',
                            name: 'tanggal',
                            className: "align-middle",
                            render: function(data, type, row) {
                                return moment(data).format('dddd, DD MMMM YYYY');
                            }
                        },
                        {
                            data: null,
                            className: "align-middle text-center",
                            render: function(data, type, row) {
                                const destroyRoute =
                                    "{{ route('employee.tahfidz.process.destroy', 'row.id') }}";
                                const sendRoute =
                                    "{{ route('employee.tahfidz.process.send-message', 'row.student.id_parent') }}";
                                return `
                        <div class="d-flex gap-2 flex-column">
                            <a href="javascript:void(0)" class="btn btn-success btn-xs set-tooltip d-flex justify-content-center align-items-center" title="Send Message" onclick="sendProcessMessage(${row.student.id})">
                                <i class='bx bxl-whatsapp' style="font-size: 20px; !important"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip d-flex justify-content-center align-items-center" title="Delete" onclick="deleteProsesAbsensi(${row.id})">
                                <i class='bx bxs-trash' style="font-size: 18px; !important" ></i>
                            </a>
                        </div>
                    `;
                            }
                        }
                    ]
                });
            });

            function sendProcessMessage(id) {
                let url_send = `{{ route('employee.tahfidz.process.send-message', ':id') }}`.replace(':id', id);

                Swal.fire({
                    title: 'Apakah Anda Akan Mengirim Pesan?',
                    text: "Data proses tahfidz akan dikirim!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, kirim!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Sedang mengirim pesan...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: url_send,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                Swal.fire('Berhasil!', 'Pesan berhasil dikirim.', 'success');
                                $('#table-process').DataTable().ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Gagal!', 'Terjadi kesalahan saat mengirim.', 'error');
                            }
                        });
                    }
                });
            }


            function deleteProsesAbsensi(id) {
                let url_destroy = `{{ route('employee.tahfidz.process.destroy', ':id') }}`.replace(':id', id);

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url_destroy,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                Swal.fire('Berhasil!', 'Data telah dihapus.', 'success');
                                $('#table-process').DataTable().ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus.', 'error');
                            }
                        });
                    }
                });
            }
        </script>
    @endpush

@endsection
