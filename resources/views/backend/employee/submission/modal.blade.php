<!-- Modal -->
<div class="modal fade" id="submission-{{ $submission->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title">
                        Detail Pengajuan
                        {{ strtoupper(substr($submission->employee->name ?? '', 0, 3)) }} -
                        {{ $submission->id }}
                    </h5>
                    <small class="text-muted">
                        {{ \Carbon\Carbon::parse($submission->created_at)->translatedFormat('d F Y, H:i') }}
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Nama Aktivitas</h6>
                        <div class="text-capitalize">
                            <p>{{ $submission->activity_name }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Pegawai</h6>
                        <div class="text-capitalize">
                            <p>{{ $submission->employee->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Lokasi</h6>
                        <div class="text-capitalize">
                            @if ($submission->location->isNotEmpty())
                                <p class="card-text">
                                    {{ $submission->location->map(fn($loc) => $loc->unit?->unit)->filter()->join(', ') ?: 'Nama unit tidak tersedia' }}
                                </p>
                            @else
                                <p class="text-muted">Belum ada lokasi ditambahkan.</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 mt-lg-0 mt-3">
                        <h6>Jenis</h6>
                        <div class="text-capitalize">
                            <p>{{ match ($submission->activity_type) {
                                'item' => 'Pengadaan Barang',
                                'service' => 'Jasa',
                                'fund' => 'Dana',
                                default => '–',
                            } }}
                            </p>
                        </div>
                    </div>
                </div>

                @if ($isWakil && $submission->approve1)
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Wakil Mudir</h6>
                            <span class="badge {{ badgeClass($submission->approve1) }}">
                                {{ strtoupper($submission->approve1) }}
                            </span>
                        </div>
                    </div>
                @elseif ($isMudir && $submission->approve1)
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Wakil Mudir</h6>
                            <span class="badge {{ badgeClass($submission->approve1) }}">
                                {{ strtoupper($submission->approve1) }}
                            </span>
                        </div>
                        @if ($submission->approve2)
                            <div class="col-md-6 mt-lg-0 mt-3">
                                <h6>Mudir</h6>
                                <span class="badge {{ badgeClass($submission->approve2) }}">
                                    {{ strtoupper($submission->approve2) }}
                                </span>
                            </div>
                        @endif
                    </div>
                @elseif($isBendahara && $submission->approve1 && $submission->approve2)
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Wakil Mudir</h6>
                            <span class="badge {{ badgeClass($submission->approve1) }}">
                                {{ strtoupper($submission->approve1) }}
                            </span>
                        </div>
                        <div class="col-md-6 mt-lg-0 mt-3">
                            <h6>Mudir</h6>
                            <span class="badge {{ badgeClass($submission->approve2) }}">
                                {{ strtoupper($submission->approve2) }}
                            </span>
                        </div>
                    </div>

                    <div class="row mt-0 mt-lg-3">
                        <div class="col-md-6 mt-lg-0 mt-3">
                            <h6>Bendahara</h6>
                            <span class="badge {{ badgeClass($submission->last_approve) }}">
                                {{ strtoupper($submission->last_approve) }}
                            </span>
                        </div>
                    </div>
                @elseif ($isLogistik && $submission->status)
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Wakil Mudir</h6>
                            <span class="badge {{ badgeClass($submission->approve1) }}">
                                {{ strtoupper($submission->approve1) }}
                            </span>
                        </div>
                        <div class="col-md-6 mt-lg-0 mt-3">
                            <h6>Mudir</h6>
                            <span class="badge {{ badgeClass($submission->approve2) }}">
                                {{ strtoupper($submission->approve2) }}
                            </span>
                        </div>
                    </div>

                    <div class="row mt-0 mt-lg-3">
                        <div class="col-md-6 mt-lg-0 mt-3">
                            <h6>Bendahara</h6>
                            <span class="badge {{ badgeClass($submission->last_approve) }}">
                                {{ strtoupper($submission->last_approve) }}
                            </span>
                        </div>
                        <div class="col-md-6 mt-lg-0 mt-3">
                            <h6>Status</h6>
                            <span class="badge {{ badgeClass($submission->status) }}">
                                {{ strtoupper($submission->status) }}
                            </span>
                        </div>
                    </div>
                @endif


                {{-- Pegawai --}}
                @if ($submission->employee->id === Auth::user()->employee->id && $submission->status)
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Status</h6>
                            <span class="badge {{ badgeClass($submission->status) }}">
                                {{ strtoupper($submission->status) }}
                            </span>
                        </div>

                        @if ($submission->status === 'approved' && $submission->activity_type === 'item')
                            <div class="col-md-6 mt-lg-0 mt-3">
                                <h6>Informasi Logistik</h6>
                                <p class="badge bg-success fs-12 text-uppercase">Barang sudah siap</p>
                            </div>
                        @endif
                    </div>
                @endif


                @if ($submission->reject_reason)
                    <div class="alert alert-danger mt-3">
                        <h6><i class="fas fa-exclamation-triangle me-1"></i> Pengajuan
                            Ditolak</h6>
                        <p class="mb-0"><strong>Alasan:</strong></p>
                        <p>{{ $submission->reject_reason }}</p>
                        @if ($submission->rejectedByEmployee)
                            <p class="mb-0"><strong>Ditolak oleh:</strong></p>
                            <p>{{ $submission->rejectedByEmployee->name }}
                                @if ($submission->rejectedByEmployee->task_main)
                                    ({{ $submission->rejectedByEmployee->task_main }})
                                @endif
                            </p>
                        @endif
                    </div>
                @endif


                <hr>

                @if ($submission->description)
                    <div class="mb-3">
                        <h6>Keterangan</h6>
                        <p class="text-muted bg-light p-2 rounded">
                            {{ $submission->description }}</p>
                    </div>
                @endif

                <!-- Daftar Item -->
                @if ($submission->items->isNotEmpty())
                    <h6 class="mt-4 mb-2">Daftar Barang</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($submission->items as $item)
                                    @php
                                        $quantity =
                                            $submission->submissionItems->firstWhere('items_id', $item->id)
                                                ?->quantity ?? 0;
                                        $subtotal = ($item->price ?? 0) * $quantity;
                                    @endphp
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td class="text-center">Rp
                                            {{ number_format($item->price ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">{{ $quantity }}</td>
                                        <td class="text-center">Rp
                                            {{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-2 d-flex justify-content-between px-2 py-3 bg-success-subtle rounded-1">
                        @php
                            $submissionId = $submission->id;

                            $total = DB::table('submission_items as si')
                                ->join('items as i', 'si.items_id', '=', 'i.id')
                                ->where('si.submissions_id', $submissionId)
                                ->sum(DB::raw('i.price * si.quantity'));
                        @endphp
                        <h6 class="mb-0">Total</h6>
                        <h6 class="mb-0">Rp. {{ number_format($total, 0, ',', '.') }}
                        </h6>
                    </div>
                @else
                    <p class="text-muted">Tidak ada barang yang diajukan.</p>
                @endif

                {{-- Daftar Aktual Logistik --}}
                @if ($submission->actualSubmissionItems->isNotEmpty() && $isLogistik)
                    <hr>
                    <h6 class="mt-4 mb-2">Daftar Pembelian Barang</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($submission->actualSubmissionItems as $item)
                                    @php
                                        $subtotal = ($item->price ?? 0) * $item->quantity;
                                    @endphp
                                    <tr>
                                        <td>{{ $item->items->name }}</td>
                                        <td class="text-center">Rp
                                            {{ number_format($item->price ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-center">Rp
                                            {{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-2 d-flex justify-content-between px-2 py-3 bg-warning-subtle rounded-1">
                        @php
                            $total = $submission->actualSubmissionItems->sum(function ($item) {
                                return ($item->price ?? 0) * $item->quantity;
                            });
                        @endphp
                        <h6 class="mb-0">Total</h6>
                        <h6 class="mb-0">Rp. {{ number_format($total, 0, ',', '.') }}
                        </h6>
                    </div>
                @endif


                {{-- Input Logistik --}}
                @if ($submission->items->isNotEmpty() && $submission->status === 'process' && $isLogistik)
                    <hr>
                    <h6 class="mt-4 mb-2">Daftar Pembelian Barang</h6>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" style="min-width: 600px !important">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th class="text-center">Harga Aktual</th>
                                    <th class="text-center">Jumlah Aktual</th>
                                    <th class="text-center">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($submission->items as $index => $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>

                                        <td>
                                            <input type="text" class="form-control form-control-sm price-input"
                                                data-index="{{ $index }}" data-item-id="{{ $item->id }}"
                                                min="0" placeholder="Harga">
                                        </td>

                                        <td>
                                            <input type="number" class="form-control form-control-sm qty-input"
                                                data-index="{{ $index }}" data-item-id="{{ $item->id }}"
                                                min="0" placeholder="Jumlah">
                                        </td>

                                        <td class="text-center">
                                            Rp <span class="subtotal" data-index="{{ $index }}">0</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-2 d-flex justify-content-between px-2 py-3 bg-warning-subtle rounded-1">
                        <h6 class="mb-0">Total Aktual</h6>
                        <h6 class="mb-0">Rp <span id="total">0</span></h6>
                    </div>
                @endif

            </div>
            <div class="modal-footer">
                @if ($submission->employee->id === Auth::user()->employee->id && $submission->status === 'pending')
                    <button class="btn btn-outline-danger" onclick="deleteSubmission({{ $submission->id }})">
                        <i class="fas fa-trash me-1"></i> Hapus
                    </button>
                @elseif ($isWakil && $submission->approve1 === 'pending' && $submission->approve2 === 'pending')
                    <button class="btn btn-outline-success"
                        onclick="approveSubmission({{ $submission->id }}, 'approve1')">
                        <i class="fas fa-check me-1"></i> Terima
                    </button>
                    <button class="btn btn-outline-danger"
                        onclick="rejectSubmission({{ $submission->id }}, 'approve1')" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Tolak
                    </button>
                @elseif ($isMudir && $submission->approve1 == 'approved' && $submission->approve2 == 'pending')
                    <button class="btn btn-outline-success"
                        onclick="approveSubmission({{ $submission->id }}, 'approve2')">
                        <i class="fas fa-check me-1"></i> Terima
                    </button>
                    <button class="btn btn-outline-danger"
                        onclick="rejectSubmission({{ $submission->id }}, 'approve2')" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Tolak
                    </button>
                @elseif ($isMudir && $submission->approve1 == 'pending')
                    <button class="btn btn-primary" disabled><i class="bx bx-time me-1"></i> Menunggu
                        Persetujuan Wakil
                        Direktur
                    </button>
                @elseif (
                    $isBendahara &&
                        $submission->approve1 == 'approved' &&
                        $submission->approve2 == 'approved' &&
                        $submission->last_approve == 'pending')
                    <button class="btn btn-outline-success"
                        onclick="approveSubmission({{ $submission->id }}, 'last_approve')">
                        <i class="fas fa-check me-1"></i> Terima
                    </button>
                    <button class="btn btn-outline-danger"
                        onclick="rejectSubmission({{ $submission->id }}, 'last_approve')" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Tolak
                    </button>
                @elseif ($isBendahara && $submission->approve2 == 'pending')
                    <button class="btn btn-primary" disabled><i class="bx bx-time me-1"></i> Menunggu
                        Persetujuan Mudir
                    </button>
                @elseif (
                    $isLogistik &&
                        $submission->approve1 == 'approved' &&
                        $submission->approve2 == 'approved' &&
                        $submission->last_approve == 'approved' &&
                        $submission->status == 'pending')
                    <button class="btn btn-outline-success"
                        onclick="approveSubmission({{ $submission->id }}, 'status')">
                        <i class="fas fa-check me-1"></i> Terima
                    </button>
                @elseif ($submission->status == 'process')
                    <button class="btn btn-success" onclick="approveSubmission({{ $submission->id }}, 'status')">
                        <i class="fas fa-check me-1"></i> Telah Selesai Diproses
                    </button>
                @endif


                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
