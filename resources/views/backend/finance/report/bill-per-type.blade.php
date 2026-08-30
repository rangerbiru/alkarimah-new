@extends('layouts.backend.index')
@section('title', $title)

@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="finance/report/bill-per-type" />
@endsection

@section('content')
    <div class="card custom-card">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                <div class="col-sm-6 col-md-3">
                    <label class="form-label">{{ __('label.school_year') }}</label>
                    <x-form.select id="year" :option="$years" :data-placeholder="__('label.choose_school_year')" :old="$year->id" />
                </div>

                <div class="col-sm-6 col-md-3">
                    <label class="form-label">{{ __('label.class') }}</label>
                    <x-form.select id="class" :option="$classes" :data-placeholder="__('label.choose') . ' ' . __('label.class')" data-allow-clear="true" />
                </div>

                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Jenis Tagihan</label>
                    <x-form.select id="bill-type" :option="$bill_types" data-placeholder="Pilih Jenis Tagihan" />
                </div>

                <div class="col-sm-6 col-md-3">
                    <button type="button" id="btn-search" class="btn btn-secondary px-4 w-100">
                        <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div id="card-info" class="row mb-3">
                <div class="col-sm-6 col-md-9">
                    <button type="button" id="btn-download-excel" class="btn btn-success label-btn">
                        <i class="fa-solid fa-file-excel label-btn-icon me-2"></i> DOWNLOAD EXCEL
                    </button>
                    <button type="button" id="btn-download-pdf" class="btn btn-danger label-btn">
                        <i class="fa-solid fa-file-pdf label-btn-icon me-2"></i> DOWNLOAD PDF
                    </button>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="alert alert-outline-info">
                        <div class="clearfix">
                            <div class="float-end"><i class="bx bx-credit-card-front" style="font-size: 16px;"></i></div>
                            <b>Total : Rp. <span id="total">0</span></b>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-report">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('label.no') }}</th>
                            <th>NIS</th>
                            <th>{{ __('label.student_name') }}</th>
                            <th>{{ __('label.class') }}</th>
                            <th class="text-end">Total Tagihan</th>
                            <th class="text-end">Sudah Dibayar</th>
                            <th class="text-end">Sisa Tagihan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div id="start" class="text-center my-4">
                    <img src="{{ asset('images/vectors/search.png') }}" class="img-fluid" style="height: 230px;" />
                    <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
                        <b>Cari Tagihan Berdasarkan Kelas & Jenis Tagihan</b><br />Silakan gunakan filter di atas untuk
                        menampilkan data.
                    </h6>
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
        window.LaravelDataTables = window.LaravelDataTables || {}
        let year = "{{ $year->id }}"
        let class_id = ""
        let bill_type = ""
        let datatable = false

        $(document).ready(function() {
            $("#card-info").hide()

            $("#year").change(function() {
                year = $(this).val();
                load();
            })

            $("#bill-type").change(function() {
                bill_type = $(this).val();
                load();
            })

            $("#class").change(function() {
                class_id = $(this).val();
                load();
            })

            $("#btn-search").click(function() {
                year = $("#year").val()
                class_id = $("#class").val()
                bill_type = $("#bill-type").val()
                load()
            })

            $("#btn-download-excel").click(function() {
                window.location =
                    `{{ route('finance.report.download.excel.bill-per-type') }}?year=${year}&class=${class_id}&bill_type=${bill_type}`
            })

            $("#btn-download-pdf").click(function() {
                window.location =
                    `{{ route('finance.report.download.pdf.bill-per-type') }}?year=${year}&class=${class_id}&bill_type=${bill_type}`
            })
        })

        function load() {
            if (class_id == "" || bill_type == "") return false

            const formData = {
                year: year,
                class: class_id,
                bill_type: bill_type
            }

            $.ajax({
                type: "POST",
                url: "{{ route('finance.report.get.total-bill-per-type') }}",
                data: formData,
                dataType: "json",
                success: function(response) {
                    $("#total").html(moneyFormat(response.data.total))
                }
            })

            if (datatable) {
                window.LaravelDataTables["table-report"].ajax.reload()
            } else {
                datatable = true
                $("#start").hide()
                $("#card-info").show()
                window.LaravelDataTables["table-report"] = $("#table-report").DataTable({
                    language: {
                        search: "",
                        searchPlaceholder: `${label_search}...`,
                        lengthMenu: "_MENU_ Data",
                        emptyTable: label_nodata
                    },
                    ajax: {
                        url: "{{ route('finance.report.datatable.bill-per-type') }}",
                        type: "POST",
                        data: (d) => {
                            d.year = year
                            d.class = class_id
                            d.bill_type = bill_type
                            return d
                        }
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
                            class: "align-top text-center",
                            searchable: false,
                            render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                        },
                        {
                            class: "align-top text-center",
                            render: (data, type, row, meta) => htmlEntities(row.nis)
                        },
                        {
                            class: "align-top",
                            render: (data, type, row, meta) => htmlEntities(row.student_name)
                        },
                        {
                            class: "align-top text-center",
                            render: (data, type, row, meta) => htmlEntities(row.class_name)
                        },
                        {
                            class: "align-top text-end",
                            render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total_amount)}`
                        },
                        {
                            class: "align-top text-end",
                            render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total_paid)}`
                        },
                        {
                            class: "align-top text-end",
                            render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total_remaining)}`
                        }
                    ]
                })
                $($.fn.dataTable.tables(true)).css('width', '100%')
            }
        }
    </script>
@endpush
