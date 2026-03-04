@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/student" />
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

                    <a href="{{ route('academic.student-permit-group.create') }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>

                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-student-permit-group">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.pengampu_name') }}</th>
                            <th>{{ __('label.permit_group_name') }}</th>
                            <th>{{ __('label.detail') }}</th>
                            <th style="width: 35px;text-align: center !important;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>


    @foreach ($permitGroups as $groupId => $group)
        <div class="modal fade" id="detail-{{ $groupId }}" tabindex="-1"
            aria-labelledby="modalLabel-{{ $groupId }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel-{{ $groupId }}">Detail Grup -
                            {{ $group->first()->group_name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <h6>{{ __('label.pengampu_name') }}</h6>
                        <p>Ustadz {{ $group->first()->ustadz->name ?? '-' }}</p>

                        <h6>{{ __('label.description') }}</h6>
                        <p>{{ $group->first()->description ?? '-' }}</p>

                        <h6>Daftar Siswa</h6>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('label.no') }}</th>
                                    <th>{{ __('label.student_name') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group as $index => $student)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->student_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
            window.LaravelDataTables["table-student-permit-group"] = $("#table-student-permit-group").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('academic.student-permit-group.datatable') }}",
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
                        render: (data, type, row, meta) => `Ustadz ${htmlEntities(row.ustadz_name)}`
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.group_name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) =>
                            `<button class='btn btn-info btn-sm' data-bs-toggle="modal" data-bs-target="#detail-${row.group_id}">Detail</button>`
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_edit =
                                "{{ route('academic.student-permit-group.edit', ':id') }}"
                            let url_destroy =
                                "{{ route('academic.student-permit-group.destroy', ':id') }}";

                            url_edit = url_edit.replace(":id", row.group_id)
                            url_destroy = url_destroy.replace(":id", row.group_id)

                            return `<div class="dropdown dropdown-link">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            
                            <li>
                                <a href="${url_edit}" class="dropdown-item">
                                    <i class="bx bx-pencil me-2"></i>${label_edit}
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item text-danger" onclick="deleteConfirmNew('${url_destroy}', true, 'table-student-permit-group')">
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
