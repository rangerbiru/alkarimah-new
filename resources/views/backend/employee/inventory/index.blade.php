@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/inventory-item" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="table-inventory">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.item_name') }}</th>
                            <th>{{ __('label.price') }}</th>
                            <th>{{ __('label.amount') }}</th>
                            <th>{{ __('label.item_code') }}</th>
                            <th class="text-center" style="width: 70px;">{{ __('label.aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
                    url: "{{ route('employee.inventory.datatable') }}",
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
                        render: (data, type, row, meta) => htmlEntities(row.items.name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(formatRupiah(row.price))
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => htmlEntities(row.quantity)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            return `<span>${row.inventory_code ?? '-'}</span>`
                        }
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            let url_inventory =
                                "{{ route('employee.inventory.input', ':id') }}"

                            url_inventory = url_inventory.replace(":id", row.id)
                            return `

                            <div class='d-flex gap-2 align-items-center'>
                                <button class="btn btn-info btn-xs fs-12 set-tooltip" title="Detail Inventaris" onclick="showInventoryDetail(${row.id})">
                                    Detail
                                </button>
                        <button class="btn btn-success btn-xs set-tooltip fs-12" title="Input Inventaris" onclick="inputInventory(${row.id}, '${url_inventory}')">
                            Inventaris
                        </button>
                                </div>
                            
                        `
                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });

        function inputInventory(id, url) {
            // Tampilkan konfirmasi
            if (!confirm('Yakin ingin memasukkan data ini ke inventaris?')) {
                return;
            }

            // Kirim POST request
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function(response) {
                    // Jika response berupa JSON (redirect), reload halaman
                    if (typeof response === 'object') {
                        $('#table-inventory').DataTable().ajax.reload();
                        alert('Data berhasil ditambahkan ke inventaris!');
                    } else {
                        // Jika response berupa HTML, buka di tab baru atau redirect
                        window.location.href = '{{ route('employee.inventory.index') }}';
                    }
                },
                error: function(xhr) {
                    alert('Gagal menambahkan  ' + xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
            });
        }

        // Fungsi tampilkan detail via AJAX
        function showInventoryDetail(id) {
            $.get(`/employee/inventory/${id}/modal`, function(html) {
                $('#modal-container').html(html);
                const modal = new bootstrap.Modal(document.getElementById('inventoryDetailModal'));
                modal.show();
            }).fail(function() {
                alert('Gagal memuat detail inventaris.');
            });
        }
    </script>
@endpush
