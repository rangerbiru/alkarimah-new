@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/activity-report" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-section-page-action :label="$title"
                :icon="$icon":create-route="route('employee.activity-report.create')" />

            <div class="card-header">
                <div class="ms-auto mt-md-0">
                    <a href="{{ route('employee.activity-report.create') }}" class="btn btn-primary label-btn">
                        {{ __('label.add') }}
                        <i class="fe fe-plus label-btn-icon me-2"></i>
                    </a>
                    @if ($isPimpinan)
                        <a href="{{ route('employee.activity-report.export') }}" class="btn btn-success label-btn">
                            {{ __('label.export') }}
                            <i class="fe fe-file label-btn-icon me-2"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="table-activity">
                        <thead>
                            <tr>
                                <th>{{ __('label.no') }}</th>
                                <th>{{ __('label.name_activity') }}</th>
                                <th>{{ __('label.reporter') }}</th>
                                <th>{{ __('label.description') }}</th>
                                <th>{{ __('label.photo') }}</th>
                                <th>{{ __('label.comment') }}</th>
                                <th class="text-center" style="width: 70px;">{{ __('label.aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
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
        const IS_HEAD_DEPARTMENT = {{ $isHeadDepartment ? 'true' : 'false' }};
        const EMPLOYEE = {{ $employee->id ?? 'null' }};
        console.log(EMPLOYEE);

        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {}
            window.LaravelDataTables["table-activity"] = $("#table-activity").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('employee.activity-report.datatable') }}",
                    type: "POST"
                },
                processing: true,
                responsive: true,
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
                        render: (data, type, row, meta) => htmlEntities(row.activity.activity_name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.employee.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.description)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            return `${row.photo ? `<img src="/storage/activity-report/${row.photo}" class="img-fluid object-fit-cover" width="50" height="50" />` : '-'}`
                        }
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: (data, type, row) => htmlEntities(row.comment ? row.comment : '-')
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let buttons = [];

                            if (IS_HEAD_DEPARTMENT && !row.comment) {
                                buttons.push(
                                    `<button class="btn btn-info btn-xs set-tooltip fs-12" title="Komentar" onclick="addComment(${row.id})">
                                    Komentar
                                </button>`
                                );
                            }

                            if (!IS_HEAD_DEPARTMENT && !row.comment && EMPLOYEE == row
                                .id_employee) {
                                let url_edit =
                                    "{{ route('employee.activity-report.edit', ':id') }}"
                                    .replace(':id', row.id);
                                buttons.push(
                                    `<a href="${url_edit}" class="btn btn-success btn-xs set-tooltip fs-12" title="Edit">
                                Edit
                            </a>`
                                );

                                buttons.push(
                                    `<button class="btn btn-danger btn-xs set-tooltip fs-12" title="Hapus" onclick="deleteAbsensi(${row.id})">
                                Hapus
                            </button>`
                                );
                            }

                            // Gabungkan semua tombol dalam flex column
                            return `<div class="d-flex flex-column gap-2">${buttons.join('')}</div>`;
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });

        function deleteAbsensi(id) {
            let url_destroy = `{{ route('employee.activity-report.destroy', ':id') }}`.replace(':id', id);
            // let url_destroy = "{{ url('employee/destroy') }}/" + id

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
                            $('#table-activity').DataTable().ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus.', 'error');
                        }
                    });
                }
            });
        }

        function addComment(id) {
            Swal.fire({
                title: 'Tambah Komentar',
                input: 'textarea',
                inputPlaceholder: 'Tulis komentar...',
                inputAttributes: {
                    'maxlength': '1000',
                    'rows': '4'
                },
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                preConfirm: (comment) => {
                    if (!comment || comment.trim() === '') {
                        Swal.showValidationMessage('Komentar tidak boleh kosong');
                        return false;
                    }
                    return comment.trim();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('employee.activity-report.comment', ':id') }}".replace(':id', id),
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            comment: result.value
                        },
                        success: function(response) {
                            Swal.fire('Berhasil!', response.message, 'success');
                            $('#table-activity').DataTable().ajax.reload(null,
                                false); // reload tanpa reset halaman
                        },
                        error: function(xhr) {
                            let message = 'Gagal menyimpan komentar.';
                            if (xhr.responseJSON?.message) {
                                message = xhr.responseJSON.message;
                            }
                            Swal.fire('Error!', message, 'error');
                        }
                    });
                }
            });
        }
    </script>
@endpush
