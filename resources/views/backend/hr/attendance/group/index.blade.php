@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/attendance/group" />
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

                    <a href="{{ route('hr.attendance.group.create') }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>

                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-attendance-group">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.group_name') }}</th>
                            <th>{{ __('label.position') }}</th>
                            <th>{{ __('label.shift_work') }}</th>
                            <th class="text-center" style="width: 70px;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($groups as $group)
        @php
            $days = [
                'senin' => 'Senin',
                'selasa' => 'Selasa',
                'rabu' => 'Rabu',
                'kamis' => 'Kamis',
                'jumat' => 'Jumat',
                'sabtu' => 'Sabtu',
                'minggu' => 'Minggu',
            ];

        @endphp

        {{-- @if ($group->shift_work === 'N')
            @include('backend.hr.attendance.group.modal-n')
        @else
            @include('backend.hr.attendance.group.modal-shift')
        @endif --}}

        @foreach ($groups as $group)
            @include('backend.hr.attendance.group.modal')
        @endforeach
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
        $(document).on('click', '.btn-save-schedule', function() {
            const groupId = $(this).data('group-id');
            const modal = $(`#detail-${groupId}`);

            const isActiveShift = modal.find('#shift-tab-' + groupId).hasClass('active');
            const form = isActiveShift ?
                modal.find('.form-shift') :
                modal.find('.form-nonshift');

            if (!form.length) return;

            const url = form.attr('action');

            Swal.fire({
                title: "Simpan Perubahan?",
                text: "Pastikan data jadwal sudah benar sebelum menyimpan.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, Simpan",
                cancelButtonText: "Batal",
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST', // ← Gunakan POST, bukan PUT/PATCH
                        data: {
                            _token: "{{ csrf_token() }}",
                            _method: 'PUT', // ← Laravel akan baca ini sebagai PUT
                            ...Object.fromEntries(new FormData(form[0])
                        .entries()) // Serialize form properly
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Menyimpan...',
                                allowOutsideClick: false,
                                didOpen: () => Swal.showLoading()
                            });
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message || 'Jadwal berhasil diperbarui.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                modal.modal('hide');
                                if (window.LaravelDataTables[
                                    "table-attendance-group"]) {
                                    window.LaravelDataTables["table-attendance-group"]
                                        .ajax.reload(null, false);
                                }
                            });
                        },
                        error: function(xhr) {
                            Swal.fire("Gagal!", xhr.responseJSON?.message ||
                                "Terjadi kesalahan saat menyimpan data.", "error");
                            console.error('Error:', xhr.responseText);
                        }
                    });
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {}
            window.LaravelDataTables["table-attendance-group"] = $("#table-attendance-group").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('hr.attendance.group.datatable') }}",
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
                        render: (data, type, row, meta) => {
                            return '<div class="text-capitalize">' + htmlEntities(row.group_name) +
                                '</div>'
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row, meta) => {
                            return '<div class="text-capitalize">' + htmlEntities(row.position
                                    ?.name ?? '-') +
                                '</div>'
                        }
                    },
                    {
                        class: "align-middle",
                        searchable: false,
                        render: (data, type, row, meta) => {
                            const selectedYa = row.shift_work === 'Y' ? 'selected' : '';
                            const selectedTidak = row.shift_work === 'N' ? 'selected' : '';

                            return `
            <div class="text-capitalize">
                <select class="form-select form-select-sm shift-work-select" 
                        data-id="${row.id}" 
                        data-old-value="${row.shift_work}"
                        style="min-width: 80px; cursor: pointer;">
                    <option value="Y" ${selectedYa}>Ya</option>
                    <option value="N" ${selectedTidak}>Tidak</option>
                </select>
            </div>
        `;
                        }
                    },

                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: function(data, type, row) {
                            // let url_edit = "{{ route('hr.attendance.group.edit', ':id') }}";
                            let url_destroy = "{{ route('hr.attendance.group.destroy', ':id') }}";

                            // url_edit = url_edit.replace(':id', row.id);
                            url_destroy = url_destroy.replace(':id', row.id);


                            return `
                                <div class='group-btn-action'>
                                         <button class="d-flex align-items-center gap-1 btn w-100 btn-success btn-xs set-tooltip"
                                                        data-bs-toggle="modal" data-bs-target="#detail-${row.id}">
                                                        <i class='bx bx-search-alt-2'></i> Detail
                                                </button>

                                    <a href="javascript:void(0)" 
                                        class="d-flex align-items-center gap-1 btn w-100 btn-danger btn-xs set-tooltip"
                                        onclick="deleteConfirmNew('${url_destroy}', true, 'table-attendance-group')">
                                        <i class="bx bx-trash"></i> Delete
                                    </a>
                                </div>
                            `;

                        }
                    }
                ]
            })

            $($.fn.dataTable.tables(true)).css('width', '100%')

            $('#table-attendance-group').on('change', '.shift-work-select', function() {
                const select = $(this);
                const id = select.data('id');
                const newValue = select.val();
                const oldValue = select.data('old-value');

                if (newValue === oldValue) {
                    return;
                }

                const newText = newValue === 'Y' ? 'Ya' : 'Tidak';
                const oldText = oldValue === 'Y' ? 'Ya' : 'Tidak';

                Swal.fire({
                    title: "Konfirmasi Perubahan",
                    html: `Ubah status shift dari <b>${oldText}</b> menjadi <b>${newText}</b>?`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Ubah",
                    cancelButtonText: "Batal",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('hr.attendance.group.update.shift', ':id') }}"
                                .replace(':id', id),
                            type: 'PATCH',
                            data: {
                                _token: "{{ csrf_token() }}",
                                shift_work: newValue
                            },
                            success: function(res) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: res.message ||
                                        'Data berhasil diperbarui.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    select.data('old-value', newValue);

                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                select.val(oldValue);

                                const errorMsg = xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat memperbarui data.';
                                Swal.fire("Gagal cuy!", errorMsg, "error");
                            }
                        });
                    } else {
                        select.val(oldValue);
                    }
                });
            });
        });

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

<style>
    .group-btn-action {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    #table-attendance-group .form-select-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 4px;
        width: 20px
    }

    #table-attendance-group .form-select-sm:focus {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        border-color: #86b7fe;
    }
</style>
