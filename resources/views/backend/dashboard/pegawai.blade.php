@extends('layouts.mobile.index')

@section('title', $title)
@section('content')
    @php
        $todayDate = \Carbon\Carbon::now()->format('Y-m-d');
    @endphp
    <div class="card mt-4">
        <div class="card-body">
            @if (!$employee)
                <div class="alert alert-danger" role="alert">
                    Maaf, Anda belum terdaftar pada absensi pegawai. Silahkan hubungi admin.
                </div>
            @elseif (!$attendanceLocation)
                <div class="alert alert-warning" role="alert">
                    Maaf, Anda sudah terdaftar pada absensi pegawai. Namun lokasi absensi belum terdaftar. Silahkan hubungi
                    admin.
                </div>
            @else
                {{-- Jika ada data kehadiran --}}
                <div class="card-attendance">
                    <h6>Tempat</h6>
                    <p>{{ $attendanceLocation->location_name }}</p>
                </div>

                <div class="card-attendance">
                    <h6>Hari</h6>
                    <p>{{ $today }}</p>
                </div>
                @php
                    function toTimeHM($value)
                    {
                        return $value ? \Carbon\Carbon::parse($value)->format('H.i') : '-';
                    }

                    $shiftTime = [
                        'pagi' => [
                            'label' => 'Shift 1',
                            'start' => toTimeHM($shifts?->shift1_check_in_time),
                            'end' => toTimeHM($shifts?->shift1_check_out_time),
                        ],
                        'sore' => [
                            'label' => 'Shift 2',
                            'start' => toTimeHM($shifts?->shift2_check_in_time),
                            'end' => toTimeHM($shifts?->shift2_check_out_time),
                        ],
                        'malam' => [
                            'label' => 'Shift 3',
                            'start' => toTimeHM($shifts?->shift3_check_in_time),
                            'end' => toTimeHM($shifts?->shift3_check_out_time),
                        ],
                    ];
                @endphp

                @if ($groupAttendance === 'Y')
                    @if ($attendance && $attendance->check_in_time && !$attendance->check_out_time)

                        <div class="card-attendance mb-3">
                            <h6>Pilih Shift</h6>

                            @php
                                $shiftLabel = $selectedShift ? $shiftTime[$selectedShift]['label'] : '-';
                                $start = $selectedShift ? $shiftTime[$selectedShift]['start'] : null;
                                $end = $selectedShift ? $shiftTime[$selectedShift]['end'] : null;
                            @endphp
                            <input type="text" disabled class="form-control w-50 form-control-sm"
                                value="{{ $shiftLabel }}" />


                        </div>
                        <div class="card-attendance mt-3">
                            <h6>Jam Kerja</h6>
                            <p id="shift-time">{{ $start . ' - ' . $end }}</p>
                        </div>
                    @else
                        <div class="card-attendance mb-3">
                            <h6>Pilih Shift</h6>
                            <select name="shift_selected" id="shift_selected" class="form-select w-50" required>
                                <option value="" disabled selected>-- Pilih Shift --</option>
                                @foreach ($shiftTime as $key => $shift)
                                    @php
                                        $hour = -1;
                                        if ($shift['start']) {
                                            $timeFormatted = str_replace('.', ':', $shift['start']);
                                            if (strlen($timeFormatted) === 2) {
                                                $timeFormatted .= ':00';
                                            }
                                            try {
                                                $hour = (int) \Carbon\Carbon::parse($timeFormatted)->format('H');
                                            } catch (\Exception $e) {
                                                $hour = -1;
                                            }
                                        }
                                    @endphp
                                    <option value="{{ $key }}" data-start="{{ $shift['start'] }}"
                                        data-end="{{ $shift['end'] }}" data-hour="{{ $hour }}"
                                        data-lunch-eligible="{{ $hour >= 05 && $hour <= 10 ? 'true' : 'false' }}">
                                        {{ $shift['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="card-attendance">
                            <h6>Jam Kerja</h6>
                            <p id="shift-time">-</p>
                        </div>

                    @endif
                @else
                    <div class="card-attendance">
                        <h6>Jam Kerja</h6>
                        <p>
                            {{ $todaySchedule
                                ? \Carbon\Carbon::parse($todaySchedule->check_in_time)->format('H.i') .
                                    ' s/d ' .
                                    \Carbon\Carbon::parse($todaySchedule->check_out_time)->format('H.i')
                                : '-' }}
                        </p>
                    </div>
                @endif

                @if ($groupAttendance === 'N' && !$lunchReq)
                    <!-- Tampilkan langsung untuk non-shift -->
                    <div class="card-attendance" id="lunch-section">
                        <h6>Makan Siang</h6>
                        <select name="lunch_request_selected" id="lunch_request_selected" class="form-select w-50" required>
                            <option value="" disabled selected>-- Pilih Request --</option>
                            <option value="Y">Request Makan Siang</option>
                            <option value="N">Tidak Request Makan Siang</option>
                        </select>
                    </div>
                @endif

                @if ($groupAttendance === 'Y' && !$lunchReq)
                    <div class="card-attendance d-none" id="lunch-section">
                        <h6>Makan Siang</h6>
                        <select name="lunch_request_selected" id="lunch_request_selected" class="form-select w-50" required>
                            <option value="" disabled selected>-- Pilih Request --</option>
                            <option value="Y">Request Makan Siang</option>
                            <option value="N">Tidak Request Makan Siang</option>
                        </select>
                    </div>
                @endif


                {{-- Absen Masuk --}}
                @if ($attendance && $attendance->check_in_time && !$attendance->check_out_time)
                    <div class="card-attendance">
                        <h6>Absen Masuk</h6>
                        <p><b>{{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H.i') }}</b></p>
                    </div>
                @endif

                <form id="attendance-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="photo" id="photo">
                    <input type="hidden" name="shift_selected" id="shift_selected_hidden">

                    @if (!$lunchReq)
                        <input type="hidden" name="lunch_request_selected" id="lunch_request_selected_hidden">
                    @endif


                    {{-- Tombol Absen Dinamis --}}
                    @if (!$attendance || $attendance->check_out_time != null)
                        @if ($groupAttendance === 'Y')
                            <button type="button" class="btn btn-success mt-2 w-100" id="attendance-btn"
                                data-action="{{ route('dashboard.employee.attendanceIn') }}">
                                Absen Masuk
                            </button>
                        @elseif ($groupAttendance === 'N' && $attendance && $attendance->check_out_time)
                            <button type="button" class="btn btn-secondary mt-2 w-100" disabled>
                                Sudah absen hari ini
                            </button>
                        @else
                            <button type="button" class="btn btn-success mt-2 w-100" id="attendance-btn"
                                data-action="{{ route('dashboard.employee.attendanceIn') }}">
                                Absen Masuk
                            </button>
                        @endif
                    @elseif ($attendance->check_in_time && !$attendance->check_out_time)
                        {{-- Sudah absen masuk, tapi belum keluar --}}
                        <button type="button" class="btn btn-danger mt-2 w-100" id="attendance-btn"
                            data-action="{{ route('dashboard.employee.attendanceOut') }}">
                            Absen Keluar
                        </button>
                    @endif
                </form>
            @endif


            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const attendanceBtn = document.getElementById("attendance-btn");

                    if (!attendanceBtn) return;

                    attendanceBtn.addEventListener("click", function() {
                        const shiftSelect = document.getElementById("shift_selected");
                        const lunchSelect = document.getElementById("lunch_request_selected");
                        const lunchHiddenInput = document.getElementById("lunch_request_selected_hidden");

                        let currentShift = null;
                        if (shiftSelect && shiftSelect.value) {
                            currentShift = shiftSelect.value;
                        } else if (document.getElementById("shift_selected_hidden")?.value) {
                            currentShift = document.getElementById("shift_selected_hidden").value;
                        }

                        if (currentShift === 'pagi') {
                            const selectedLunch = lunchSelect?.value;
                            if (!selectedLunch) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Perhatian!',
                                    text: 'Silakan pilih opsi Makan Siang terlebih dahulu.',
                                });
                                return;
                            }
                            if (lunchHiddenInput) {
                                lunchHiddenInput.value = selectedLunch;
                            }
                        } else {
                            // Untuk Shift 2 & 3: kirim nilai default "N" atau kosongkan
                            if (lunchHiddenInput) {
                                lunchHiddenInput.value = 'N'; // atau ''
                            }
                        }

                        // Lanjut ke proses kamera
                        openCameraAndSubmit(this.getAttribute("data-action"));
                    });

                    async function openCameraAndSubmit(actionUrl) {
                        const form = document.getElementById("attendance-form");
                        if (!form) return;

                        form.action = actionUrl;

                        try {
                            const stream = await navigator.mediaDevices.getUserMedia({
                                video: true
                            });
                            const video = document.createElement('video');
                            video.srcObject = stream;
                            video.autoplay = true;
                            video.width = 320;
                            video.height = 240;

                            const modalHTML = `
                    <div class="d-flex flex-column align-items-center gap-3">
                        ${video.outerHTML}
                    </div>
                `;

                            Swal.fire({
                                title: 'Ambil Foto Absensi',
                                html: modalHTML,
                                showCancelButton: true,
                                confirmButtonText: 'Ambil Foto',
                                cancelButtonText: 'Batal',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    const swalVideo = Swal.getHtmlContainer().querySelector('video');
                                    if (swalVideo) {
                                        swalVideo.srcObject = stream;
                                    }
                                },
                                preConfirm: () => {
                                    const swalVideo = Swal.getHtmlContainer().querySelector('video');
                                    if (!swalVideo) {
                                        Swal.showValidationMessage('Gagal mengambil gambar.');
                                        return false;
                                    }

                                    const canvas = document.createElement('canvas');
                                    canvas.width = swalVideo.videoWidth;
                                    canvas.height = swalVideo.videoHeight;
                                    const ctx = canvas.getContext('2d');
                                    ctx.drawImage(swalVideo, 0, 0);
                                    const dataURL = canvas.toDataURL('image/jpeg', 0.9);

                                    const photoInput = document.getElementById('photo');
                                    if (photoInput) {
                                        photoInput.value = dataURL;
                                    }

                                    // Hentikan kamera
                                    stream.getTracks().forEach(track => track.stop());
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form.submit();
                                } else {
                                    stream.getTracks().forEach(track => track.stop());
                                }
                            });
                        } catch (err) {
                            console.error('Kamera error:', err);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal mengakses kamera',
                                text: 'Pastikan izin kamera telah diberikan dan perangkat mendukung.',
                            });
                        }
                    }
                });
            </script>

            <script>
                document.getElementById("shift_selected").addEventListener("change", function() {
                    document.getElementById("shift_selected_hidden").value = this.value;
                });

                document.getElementById("lunch_request_selected").addEventListener("change", function() {
                    document.getElementById("lunch_request_selected_hidden").value = this.value;
                });

                document.addEventListener('DOMContentLoaded', function() {
                    const shiftSelect = document.getElementById('shift_selected');
                    const lunchSection = document.getElementById('lunch-section');

                    if (!shiftSelect || !lunchSection) return;

                    shiftSelect.addEventListener('change', function() {
                        const option = this.selectedOptions[0];
                        const isEligible = option ? option.dataset.lunchEligible === 'true' : false;

                        if (isEligible) {
                            lunchSection.classList.remove('d-none');
                        } else {
                            lunchSection.classList.add('d-none');
                            // Optional: reset pilihan
                            const lunchSelect = document.getElementById('lunch_request_selected');
                            if (lunchSelect) lunchSelect.value = '';
                        }
                    });
                });
            </script>

            <script>
                @if (session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: '{{ session('success') }}',
                    });
                @endif
                @if (session('error'))
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: '{{ session('error') }}',
                    });
                @endif
            </script>

            <div class="row mt-4 row-gap-2">
                {{-- <div class="col-3 text-center">
            <a href="{{ route('finance.payroll.index') }}" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/payroll.svg') }}" />

                <div class="mt-1 text">{{ __('label.payroll') }}</div>
            </a>
        </div> --}}

                <div class="col-2 w-col-custom text-center">
                    <a href="{{ route('employee.permit.index') }}"
                        class="btn-icon text-grey position-relative notif-feature-first">
                        <img src="{{ asset('images/icons/perizinan-pegawai.png') }}" />
                        @if ($totalUnreadPermit > 0)
                            <span class="badge rounded-pill bg-danger-subtle text-danger">
                                {{ $totalUnreadPermit }}
                            </span>
                        @endif
                        <div class="mt-1 text">{{ __('label.employee_permit') }}</div>
                    </a>
                </div>

                <div class="col-2 w-col-custom text-center">
                    <a href="{{ route('employee.lunch-report.index') }}" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/makan.png') }}" />

                        <div class="mt-1 text">{{ __('label.lunch') }}</div>
                    </a>
                </div>

                <div class="col-2 w-col-custom text-center">
                    <span class="btn-icon text-grey position-relative notif-feature-first" data-bs-toggle="modal"
                        data-bs-target="#exampleModal">
                        <img src="{{ asset('images/icons/laporan-kegiatan-harian.png') }}" />
                        @if ($totalUnreadIndividualActivity > 0)
                            <span class="badge rounded-pill bg-danger-subtle text-danger">
                                {{ $totalUnreadIndividualActivity }}
                            </span>
                        @endif
                        <div class="mt-1 text">{{ __('label.daily_activity') }}</div>
                    </span>
                </div>

                <div class="col-2 w-col-custom text-center">
                    <a href="{{ route('employee.attendance.index') }}" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/kehadiran.png') }}" />

                        <div class="mt-1 text">{{ __('label.attendance_employee_report') }}</div>
                    </a>
                </div>

                <div class="col-2 w-col-custom text-center">
                    <a href="#" onclick="maintenance()" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/payroll.png') }}" />

                        <div class="mt-1 text">{{ __('label.payroll') }}</div>
                    </a>
                </div>


            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="text-primary">Menu Lainnya</h5>
            <div class="row mt-3 row-gap-2">
                <div class="col-3 text-center">
                    <a href="{{ route('employee.teaching-schedule.index') }}" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/jadwal-mengajar.png') }}" />

                        <div class="mt-1 text">{{ __('label.teaching_schedule') }}</div>
                    </a>
                </div>

                <div class="col-3 text-center">
                    <a href="{{ route('employee.tahfidz.index') }}" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/absensi-tahfidz.png') }}" />

                        <div class="mt-1 text">{{ __('label.absensi_tahfidz') }}</div>
                    </a>
                </div>



                @if ($allowedSubmission)
                    <div class="col-3 text-center">
                        <a href="{{ route('employee.submission.index') }}" class="btn-icon text-grey notif-feature">
                            <img src="{{ asset('images/icons/pengadaan-barang.png') }}" />

                            @if ($totalUnreadSubmission > 0)
                                <span class="badge rounded-pill bg-danger-subtle text-danger">
                                    {{ $totalUnreadSubmission }}
                                </span>
                            @endif

                            <div class="mt-1 text">{{ __('label.submission_item') }}</div>
                        </a>
                    </div>
                @endif


                <div class="col-3 text-center">
                    <a href="{{ route('employee.student-permit.index') }}" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/perizinan-santri.png') }}" />

                        <div class="mt-1 text">{{ __('label.student_permit') }}</div>
                    </a>
                </div>

                <div class="col-3 text-center">
                    <a href="{{ route('employee.hafalan.index') }}" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/target-hafalan.png') }}" />

                        <div class="mt-1 text">{{ __('label.target_ziyadah') }}</div>
                    </a>
                </div>

                <div class="col-3 text-center">
                    <a href="{{ route('academic.violation.index') }}" class="btn-icon text-grey">
                        <img src="{{ asset('images/icons/pelanggaran.jpeg') }}" />

                        <div class="mt-1 text">{{ __('label.student_violation') }}</div>
                    </a>
                </div>


                @if (in_array($module_absence, $module_rights))
                    <div class="col-3 text-center">
                        <a href="{{ route('academic.absence.create') }}" class="btn-icon text-grey">
                            <img src="{{ asset('images/icons/absensi.png') }}" />

                            <div class="mt-1 text">{{ __('label.absence') }}</div>
                        </a>
                    </div>
                @endif

                @if ($isLogistik)
                    <div class="col-3 text-center">
                        <a href="{{ route('employee.inventory.index') }}" class="btn-icon text-grey">
                            <img src="{{ asset('images/icons/kehadiran.png') }}" />

                            <div class="mt-1 text">{{ __('label.inventory') }}</div>
                        </a>
                    </div>
                @endif

                @if ($inDepartment || $isPimpinan)
                    <div class="col-3 text-center">
                        <a href="{{ route('employee.attendance-report.index') }}" class="btn-icon text-grey">
                            <img src="{{ asset('images/icons/rekap-absensi.jpeg') }}" />

                            <div class="mt-1 text">{{ __('label.absence_report') }}</div>
                        </a>
                    </div>

                    <div class="col-3 text-center">
                        <a href="{{ route('academic.monitoring.index') }}" class="btn-icon text-grey">
                            <img src="{{ asset('images/icons/monitor-kelas.jpeg') }}" />

                            <div class="mt-1 text">{{ __('label.class_monitoring') }}</div>
                        </a>
                    </div>
                @endif

                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">
                                    {{ __('label.daily_activity_reports') }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-6 text-center">
                                        <a href="{{ route('employee.activity-report.index') }}"
                                            class="btn-icon text-grey position-relative notif-feature-activity">
                                            <img src="{{ asset('images/icons/siswa.svg') }}" />
                                            @if ($totalUnreadIndividualActivity > 0)
                                                <span class="badge rounded-pill bg-danger-subtle text-danger">
                                                    {{ $totalUnreadIndividualActivity }}
                                                </span>
                                            @endif
                                            <div class="mt-1 text">{{ __('label.individual_activity') }}</div>
                                        </a>
                                    </div>
                                    <div class="col-6 text-center">
                                        <a href="{{ route('employee.committee-activity.index') }}"
                                            class="btn-icon text-grey">
                                            <img src="{{ asset('images/icons/report.png') }}" />

                                            <div class="mt-1 text">{{ __('label.committee_activity') }}</div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    @if (session('ask_reason'))
        @php
            $askReason = session('ask_reason');
        @endphp

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '{{ $askReason['title'] ?? 'Perhatian!' }}',
                    text: '{{ $askReason['text'] ?? 'Silakan isi alasan Anda.' }}',
                    icon: '{{ $askReason['type'] ?? 'warning' }}',
                    input: 'text',
                    inputPlaceholder: 'Masukkan alasan di sini...',
                    showCancelButton: true,
                    confirmButtonText: 'Kirim',
                    // cancelButtonText: 'Batal',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Alasan tidak boleh kosong!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ route('dashboard.employee.attendance.reason') }}", {
                                method: "POST",
                                headers: {
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    reason: result.value,
                                    reason_type: '{{ $askReason['reason_type'] ?? 'in' }}'
                                })
                            })
                            .then(res => res.json())
                            .then(() => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: '{{ $askReason['success'] ?? 'Alasan Anda berhasil dikirim.' }}',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            });
                    }
                });
            });
        </script>
    @endif




    <div class="card mt-4" style="border-radius: 20px;">
        <div class="card-body">
            <div class="row g-1 text-center">
                <div class="col">
                    <a href="{{ route('service.quran.index') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/quran.png') }}" class="icon-custom-small" />

                        <div class="mt-1 text">{{ __('label.alquran') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.hadist') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/hadits.png') }}" class="icon-custom-small" />

                        <div class="mt-1 text">{{ __('label.hadist') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.posterDakwah') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/poster-dakwah.png') }}" />

                        <div class="mt-1 text">{{ __('label.poster_dakwah') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.jadwalSholat') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/lokasi.png') }}" class="icon-custom-small" />

                        <div class="mt-1 text">{{ __('label.jadwal_sholat') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.dzikirDoa') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/dzikir-doa.png') }}" />

                        <div class="mt-1 text">{{ __('label.dzikir_and_doa') }}</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function maintenance() {
        Swal.fire({
            icon: "error",
            title: "Mohon Maaf",
            text: "Sedang dalam maintenance/perbaikan",
        });
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const shiftSelect = document.getElementById("shift_selected");
        const shiftTime = document.getElementById("shift-time");

        if (shiftSelect) {
            shiftSelect.addEventListener("change", function() {
                const start = this.selectedOptions[0].dataset.start;
                const end = this.selectedOptions[0].dataset.end;

                shiftTime.textContent = `${start} s/d ${end}`;
            });
        }
    });
