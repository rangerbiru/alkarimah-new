@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/teaching-schedule" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h5>Kelas: {{ $detail->classHour->class->name }}</h5>
            <x-section-form :label="__('label.student_list')" icon="bx bxs-user-circle" />

            <form action="{{ route('employee.teaching-schedule.store') }}" method="POST">
                @csrf
                <!-- Kirim ID dari class_hour_detail -->
                <input type="hidden" name="id_class_hour_details" value="{{ $detail->id }}">
                <div class="mb-3">
                    <input type="text" id="studentSearch" class="form-control"
                        placeholder="Cari siswa berdasarkan nama atau NIS...">
                </div>
                @forelse ($students as $std)
                    <div class="card card-tab border-black card-student mb-3" data-id="{{ $std->id }}"
                        data-name="{{ strtolower($std->name) }}" data-nis="{{ $std->nis }}">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-shrink-0">
                                    <i class='bx bx-user' style="font-size: 40px"></i>
                                </div>
                                <div class="mt-1">
                                    <h6 class="mb-1">{{ $std->name }}</h6>
                                    <small class="text-muted">NIS {{ $std->nis }} / Kelas:
                                        {{ $std->class->name ?? '-' }}</small>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm mt-1" role="group">
                                @php
                                    $currentStatus = $existingAttendance[$std->id] ?? 'hadir';
                                @endphp

                                <input type="radio" name="status[{{ $std->id }}]" id="hadir_{{ $std->id }}"
                                    value="hadir" class="btn-check" {{ $currentStatus === 'hadir' ? 'checked' : '' }}>
                                <label for="hadir_{{ $std->id }}" class="btn btn-outline-success btn-sm">HADIR</label>

                                <input type="radio" name="status[{{ $std->id }}]" id="izin_{{ $std->id }}"
                                    value="izin" class="btn-check" {{ $currentStatus === 'izin' ? 'checked' : '' }}>
                                <label for="izin_{{ $std->id }}" class="btn btn-outline-info btn-sm">IZIN</label>

                                <input type="radio" name="status[{{ $std->id }}]" id="sakit_{{ $std->id }}"
                                    value="sakit" class="btn-check" {{ $currentStatus === 'sakit' ? 'checked' : '' }}>
                                <label for="sakit_{{ $std->id }}" class="btn btn-outline-warning btn-sm">SAKIT</label>

                                <input type="radio" name="status[{{ $std->id }}]" id="alfa_{{ $std->id }}"
                                    value="alfa" class="btn-check" {{ $currentStatus === 'alfa' ? 'checked' : '' }}>
                                <label for="alfa_{{ $std->id }}" class="btn btn-outline-danger btn-sm">ALFA</label>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info text-center">
                        Tidak ada siswa di kelas ini.
                    </div>
                @endforelse

                <div class="text-center mt-4 d-flex gap-2 align-items-center justify-content-center flex-column">
                    @if (!$existingAttendance)
                        <button type="submit" class="btn btn-primary w-100">Simpan Absensi</button>
                    @else
                        <button type="submit" class="btn btn-info w-100">Update Absensi</button>
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal"
                            data-bs-target="#exampleModal">
                            Jurnal Pembelajaran
                        </button>
                    @endif
                    <a href="{{ route('employee.teaching-schedule.index') }}" class="btn btn-secondary w-100">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="exampleModalLabel">Jurnal Kelas -
                        {{ $detail->classHour->class->name }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Info Dasar (tidak berubah) -->
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nama Guru</strong></p>
                            <p class="text-muted">{{ Auth::user()->employee->name ?? '–' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Mata Pelajaran</strong></p>
                            <p class="text-muted">{{ $detail->subject?->name ?? '–' }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Tanggal Pertemuan</strong></p>
                            <p class="text-muted">{{ $formattedDate }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Jumlah Santri</strong></p>
                            <p class="text-muted">{{ $students->count() }} Siswa</p>
                        </div>
                    </div>

                    <!-- Statistik -->
                    <div class="border-top pt-3 mb-3">
                        @php
                            $hadir = $sakit = $izin = $alfa = 0;
                            foreach ($existingAttendance as $status) {
                                match ($status) {
                                    'hadir' => $hadir++,
                                    'sakit' => $sakit++,
                                    'izin' => $izin++,
                                    'alfa' => $alfa++,
                                };
                            }
                        @endphp

                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Siswa Hadir</strong></p>
                                <p class="text-muted">{{ $hadir }} Siswa</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Siswa Sakit</strong></p>
                                <p class="text-muted">{{ $sakit }} Siswa</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Siswa Izin</strong></p>
                                <p class="text-muted">{{ $izin }} Siswa</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Tanpa Keterangan</strong></p>
                                <p class="text-muted">{{ $alfa }} Siswa</p>
                            </div>
                        </div>
                    </div>

                    @if ($journalBefore)
                        <div class="border-top pt-3 mb-3">
                            <h6>Jurnal Sebelumnya</h6>
                            <div class="mb-3">
                                <label for="chapter" class="form-label">BAB Sebelumnya</label>
                                <input type="text" class="form-control" id="chapter" name="chapter"
                                    value="{{ optional($journalBefore ?? null)->chapter ?? old('chapter') }}" readonly
                                    disabled>
                            </div>

                            <div class="mb-3">
                                <label for="subject_matter" class="form-label">Keterangan</label>
                                <textarea readonly disabled class="form-control" id="subject_matter" name="subject_matter" rows="2">{{ optional($journalBefore ?? null)->subject_matter ?? old('subject_matter') }}</textarea>
                            </div>
                        </div>
                    @endif

                    <form id="journalForm" class="border-top pt-3 mb-3">
                        @csrf
                        <input type="hidden" name="id_class_hour_details" value="{{ $detail->id }}">
                        <input type="hidden" name="date" value="{{ now()->toDateString() }}">

                        <h6>Jurnal Hari Ini</h6>
                        <div class="mb-3">
                            <label for="chapter" class="form-label">BAB</label>
                            <input type="text" class="form-control" id="chapter" name="chapter"
                                placeholder="Masukkan judul BAB"
                                value="{{ optional($journalNow ?? null)->chapter ?? old('chapter') }}">
                        </div>

                        <div class="mb-3">
                            <label for="subject_matter" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="subject_matter" name="subject_matter" rows="4"
                                placeholder="Catatan pembelajaran, materi, atau aktivitas kelas">{{ optional($journalNow ?? null)->subject_matter ?? old('subject_matter') }}</textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">

    <style>
        .bg-success-subtle {
            background-color: #d1e7dd !important;
            color: #0f5132 !important;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const journalForm = document.getElementById('journalForm');
                if (!journalForm) return;

                journalForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';

                    try {
                        const response = await fetch(
                            "{{ route('employee.teaching-schedule.journal.store') }}", {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .getAttribute('content')
                                }
                            });

                        const result = await response.json();

                        if (result.success) {
                            // Tampilkan SweetAlert
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: result.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Redirect ke halaman index
                                window.location.href = result.redirect_url;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: result.message || 'Terjadi kesalahan saat menyimpan.',
                            });
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan jaringan. Coba lagi.',
                        });
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('studentSearch');
                const studentCards = document.querySelectorAll('.card-student');

                if (!searchInput) return;

                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();

                    studentCards.forEach(card => {
                        const name = card.getAttribute('data-name') || '';
                        const nis = card.getAttribute('data-nis') || '';

                        const match = name.includes(query) || nis.includes(query);
                        card.style.display = match ? '' : 'none';
                    });
                });
            });
        </script>
    @endpush
@endpush
