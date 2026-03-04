@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/hafalan" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-section-page-action :label="$title" :icon="$icon":create-route="route('employee.tahfidz.create')" />

            <div class="card-header">
                <div class="ms-auto mt-md-0">
                    <a href="{{ route('employee.hafalan.create') }}" class="btn btn-primary label-btn">
                        {{ __('label.add') }}
                        <i class="fe fe-plus label-btn-icon me-2"></i>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="table-hafalan">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Santri</th>
                                <th>Jenis Kaldik</th>
                                <th>Detail Hafalan</th>
                                <th class="text-center" style="width: 70px;">{{ __('label.aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    @foreach ($hasil as $item)
        @php
            $slugJenisKaldik = Str::slug($item->jenis_kaldik);
        @endphp

        <div class="modal fade" id="detail-{{ $item->id }}-{{ $slugJenisKaldik }}" ...>
            @include('backend.employee.hafalan.modal')
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
            let table = $('#table-hafalan').DataTable({
                processing: true,
                serverSide: false,
                ajax: "{{ route('employee.hafalan.datatable') }}",
                columns: [{
                        data: null,
                        class: 'align-middle text-center',
                        render: (data, type, row, meta) => meta.row + 1
                    },
                    {
                        data: 'nama_santri',
                        class: 'align-middle'
                    },
                    {
                        data: 'jenis_kaldik',
                        class: 'align-middle'
                    },
                    {
                        data: null,
                        class: 'text-center align-middle',
                        render: function(data, type, row) {
                            return `<button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detail-${row.id}-${row.jenis_kaldik.toLowerCase().replace(/\s+/g, '-')}">Detail</button>`;
                        }
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            // let editUrl = "{{ route('employee.hafalan.edit', ':id') }}".replace(
                            //     ':id', row.id);
                            // // Tambahkan query parameter untuk jenis_kaldik
                            // editUrl += `?jenis_kaldik=${encodeURIComponent(row.jenis_kaldik)}`;

                            return `
                            <div class="dropdown dropdown-link">
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="javascript:void(0)" class="dropdown-item text-danger" onclick="deleteHafalan(${row.id}, '${row.jenis_kaldik}')">
                                            <i class="bx bx-trash me-2"></i>Hapus
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        `;
                        }
                    }

                ]
            });
        });

        function deleteHafalan(id, jenis_kaldik) {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Data hafalan ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('employee.hafalan.custom-destroy') }}",
                        method: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id,
                            jenis_kaldik: jenis_kaldik
                        },
                        success: function(response) {
                            Swal.fire("Berhasil", response.message, "success");
                            $('#table-hafalan').DataTable().ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire("Gagal", xhr.responseJSON?.message || 'Terjadi kesalahan',
                                "error");
                        }
                    });
                }
            });
        }

        // function showDetail(data) {
        //     $('#detailPeriodeKaldik').text(data.periode_kaldik);
        //     $('#detailNamaSantri').text(data.nama_santri);
        //     $('#detailAktifTm').text(data.aktiv_tm + ' Pertemuan');
        //     if (data.jenis_kaldik === 'Murojaah Manzil') {
        //         $('#detailTargetPerhari').text(data.target_perhari + ' Halaman');
        //         $('#detailTotalTarget').text(data.total_target + ' Halaman');
        //         $('#detailMulaiTarget').text('Juz ' + data.mulai_target_juz + ' Halaman ' + data.mulai_target_halaman);
        //         $('#detailAkhirTarget').text('Juz ' + data.akhir_target_juz + ' Halaman ' + data.akhir_target_halaman);
        //     } else {
        //         $('#detailTargetPerhari').text(data.target_perhari + ' Baris');
        //         $('#detailTotalTarget').text(data.total_target + ' Baris');
        //         $('#detailMulaiTarget').text('Juz ' + data.mulai_target_juz + ' Halaman ' + data.mulai_target_halaman +
        //             ' Baris ' + data.mulai_target_baris);
        //         $('#detailAkhirTarget').text('Juz ' + data.akhir_target_juz + ' Halaman ' + data.akhir_target_halaman +
        //             ' Baris ' + data.akhir_target_baris);
        //     }
        //     $('#detailId').text(data.id);
        //     $('#modalDetail').modal('show');
        // }
    </script>
@endpush
