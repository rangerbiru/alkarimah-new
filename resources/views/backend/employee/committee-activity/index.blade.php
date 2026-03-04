@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/committee-activity" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-section-page-action :label="$title"
                :icon="$icon":create-route="route('employee.committee-activity.create')" />

            <div class="card-header">
                <div class="ms-auto mt-md-0">
                    <a href="{{ route('employee.committee-activity.create') }}" class="btn btn-primary label-btn">
                        {{ __('label.add') }}
                        <i class="fe fe-plus label-btn-icon me-2"></i>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="table-activity">
                        <thead>
                            <tr>
                                <th>{{ __('label.no') }}</th>
                                <th>{{ __('label.name_activity') }}</th>
                                <th>{{ __('label.responsible_person') }}</th>
                                <th>{{ __('label.location') }}</th>
                                <th>{{ __('label.date') }}</th>
                                <th class="text-center" style="width: 70px;">{{ __('label.aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @foreach ($activity as $act)
        @include('backend.employee.committee-activity.modal', ['activity' => $act])
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
                    url: "{{ route('employee.committee-activity.datatable') }}",
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
                        render: (data, type, row, meta) => htmlEntities(row.activity_name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.responsible_person)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.location)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            moment.locale('id')
                            return moment(row.activity_date).format('dddd, DD MMMM YYYY')
                        }
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_edit =
                                "{{ route('employee.committee-activity.edit', ':id') }}"
                            let url_destroy =
                                "{{ route('employee.committee-activity.destroy', ':id') }}"


                            url_edit = url_edit.replace(":id", row.id)
                            url_destroy = url_destroy.replace(":id", row.id)
                            return `<div class='d-flex gap-1 flex-column flex-md-row justify-content-center align-items-center'>
                            <button class="btn btn-primary btn-xs set-tooltip fs-12 w-100" data-bs-toggle="modal" data-bs-target="#detail-committee-${row.id}">
                            Detail
                        </button>
                            <a href="${url_edit}" class="btn btn-success btn-xs set-tooltip fs-12 w-100" title="Edit">
                            Edit
                        </a>
                        <button class="btn btn-danger btn-xs set-tooltip fs-12 w-100" title="Hapus" onclick="deleteAbsensi(${row.id}, '${url_destroy}')">
                            Hapus
                        </button>
                        </div>`
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });

        function deleteAbsensi(id, url_destroy) {
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
    </script>
@endpush
