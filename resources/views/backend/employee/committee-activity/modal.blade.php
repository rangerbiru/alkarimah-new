{{-- Modal Detail Kegiatan --}}
<div class="modal fade" id="detail-committee-{{ $activity->id }}" tabindex="-1"
    aria-labelledby="detailCommitteeLabel{{ $activity->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailCommitteeLabel{{ $activity->id }}">
                    Detail Kegiatan: {{ $activity->activity_name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <h6>Tanggal Kegiatan</h6>
                            <div class="text-capitalize">
                                {{ $activity->activity_date ? $activity->activity_date->translatedFormat('l, d F Y') : '-' }}
                            </div>
                        </div>
                        <div class="mb-2">
                            <h6>Jenis Kegiatan</h6>
                            <div class="text-capitalize">{{ $activity->activity_type }}</div>
                        </div>
                        <div class="mb-2">
                            <h6>Bidang Terkait</h6>
                            <div class="text-capitalize">{{ $activity->related_field ?? '-' }}</div>
                        </div>
                        <div class="mb-2">
                            <h6>Lokasi</h6>
                            <div class="text-capitalize">{{ $activity->location ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <h6>Penanggung Jawab</h6>
                            <div class="text-capitalize">{{ $activity->responsible_person }}</div>
                        </div>
                        <div class="mb-2">
                            <h6>Jumlah Peserta</h6>
                            <div class="text-capitalize">{{ $activity->participant_count }}</div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Ringkasan Kegiatan -->
                <div class="mb-4">
                    <h6><i class="fas fa-align-left me-1"></i> Ringkasan Kegiatan</h6>
                    <p class="mb-0 list-group-item rounded-2">{{ $activity->activity_summary ?? '-' }}</p>
                </div>

                <div class="mb-4">
                    <h6><i class="fas fa-users me-1"></i>
                        Daftar Peserta ({{ $activity->employees->count() }})
                    </h6>

                    @if ($activity->employees->isNotEmpty())
                        <ul class="list-group">
                            @foreach ($activity->employees->take(4) as $emp)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $emp->name }}
                                    <span class="badge bg-secondary">{{ $emp->task_main ?? '-' }}</span>
                                </li>
                            @endforeach

                            @if ($activity->employees->count() > 4)
                                @php
                                    $remaining = $activity->employees->skip(4);
                                @endphp
                                <li class="list-group-item p-0 border-0">
                                    <a class="btn btn-sm btn-link text-decoration-none d-block"
                                        data-bs-toggle="collapse" href="#more-employees-{{ $activity->id }}"
                                        role="button">
                                        +{{ $remaining->count() }} pegawai lainnya
                                    </a>
                                    <ul class="collapse list-group" id="more-employees-{{ $activity->id }}">
                                        @foreach ($remaining as $emp)
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                {{ $emp->name }}
                                                <span class="badge bg-secondary">{{ $emp->task_main ?? '-' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        </ul>
                    @else
                        <p class="text-muted">Tidak ada peserta terdaftar.</p>
                    @endif
                </div>

                <!-- Dokumentasi -->
                <div>
                    <h6><i class="fas fa-paperclip me-1"></i> Dokumentasi</h6>

                    <!-- Foto -->
                    @if ($activity->photos->isNotEmpty())
                        <div class="mt-2">
                            <strong>Foto Kegiatan ({{ $activity->photos->count() }}):</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach ($activity->photos as $photo)
                                    <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank"
                                        class="d-block" title="{{ $photo->file_name }}">
                                        <img src="{{ asset('storage/' . $photo->file_path) }}" class="img-thumbnail"
                                            style="width: 100px; height: 100px; object-fit: cover;" alt="Foto">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- SK -->
                    @if ($activity->skDocuments->isNotEmpty())
                        <div class="mt-3">
                            <strong>Surat Keputusan ({{ $activity->skDocuments->count() }}):</strong>
                            <ul class="list-group mt-1">
                                @foreach ($activity->skDocuments as $sk)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="{{ asset('storage/' . $sk->file_path) }}" target="_blank"
                                            class="text-primary">
                                            <i class="fas fa-file-pdf me-1"></i> {{ Str::limit($sk->file_name, 30) }}
                                        </a>
                                        <span class="text-muted small">
                                            @if ($sk->file_path && \Storage::disk('public')->exists($sk->file_path))
                                                {{ number_format(\Storage::disk('public')->size($sk->file_path) / 1024, 1) }}
                                                KB
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Berita Acara -->
                    @if ($activity->beritaAcara->isNotEmpty())
                        <div class="mt-3">
                            <strong>Berita Acara ({{ $activity->beritaAcara->count() }}):</strong>
                            <ul class="list-group mt-1">
                                @foreach ($activity->beritaAcara as $minutes)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="{{ asset('storage/' . $minutes->file_path) }}" target="_blank"
                                            class="text-success">
                                            <i class="fas fa-file-alt me-1"></i>
                                            {{ Str::limit($minutes->file_name, 30) }}
                                        </a>
                                        <span class="text-muted small">
                                            @if ($minutes->file_path && \Storage::disk('public')->exists($minutes->file_path))
                                                {{ number_format(\Storage::disk('public')->size($minutes->file_path) / 1024, 1) }}
                                                KB
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($activity->photos->isEmpty() && $activity->skDocuments->isEmpty() && $activity->beritaAcara->isEmpty())
                        <p class="text-muted mt-2">Tidak ada dokumen yang diunggah.</p>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
