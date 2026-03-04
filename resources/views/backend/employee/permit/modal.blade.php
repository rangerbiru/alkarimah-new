<!-- Modal -->
<div class="modal fade" id="permit-{{ $permit->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title">
                        Detail Perizinan
                    </h5>
                    <small class="text-muted">
                        {{ \Carbon\Carbon::parse($permit->created_at)->translatedFormat('d F Y, H:i') }}
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Nama Pegawai</h6>
                        <div class="text-capitalize">
                            <p>{{ $permit->name ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Jenis Perizinan</h6>
                        <div class="text-capitalize">
                            <p>{{ $permit->permitType->permit_type ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Tanggal Izin</h6>
                        <div class="text-capitalize">
                            <p>{{ \Carbon\Carbon::parse($permit->date)->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>

                    @if ($permit->permitType->level == 1)
                        <div class="col-md-6">
                            <h6>Waktu Mulai Izin</h6>
                            <div class="text-capitalize">
                                <p>{{ \Carbon\Carbon::parse($permit->permit_start_time)->format('H:i') }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6>Lama Waktu Izin</h6>
                            <div class="text-capitalize">
                                <p>{{ $permit->permit_hour_total ? $permit->permit_hour_total . ' Jam' : '-' }}</p>
                            </div>
                        </div>
                    @elseif ($permit->permitType->level == 2)
                        <div class="col-md-6">
                            <h6>Lama Hari Izin</h6>
                            <div class="text-capitalize">
                                <p>{{ $permit->permit_day_total ? $permit->permit_day_total . ' Hari' : '-' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Status</h6>
                        <span class="badge {{ badgeClass($permit->status) }}">
                            {{ strtoupper($permit->status) }}
                        </span>
                    </div>
                </div>


                @if ($permit->note && in_array($permit->status, ['rejected', 'approved']))
                    @php
                        $isRejected = $permit->status === 'rejected';
                        $alertClass = $isRejected ? 'alert-danger' : 'alert-success';
                        $icon = $isRejected ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
                        $title = $isRejected ? 'Perizinan Ditolak' : 'Perizinan Disetujui';
                        $reviewer = $permit->decision_by;
                    @endphp

                    <div class="alert {{ $alertClass }} mt-3">
                        <h6>
                            <i class="{{ $icon }} me-1"></i> {{ $title }}
                        </h6>
                        <p class="mb-0"><strong>Alasan:</strong></p>
                        <p>{{ $permit->note }}</p>

                        @if ($reviewer)
                            <p class="mb-0"><strong>{{ $isRejected ? 'Ditolak oleh:' : 'Disetujui oleh:' }}</strong>
                            </p>
                            <p>
                                {{ $permit->decisionBy->name }}
                                @if ($permit->decisionBy->task_main)
                                    ({{ $permit->decisionBy->task_main }})
                                @endif
                            </p>
                        @endif
                    </div>
                @endif

                <hr>

                @if ($permit->reason)
                    <div class="mb-3">
                        <h6>Detail Izin</h6>
                        <p class="text-muted bg-light p-2 rounded">
                            {{ $permit->reason ? $permit->reason : '-' }}</p>
                    </div>
                @endif

                @if ($permit->attachment)
                    <hr>
                    <div class="mb-3">
                        <h6>Dokumen Pendukung</h6>

                        @php
                            $extension = pathinfo($permit->attachment, PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                        @endphp

                        @if ($isImage)
                            <div style="text-align: center;">
                                <a href="{{ asset('storage/' . $permit->attachment) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $permit->attachment) }}" alt="Dokumen Pendukung"
                                        class="attachment-preview-img" title="Klik untuk membuka di tab baru"
                                        style="max-width: 100%; max-height: 100px;">
                                </a>
                            </div>
                        @else
                            <div
                                style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px dashed #ddd;">
                                <i class="bx bxs-file-blank" style="font-size: 64px; color: #555;"></i>
                                <br><br>
                                <p style="margin: 0; font-size: 14px; color: #666;">{{ Str::upper($extension) }} File
                                </p>
                                <a href="{{ asset('storage/' . $permit->attachment) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary mt-2" style="text-decoration: none;">
                                    <i class="bx bx-download me-1"></i> Unduh / Buka
                                </a>
                            </div>
                        @endif
                    </div>
                @endif


            </div>
            <div class="modal-footer">
                @if ($permit->employee->id === Auth::user()->employee->id && $permit->status === 'pending')
                    <button class="btn btn-outline-danger" onclick="deletePermit({{ $permit->id }})">
                        <i class="fas fa-trash me-1"></i> Hapus
                    </button>
                @endif

                @if ($isHeadOfDepartment && $permit->status === 'pending')
                    @php
                        $canApprove = false;
                        if ($permit->department_id) {
                            $managedDepartmentIds = $managedDepartments->pluck('id')->toArray();
                            $canApprove = in_array($permit->department_id, $managedDepartmentIds);
                        }
                    @endphp

                    @if (($canApprove && $permit->employee_id !== Auth::user()->employee->id) || $isPengurus)
                        <button class="btn btn-success"
                            onclick="approvePermit({{ $permit->id }}, 'permit-{{ $permit->id }}')">
                            <i class="fas fa-check me-1"></i> {{ __('label.approved') }}
                        </button>

                        <button class="btn btn-danger"
                            onclick="rejectPermit({{ $permit->id }}, 'permit-{{ $permit->id }}')">
                            <i class="fas fa-times me-1"></i> {{ __('label.rejected') }}
                        </button>
                    @endif
                @endif


                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Setujui Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="approveReasonInput" class="form-label">Alasan
                        Disetujui</label>
                    <textarea id="approveReasonInput" class="form-control" rows="4" placeholder="Tulis alasan penyetujuan..."
                        maxlength="1000" required></textarea>
                    <div class="invalid-feedback">Alasan wajib diisi.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="confirmApproveBtn" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Ya, Setujui
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Tolak Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="confirmRejectBtn" class="btn btn-danger">
                    <i class="fas fa-times me-1"></i> Ya, Tolak
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .attachment-preview {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px dashed #ddd;
    }

    .attachment-preview img {
        max-width: 100%;
        max-height: 300px;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: transform 0.2s;
    }

    .attachment-preview img:hover {
        transform: scale(1.02);
    }
</style>

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
                const items = document.querySelectorAll('.permit-item');

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
        function approvePermit(id, currentModalId = null) {
            window.currentRejectId = id;

            if (currentModalId) {
                const detailModal = bootstrap.Modal.getInstance(document.getElementById(currentModalId));
                if (detailModal) {
                    detailModal.hide();
                    document.getElementById(currentModalId).addEventListener('hidden.bs.modal',
                        function openApproveModal() {
                            openApproveReasonModal();
                            this.removeEventListener('hidden.bs.modal', openApproveModal);
                        });
                } else {
                    openApproveReasonModal();
                }
            } else {
                openApproveReasonModal();
            }

            function openApproveReasonModal() {
                const modal = new bootstrap.Modal(document.getElementById('approveReasonModal'));
                modal.show();

                document.getElementById('approveReasonInput').value = '';
                document.getElementById('confirmApproveBtn').onclick = function() {
                    const reason = document.getElementById('approveReasonInput').value.trim();
                    if (!reason) {
                        document.getElementById('approveReasonInput').classList.add('is-invalid');
                        return;
                    }
                    document.getElementById('approveReasonInput').classList.remove('is-invalid');

                    $.ajax({
                        url: "{{ route('employee.permit.approve', ['id' => ':id']) }}".replace(':id', id),
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            note: reason
                        },
                        success: function(response) {
                            Swal.fire('Disetujui!', "Pengajuan izin berhasil disetujui", 'success').then(
                                () => {
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
        }
    </script>

    <script>
        function rejectPermit(id, currentModalId = null) {
            window.currentRejectId = id;

            if (currentModalId) {
                const detailModal = bootstrap.Modal.getInstance(document.getElementById(currentModalId));
                if (detailModal) {
                    detailModal.hide();
                    document.getElementById(currentModalId).addEventListener('hidden.bs.modal', function openRejectModal() {
                        openRejectReasonModal();
                        this.removeEventListener('hidden.bs.modal', openRejectModal);
                    });
                } else {
                    openRejectReasonModal();
                }
            } else {
                openRejectReasonModal();
            }

            function openRejectReasonModal() {
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
                        url: "{{ route('employee.permit.reject', ['id' => ':id']) }}".replace(':id', id),
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            note: reason
                        },
                        success: function(response) {
                            Swal.fire('Ditolak!', "Pengajuan izin berhasil ditolak", 'success').then(
                                () => {
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
        }
    </script>

    <script>
        function deletePermit(id) {
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
