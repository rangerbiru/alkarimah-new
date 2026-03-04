@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/lunch-report" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-section-page-action :label="$title"
                :icon="$icon":create-route="route('employee.activity-report.create')" />

            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex flex-column gap-2">
                        <h5 class="mb-0" id="page-title">{{ $title }}</h5>
                        <span class="badge bg-success fs-14" id="total-lunch-badge" style="width: fit-content">Total :
                            {{ $totalLunch }}
                            Porsi</span>
                    </div>
                    <!-- Filter Tanggal -->
                    <div class="input-group" style="width: auto;">
                        <span class="input-group-text">
                            <i class="bx bx-calendar"></i>
                        </span>
                        <input type="date" id="filter-date" class="form-control"
                            value="{{ \Carbon\Carbon::today()->toDateString() }}">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="table-activity">
                        <thead>
                            <tr>
                                <th>{{ __('label.no') }}</th>
                                <th>{{ __('label.field_name') }}</th>
                                <th>{{ __('label.total_lunch') }}</th>
                                <th>{{ __('label.name_employee_lunch') }}</th>
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
        $(document).ready(function() {
            function formatDateForTitle(dateString) {
                if (!dateString) return 'Tanggal tidak valid';
                const date = new Date(dateString);
                const day = String(date.getDate()).padStart(2, '0');
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                    'September', 'Oktober', 'November', 'Desember'
                ];
                return `${day} ${months[date.getMonth()]} ${date.getFullYear()}`;
            }

            // Ambil elemen judul
            const $title = $('#page-title');
            const $dateInput = $('#filter-date');

            // Set judul awal
            const initialDate = $dateInput.val();
            $title.text(`Rekap Absensi Makan Tanggal: ${formatDateForTitle(initialDate)}`);

            window.LaravelDataTables = window.LaravelDataTables || {}
            const table = $("#table-activity").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('employee.lunch-report.datatable') }}",
                    type: "POST",
                    data: function(d) {
                        d.filter_date = $('#filter-date').val();
                    }
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
                    });

                    const response = table.ajax.json();
                    if (response && response.total_lunch_summary !== undefined) {
                        $('#total-lunch-badge').text(`Total : ${response.total_lunch_summary} Porsi`);
                    }
                },
                columns: [{
                        class: "align-middle",
                        width: "50px",
                        searchable: false,
                        orderable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'task_main',
                        class: "align-middle",
                        render: (data) => htmlEntities(data)
                    },
                    {
                        data: 'total',
                        class: "align-middle",
                        render: (data) => htmlEntities(data)
                    },
                    {
                        data: 'employee_names',
                        class: "align-middle employee-names-cell",
                        render: (data) => {
                            if (!data || data === '-') return '-';
                            const names = data.split('||').map(name =>
                                `<span>${htmlEntities(name.trim())}</span>`).join('<br>');
                            return names;
                        }
                    }
                ]
            })

            $dateInput.on('change', function() {
                const selectedDate = $(this).val();
                $title.text(`Rekap Absensi Makan Tanggal: ${formatDateForTitle(selectedDate)}`);
                table.ajax.reload();
            });

            $($.fn.dataTable.tables(true)).css('width', '100%')
        });
    </script>
@endpush

<style>
    .employee-names-cell {
        max-width: 300px;
        white-space: pre-line;
        word-wrap: break-word;
        line-height: 1.4;
        font-size: 0.875rem;
    }
</style>
