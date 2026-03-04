<!-- Modal Detail Inventaris -->
<div class="modal fade" id="inventoryDetailModal" tabindex="-1" aria-labelledby="inventoryDetailModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="inventoryDetailModalLabel">
                    <i class="ti ti-package me-2"></i>Detail Data Inventaris
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if (isset($item))
                    <div class="row g-4">
                        <!-- Kolom Kiri -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">ID Aset</h6>
                                <p class="mb-0 fw-medium">{{ $item->asset_id ?? '-' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Kode Inventaris</h6>
                                <p class="mb-0 fw-medium">{{ $item->inventory_code ?? '-' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Nama Aset</h6>
                                <p class="mb-0 fw-medium">{{ $item->name ?? '-' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class="mb-0">Kategori</h6>
                                <p class="mb-0 fw-medium">
                                    {{ $category ? $category->code . ' - ' . $category->name : '-' }}
                                </p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Merk / Tipe</h6>
                                <p class="mb-0 fw-medium">{{ $item->brand ?? '-' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Nomor Seri</h6>
                                <p class="mb-0 fw-medium">{{ $item->serial_number ?? '-' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Spesifikasi</h6>
                                <p class="mb-0 fw-medium text-end">{{ $item->specification ?? '-' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Lokasi</h6>
                                <p class="mb-0 fw-medium">
                                    {{ $locationName ?? '-' }}
                                </p>
                            </div>

                            <div class="d-flex justify-content-between py-2">
                                <h6 class=" mb-0">Unit / Bagian</h6>
                                <p class="mb-0 fw-medium">
                                    {{ $item->unit ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <!-- Kolom Kanan -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Penanggung Jawab</h6>
                                <p class="mb-0 fw-medium">
                                    @php
                                        $responsible = \App\Models\Employee::find($item->responsible_person);
                                    @endphp
                                    {{ $responsible?->name ?? ($item->responsible_person ?? '-') }}
                                </p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Tanggal Perolehan</h6>
                                <p class="mb-0 fw-medium">
                                    {{ $item->acquisition_date ? \Carbon\Carbon::parse($item->acquisition_date)->translatedFormat('d F Y') : '-' }}
                                </p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Sumber Dana</h6>
                                <p class="mb-0 fw-medium">
                                    {{ $item->source_funding == 'bos' ? 'BOS' : ($item->source_funding == 'yayasan' ? 'Yayasan' : '-') }}
                                </p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-1">Harga Perolehan</h6>
                                <p class="mb-0 fw-medium">Rp
                                    {{ number_format($item->acquisition_price ?? 0, 0, ',', '.') }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-1">Jumlah</h6>
                                <p class="mb-0 fw-medium">{{ $item->quantity ?? '1' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Nilai Perolehan Total</h6>
                                <p class="mb-0 fw-medium">Rp.
                                    {{ number_format($item->total_acquisition_value ?? 0, 0, ',', '.') }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Nilai Residu</h6>
                                <p class="mb-0 fw-medium">
                                    {{ $item->residual_value ?? '0' }}</p>
                            </div>

                            <div class="row g-3 py-2 border-bottom">
                                <div class="col-6">
                                    <h6 class=" mb-1">Masa Manfaat</h6>
                                    <p class="mb-0 fw-medium">{{ $item->useful_life_years ?? '-' }} Tahun</p>
                                </div>
                                <div class="col-6">
                                    <h6 class=" mb-1">Metode Penyusutan</h6>
                                    <p class="mb-0 fw-medium">{{ $item->depreciation_method ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Bulan Terpakai</h6>
                                <p class="mb-0 fw-medium">{{ $item->used_until_date ?? '-' }}</p>
                            </div>

                            <div class="row g-3 py-2 border-bottom">
                                <div class="col-6">
                                    <h6 class=" mb-1">Penyusutan/Tahun</h6>
                                    <p class="mb-0 fw-medium">
                                        {{ $item->depreciation_amount_per_year ?? '-' }}</p>
                                </div>
                                <div class="col-6">
                                    <h6 class=" mb-1">Penyusutan/Bulan</h6>
                                    <p class="mb-0 fw-medium">
                                        {{ $item->depreciation_amount_per_month ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <h6 class=" mb-0">Akumulasi Penyusutan</h6>
                                <p class="mb-0 fw-medium">{{ $item->accumulated_depreciation ?? '-' }}</p>
                            </div>

                            <div class="d-flex justify-content-between py-2">
                                <h6 class=" mb-0">Nilai Buku</h6>
                                <p class="mb-0 fw-medium">{{ $item->book_value ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Section -->
                    <div class="row mt-4 pt-3 border-top">
                        <div class="col-md-6">
                            <h6 class="mb-2">Kondisi</h6>
                            <span
                                class="badge bg-{{ $item->condition == 'Baik' ? 'success' : ($item->condition == 'Rusak' ? 'danger' : 'warning') }} px-3 py-2">
                                {{ $item->condition ?? '-' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <h6 class=" mb-2">Status</h6>
                            <span class="badge bg-{{ $item->status == 'Aktif' ? 'primary' : 'secondary' }} px-3 py-2">
                                {{ $item->status ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <!-- Dokumen -->
                    @if (!empty($documents))
                        <div class="mt-4 pt-3 border-top">
                            <h6 class=" mb-3"><i class="ti ti-file me-2"></i>Dokumen</h6>
                            <div class="row g-3">
                                @foreach ($documents as $doc)
                                    <div class="col-md-3">
                                        @php
                                            $fileInfo = pathinfo($doc);
                                            $extension = strtolower($fileInfo['extension'] ?? '');
                                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
                                        @endphp

                                        @if ($isImage)
                                            <a href="{{ asset('storage/' . $doc) }}" target="_blank"
                                                class="text-decoration-none d-block">
                                                <div class="border rounded overflow-hidden">
                                                    <img src="{{ asset('storage/' . $doc) }}" class="w-100"
                                                        style="height: 120px; object-fit: cover;" alt="Dokumen">
                                                </div>
                                                <small
                                                    class=" d-block mt-1 text-truncate">{{ basename($doc) }}</small>
                                            </a>
                                        @else
                                            <a href="{{ asset('storage/' . $doc) }}" target="_blank"
                                                class="text-decoration-none d-block">
                                                <div class="bg-light border rounded p-3 text-center">
                                                    <i class="fas fa-file-alt fa-2x text-secondary mb-2"></i>
                                                    <small class=" d-block text-truncate">{{ basename($doc) }}</small>
                                                </div>
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Keterangan -->
                    @if ($item->description)
                        <div class="mt-4 pt-3 border-top">
                            <h6 class=" mb-2"><i class="ti ti-message me-2"></i>Keterangan</h6>
                            <p class="mb-0">{{ $item->description }}</p>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="bg-light rounded-circle p-3 d-inline-block mb-3">
                            <i class="ti ti-package fs-1 "></i>
                        </div>
                        <p class=" mb-0">Data inventaris tidak ditemukan</p>
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>