</script>


@push('styles')
    <style>
        .btn-icon {
            cursor: pointer;
        }

        .btn-icon img {
            width: 45px;
        }

        .btn-icon .text {
            font-size: 10px;
        }

        .btn-icon-small img {
            width: 45px;
        }

        .btn-icon-small .text {
            font-size: 12px;
        }

        .title-line {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .title-line:after {
            background: #cad0da;
            height: 1px;
            flex: 1;
            content: '';
            margin-left: 10px;
        }

        .title-line:before {
            background: none;
        }

        .card-history .icon {
            background: var(--input-border);
            border-radius: 50%;
            padding: 6px 10px;
            font-size: 25px;
            margin-right: 10px;
        }

        .card-history .text {
            font-size: 12px;
            line-height: 15px;
            padding-top: 3px;
        }

        .icon-custom-small {
            padding: 5px;
            border: 2px solid #e6e7e8;
            border-radius: 10px;
            width: 48px;
        }

        .card-attendance {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .card-attendance h6,
        .card-attendance p {
            margin-bottom: 0 !important;
        }

        .w-col-custom {
            width: 20% !important;
        }

        .notif-feature {
            position: relative;
        }

        .notif-feature span {
            position: absolute;
            top: -20px;
            right: 0;
        }

        .notif-feature-first span {
            position: absolute;
            top: -20px;
            right: -10px;
        }

        .notif-feature-activity span {
            position: absolute;
            top: -20px;
        }
    </style>
@endpush
