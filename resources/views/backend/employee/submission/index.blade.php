@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/submission" />
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
                    <h5 class="mb-0">{{ __('label.list_submission') }}</h5>
                    @if ($canCreate)
                        <a href="{{ route('employee.submission.create') }}" class="btn btn-primary label-btn">
                            {{ __('label.add') }}
                            <i class="fe fe-plus label-btn-icon me-2"></i>
                        </a>
                    @endif
                </div>
                <div class="pt-3">
                    <form method="GET" action="{{ route('employee.submission.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari nomor pengajuan..."
                                value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>



            <div class="p-lg-3 p-2">
                @if ($groupedSubmissions->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <p class="mb-0">Belum ada pengajuan.</p>
                    </div>
                @else
                    @foreach ($groupedSubmissions as $date => $submissions)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between px-1">
                                <h6 class="mb-2 mt-1">
                                    <span class="text-muted fs-12">
                                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
                                    </span>
                                </h6>

                                <h6 class="mb-2 mt-1">
                                    <span class="text-muted fs-12">
                                        Rp {{ number_format($submissionAmounts[$date] ?? 0, 0, ',', '.') }}
                                    </span>
                                </h6>
                            </div>
                            @foreach ($submissions as $submission)
                                @php
                                    $isApprovedByWadir =
                                        $isWakil &&
                                        $submission->approve1 === 'pending' &&
                                        $submission->approve2 === 'pending';

                                    $isApprovedByMudir =
                                        $isMudir &&
                                        $submission->approve1 === 'approved' &&
                                        $submission->approve2 === 'pending';

                                    $isLastApproved =
                                        $isBendahara &&
                                        $submission->approve1 === 'approved' &&
                                        $submission->approve2 === 'approved' &&
                                        $submission->last_approve === 'approved' &&
                                        $submission->status === 'pending';
                                @endphp
                                <button type="button" data-bs-toggle="modal"
                                    data-bs-target="#submission-{{ $submission->id }}"
                                    class="d-flex align-items-center py-3 list-group-item w-100 rounded-2 {{ $isApprovedByWadir || $isApprovedByMudir || $isLastApproved ? 'border-3 border-success-subtle my-1' : '' }}">
                                    <div class="me-3">
                                        <div class="bg-success rounded-circle p-2 d-flex align-items-center justify-content-center"
                                            style="width: 30px; height: 30px;">
                                            <i class="fas fa-box text-white"></i>
                                        </div>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="text-start">
                                                <h6>{{ strtoupper(substr($submission->employee->name ?? '', 0, 3)) }}-{{ $submission->id }}
                                                </h6>
                                                <div class="text-success fw-bold mt-1">
                                                    {{ strtoupper($submission->employee?->task_main ?? 'UMUM') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ms-3">
                                        @if ($submission->employee_id === Auth::user()->employee->id)
                                            <div class="d-flex flex-column gap-2 align-items-end">
                                                <span class="badge {{ badgeClass($submission->status) }}"
                                                    style="width: fit-content;">
                                                    {{ strtoupper($submission->status) }}
                                                </span>
                                                @if ($submission->status === 'approved' && $submission->activity_type === 'item')
                                                    <span
                                                        class="badge {{ badgeClass($submission->status) }} text-uppercase">
                                                        Barang sudah siap
                                                    </span>
                                                @endif
                                            </div>
                                        @elseif ($isWakil)
                                            <span class="badge {{ badgeClass($submission->approve1) }}">
                                                {{ strtoupper($submission->approve1) }}
                                            </span>
                                        @elseif ($isMudir)
                                            <span class="badge {{ badgeClass($submission->approve2) }}">
                                                {{ strtoupper($submission->approve2) }}
                                            </span>
                                        @elseif ($isBendahara)
                                            <span class="badge {{ badgeClass($submission->last_approve) }}">
                                                {{ strtoupper($submission->last_approve) }}
                                            </span>
                                        @elseif ($isLogistik)
                                            <span class="badge {{ badgeClass($submission->status) }}">
                                                {{ strtoupper($submission->status) }}
                                            </span>
                                        @endif
                                    </div>

                                </button>

                                {{-- Modal Detail --}}
                                @include('backend.employee.submission.modal')

                                <!-- Modal Input Alasan Penolakan -->
                                <div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title">Tolak Pengajuan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="rejectReasonInput" class="form-label">Alasan
                                                        Penolakan</label>
                                                    <textarea id="rejectReasonInput" class="form-control" rows="4" placeholder="Tulis alasan penolakan..."
                                                        maxlength="1000" required></textarea>
                                                    <div class="invalid-feedback">Alasan wajib diisi.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Batal</button>
                                                <button type="button" id="confirmRejectBtn" class="btn btn-danger">
                                                    <i class="fas fa-times me-1"></i> Ya, Tolak
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
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
                const approveBtn = event?.target?.closest('button') || document.querySelector(
                    `button[onclick*="approveSubmission('${id}', '${level}')"]`);

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
                    confirmButtonText: 'Ya, Setujui',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        if (approveBtn) {
                            approveBtn.disabled = true;
                            approveBtn.dataset.originalHtml = approveBtn.innerHTML;
                            approveBtn.innerHTML =
                                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
                        }
                    }
                }).then((result) => {
                    if (!result.isConfirmed) {
                        if (approveBtn) {
                            approveBtn.disabled = false;
                            approveBtn.innerHTML = approveBtn.dataset.originalHtml || 'Setujui';
                        }
                        return;
                    }

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
                        },
                        complete: function() {
                            if (approveBtn) {
                                approveBtn.disabled = false;
                                approveBtn.innerHTML = approveBtn.dataset.originalHtml || 'Setujui';
                            }
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

                document.getElementById('rejectReasonInput').value = '';
                document.getElementById('rejectReasonInput').classList.remove('is-invalid');

                const confirmBtn = document.getElementById('confirmRejectBtn');
                confirmBtn.disabled = false;
                confirmBtn.dataset.originalText = confirmBtn.innerHTML;

                modal.show();

                confirmBtn.onclick = function() {
                    const reason = document.getElementById('rejectReasonInput').value.trim();
                    if (!reason) {
                        document.getElementById('rejectReasonInput').classList.add('is-invalid');
                        return;
                    }
                    document.getElementById('rejectReasonInput').classList.remove('is-invalid');

                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';

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
                        },
                        complete: function() {
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = confirmBtn.dataset.originalText || 'Konfirmasi Tolak';
                        }
                    });
                };

                document.getElementById('rejectReasonModal').addEventListener('hidden.bs.modal', function() {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = confirmBtn.dataset.originalText || 'Konfirmasi Tolak';
                });
            }
        </script>

        <script>
            function deleteSubmission(id) {
                let url_destroy = `{{ route('employee.submission.destroy', ':id') }}`.replace(':id', id);

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

        <script>
            document.addEventListener('input', function(e) {

                if (
                    !e.target.classList.contains('price-input') &&
                    !e.target.classList.contains('qty-input')
                ) {
                    return;
                }

                const index = e.target.dataset.index;
                if (index === undefined) return;

                const priceInput = document.querySelector(`.price-input[data-index="${index}"]`);
                const qtyInput = document.querySelector(`.qty-input[data-index="${index}"]`);
                const subtotalEl = document.querySelector(`.subtotal[data-index="${index}"]`);

                if (!priceInput || !qtyInput || !subtotalEl) return;

                if (e.target.classList.contains('price-input')) {
                    const raw = priceInput.value.replace(/\D/g, '');
                    priceInput.value = formatRupiah(raw);
                }

                const price = getNumber(priceInput.value);
                const qty = Number(qtyInput.value) || 0;

                const subtotal = (price > 0 && qty > 0) ? price * qty : 0;
                subtotalEl.textContent = subtotal.toLocaleString('id-ID');

                validateInput(priceInput, price);
                validateInput(qtyInput, qty);

                calculateTotal();
            });

            function formatRupiah(value) {
                if (!value) return '';
                return 'Rp ' + value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function getNumber(value) {
                return Number(value.replace(/\D/g, '')) || 0;
            }

            function validateInput(input, value) {
                if (!value || value <= 0) {
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            }

            function calculateTotal() {
                let total = 0;

                document.querySelectorAll('.subtotal').forEach(el => {
                    total += Number(el.textContent.replace(/\./g, '')) || 0;
                });

                const totalEl = document.getElementById('total');
                if (totalEl) {
                    totalEl.textContent = total.toLocaleString('id-ID');
                }
            }
        </script>
    @endpush
@endsection
