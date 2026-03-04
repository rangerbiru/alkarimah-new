@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/permit" />
@endsection

@php
    function badgeClass($status)
    {
        return match ($status) {
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'pending' => 'bg-info',
            default => 'bg-primary',
        };
    }
@endphp

@section('content')
    <div class="card">
        <div class="card-body p-0 bg-light rounded-2">
            <div class="d-flex flex-column p-3 border-bottom bg-white rounded-2">
                <div class="d-flex justify-content-between align-items-center ">
                    <h5 class="mb-0">{{ __('label.employee_permit_list') }}</h5>
                    @if (!$isPengurus)
                        <a href="{{ route('employee.permit.create') }}" class="btn btn-primary label-btn">
                            {{ __('label.add') }}
                            <i class="fe fe-plus label-btn-icon me-2"></i>
                        </a>
                    @endif
                </div>
                <div class="pt-3">
                    <form method="GET" action="{{ route('employee.permit.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari perizinan..."
                                value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>



            <div class="p-lg-3 p-2">
                @if (!$isPengurus)
                    <div class="mb-4">
                        <h5 class="mb-2 p-2 text-success">
                            <i class="fas fa-user me-2"></i> Izin Pribadi
                        </h5>
                        @if ($groupedOwnPermits->isEmpty())
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                <p class="mb-0">Belum ada izin pribadi.</p>
                            </div>
                        @else
                            @foreach ($groupedOwnPermits as $date => $permits)
                                <div class="mb-3">
                                    <h6 class="text-muted fs-12 px-1">
                                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</h6>
                                    @foreach ($permits as $permit)
                                        @include('backend.employee.permit.list-item', [
                                            'permit' => $permit,
                                        ])
                                    @endforeach
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                @if ($isHeadOfDepartment && $groupedSubordinatePermits->isNotEmpty())
                    <div class="mb-4">
                        <h5 class="mb-2 p-2 text-primary">
                            <i class="fas fa-users me-2"></i> Izin Pegawai
                        </h5>
                        @if ($groupedSubordinatePermits->isEmpty())
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-user-friends fa-2x mb-2"></i>
                                <p class="mb-0">Belum ada izin dari pegawai lain.</p>
                            </div>
                        @else
                            @foreach ($groupedSubordinatePermits as $date => $permits)
                                <div class="mb-3">
                                    <h6 class="text-muted fs-12 px-1">
                                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</h6>
                                    @foreach ($permits as $permit)
                                        @include('backend.employee.permit.list-item', [
                                            'permit' => $permit,
                                        ])
                                    @endforeach
                                </div>
                            @endforeach
                        @endif
                    </div>

                @endif

                @if ($isPengurus)
                    <div class="mb-4">
                        <h5 class="mb-2 p-2 text-primary">
                            <i class="fas fa-user-friends me-2"></i> Izin Kepala Bidang
                        </h5>

                        @if ($groupedHeadPermits->isEmpty())
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-user-friends fa-2x mb-2"></i>
                                <p class="mb-0">Belum ada izin dari kepala bidang</p>
                            </div>
                        @else
                            @foreach ($groupedHeadPermits as $date => $permits)
                                <div class="mb-3">
                                    <h6 class="text-muted fs-12 px-1">
                                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
                                    </h6>

                                    @foreach ($permits as $permit)
                                        @include('backend.employee.permit.list-item', [
                                            'permit' => $permit,
                                        ])
                                    @endforeach
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                @if ($isHeadOfDepartment || !$groupedOwnPermits->isEmpty())
                    @foreach ($groupedOwnPermits->flatten() as $permit)
                        @include('backend.employee.permit.modal', ['permit' => $permit])
                    @endforeach
                    @foreach ($groupedSubordinatePermits->flatten() as $permit)
                        @include('backend.employee.permit.modal', ['permit' => $permit])
                    @endforeach
                    @foreach ($groupedHeadPermits->flatten() as $permit)
                        @include('backend.employee.permit.modal', ['permit' => $permit])
                    @endforeach

                @endif
            </div>
        </div>
    </div>
    @push('scripts')
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.querySelector('input[name="search"]');
                if (!searchInput) return;

                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.trim().toLowerCase();
                    const items = document.querySelectorAll('.submission-item');

                    items.forEach(item => {
                        const id = item.getAttribute('data-id');
                        if (searchTerm === '' || id.includes(searchTerm)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        </script>



        <script>
            function approveSubmission(id, level) {

                let actualItems = [];

                if (level === 'status') {

                    let isValid = true;

                    document.querySelectorAll('.price-input').forEach(input => {
                        const price = Number(input.value.replace(/\D/g, ''));
                        const qtyInput = document.querySelector(`.qty-input[data-item-id="${input.dataset.itemId}"]`);
                        const qty = Number(qtyInput?.value || 0);

                        if (price <= 0 || qty <= 0) {
                            isValid = false;
                        }

                        actualItems.push({
                            items_id: input.dataset.itemId,
                            price: price,
                            quantity: qty
                        });
                    });

                    if (!isValid) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data Belum Lengkap',
                            text: 'Harga dan jumlah aktual wajib diisi semua.'
                        });
                        return;
                    }
                }

                Swal.fire({
                    title: 'Konfirmasi Persetujuan',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: `{{ route('employee.submission.approve', ':id') }}`.replace(':id', id),
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            level: level,
                            actual_items: actualItems
                        },
                        success: function() {
                            Swal.fire('Berhasil!', 'Data berhasil disimpan.', 'success')
                                .then(() => window.location.reload());
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                        }
                    });
                });
            }
        </script>

        <script>
            function rejectSubmission(id, level) {
                window.currentRejectId = id;
                window.currentRejectLevel = level;

                const modal = new bootstrap.Modal(document.getElementById('rejectReasonModal'));
                modal.show();

                document.getElementById('rejectReasonInput').value = '';
                document.getElementById('confirmRejectBtn').onclick = function() {
                    const reason = document.getElementById('rejectReasonInput').value.trim();
                    if (!reason) {
                        document.getElementById('rejectReasonInput').classList.add('is-invalid');
                        return;
                    }
                    document.getElementById('rejectReasonInput').classList.remove('is-invalid');

                    $.ajax({
                        url: "{{ route('employee.submission.reject', ['id' => ':id']) }}".replace(':id', id),
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            level: level,
                            reject_reason: reason
                        },
                        success: function(response) {
                            Swal.fire('Ditolak!', response.message, 'success').then(() => {
                                window.location.reload();
                            });
                            modal.hide();
                        },
                        error: function(xhr) {
                            let message = 'Terjadi kesalahan saat menolak.';
                            if (xhr.responseJSON?.message) {
                                message = xhr.responseJSON.message;
                            }
                            Swal.fire('Gagal!', message, 'error');
                        }
                    });
                };
            }
        </script>

        <script>
            function deleteSubmission(id) {
                let url_destroy = `{{ route('employee.permit.destroy', ':id') }}`.replace(':id', id);

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
                                Swal.fire('Berhasil!', 'Data telah dihapus.', 'success').then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                let message = 'Terjadi kesalahan saat menghapus.';
                                if (xhr.responseJSON?.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Gagal!', message, 'error');
                            }
                        });
                    }
                });
            }
        </script>
    @endpush
@endsection
