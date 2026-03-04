@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/item" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-section-page-action :label="$title" :icon="$icon":create-route="route('hr.item.create')" />

            <div class="card-header">
                <div class="ms-auto mt-md-0">
                    <a href="{{ route('hr.item.create') }}" class="btn btn-primary label-btn">
                        {{ __('label.add') }}
                        <i class="fe fe-plus label-btn-icon me-2"></i>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="table-inventory">
                        <thead>
                            <tr>
                                <th>{{ __('label.no') }}</th>
                                <th>{{ __('label.asset_category') }}</th>
                                <th>{{ __('label.name') }}</th>
                                <th>{{ __('label.price') }}</th>
                                <th class="text-center" style="width: 70px;">{{ __('label.aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-container"></div>
@endsection

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
        type="text/javascript"></script>

    <script>
        function formatRupiah(value) {
            if (!value && value !== 0) return '-';
            let number = parseFloat(value);
            if (isNaN(number)) return '-';
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        }

        function htmlEntities(str) {
            return String(str).replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
    </script>

    <script>
        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {}
            window.LaravelDataTables["table-inventory"] = $("#table-inventory").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('hr.item.datatable') }}",
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
                        render: (data, type, row, meta) => {
                            return `${row.category.code} - ${row.category.name}`
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => formatRupiah(row.price)
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_destroy =
                                "{{ route('hr.item.destroy', ':id') }}"

                            url_destroy = url_destroy.replace(":id", row.id)
                            return `
                            <div class='d-flex gap-2 align-items-center'>
                                <button class="btn btn-info btn-xs" onclick="showItemDetail(${row.id})">
                                    Detail
                                </button>
                        <button class="btn btn-danger btn-xs set-tooltip fs-12" title="Hapus" onclick="deleteItem(${row.id}, '${url_destroy}')">
                            Hapus
                        </button>
                                </div>
                        `
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });

        function showItemDetail(id) {
            $.get(`/hr/item/${id}/modal`, function(html) {
                $('#modal-container').html(html);
                const modal = new bootstrap.Modal(document.getElementById('itemDetailModal'));
                modal.show();
            }).fail(function() {
                alert('Gagal memuat detail barang.');
            });
        }

        // Fungsi hapus
        function deleteItem(id, url_destroy) {
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
                            $('#table-inventory').DataTable().ajax.reload();
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
