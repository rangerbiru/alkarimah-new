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

                            <b>{{ number_format($count, 0, '', '.') }}</b> {{ $title }}
                        </div>
                    </div>
                </div>
                <div class="col-sm-5 col-md-9">
                    <div class="d-block d-sm-none mt-3"></div>

                    <a href="{{ route('academic.student.create') }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>
                    <button class="btn btn-success label-btn" data-bs-toggle="modal" data-bs-target="#importStudentModal">
                        <i class="fas fa-upload label-btn-icon me-2 fs-10"></i>
                        {{ __('label.upload') }}
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info label-btn dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bx bxs-cog label-btn-icon me-2"></i>
                            SETTING
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{{ route('academic.student.set') }}" class="dropdown-item">
                                    {{ __('label.set_class') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('academic.student.set.excul') }}" class="dropdown-item">
                                    {{ __('label.set_excul') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('academic.student.change') }}" class="dropdown-item">
                                    {{ __('label.change_class') }}
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('errors_import'))
                <div class="alert alert-danger">
                    <b>Data yang gagal diimport:</b>
                    <ul>
                        @foreach (session('errors_import') as $error)
                            <li>Baris ke-{{ $error['row'] }} : {{ $error['error'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table" id="table-student">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.nis') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.gender') }}</th>
                            <th>{{ __('label.class') }}</th>
                            <th>{{ __('label.parent') }}</th>
                            <th>{{ __('label.status') }}</th>
                            <th style="width: 35px;text-align: center !important;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('student.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Data Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="alert alert-info">
                            <b>Ketentuan Import:</b>

                            <ul class="mb-0 mt-2">
                                <li>File harus format <b>Excel (.xlsx)</b> atau <b>CSV</b></li>
                                <li>Baris pertama harus berisi <b>nama kolom</b></li>
                                <li><b>Kolom wajib diisi:</b>
                                    <ul>
                                        <li>nis</li>
                                        <li>nama</li>
                                        <li>jenis-kelamin</li>
                                        <li>religion</li>
                                        <li>card_number</li>
                                        <li>entry_date</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Upload File</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary">
                            Import Data
                        </button>
                    </div>
                </div>

            </form>
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
        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {}
            window.LaravelDataTables["table-student"] = $("#table-student").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('academic.student.datatable') }}",
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
                        render: (data, type, row, meta) => htmlEntities(row.nis)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => row.gender_name
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.class.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => (row.parent == null) ? "-" : htmlEntities(row
                            .parent.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => row.status_badge
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_history_move =
                                "{{ route('academic.student.history.displacement', 0) }}"
                            let url_edit = "{{ route('academic.student.edit', 0) }}"
                            let url_destroy = "{{ route('academic.student.destroy', 0) }}"

                            url_history_move = url_history_move.replace("0", row.encrypted_id)
                            url_edit = url_edit.replace("0", row.encrypted_id)
                            url_destroy = url_destroy.replace("0", row.encrypted_id)

                            return `<div class="dropdown dropdown-link">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="${url_history_move}" class="dropdown-item">
                                    <i class="ti ti-history-toggle me-2"></i>{{ __('label.move_history') }}
                                </a>
                            </li>
                            <li>
                                <a href="${url_edit}" class="dropdown-item">
                                    <i class="bx bx-pencil me-2"></i>${label_edit}
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item text-danger" onclick="deleteConfirm('${url_destroy}', false, 'table-student')">
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
    </script>
@endpush
