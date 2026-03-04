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
    $dayTimes = $group->days->keyBy('day_name');
    $dayTimesShift = $group->shift ?? collect();
    $shiftTimes = $dayTimesShift->keyBy('day_name');

    // Tentukan tab aktif berdasarkan shift_work
    $activeNonShift = $group->shift_work === 'N' ? 'active show' : '';
    $activeShift = $group->shift_work === 'Y' ? 'active show' : '';
@endphp

<div class="modal fade" id="detail-{{ $group->id }}" tabindex="-1" aria-labelledby="modalLabel-{{ $group->id }}"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-capitalize">
                    Detail Grup - {{ $group->group_name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            {{-- TAB NAVIGATION --}}
            <div class="modal-body">
                <ul class="nav nav-tabs" id="shiftTabs-{{ $group->id }}" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeNonShift }} " id="nonshift-tab-{{ $group->id }}"
                            data-bs-toggle="tab" data-bs-target="#nonshift-{{ $group->id }}" type="button"
                            role="tab">
                            <i class="bx bx-time me-1"></i> Non-Shift
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeShift }}" id="shift-tab-{{ $group->id }}"
                            data-bs-toggle="tab" data-bs-target="#shift-{{ $group->id }}" type="button"
                            role="tab">
                            <i class="bx bx-transfer-alt me-1"></i> Multi-Shift
                        </button>
                    </li>
                </ul>
            </div>

            {{-- TAB CONTENT --}}
            <div class="modal-body">
                <div class="tab-content" id="shiftTabsContent-{{ $group->id }}">

                    {{-- TAB 1: NON-SHIFT --}}
                    <div class="tab-pane fade {{ $activeNonShift }}" id="nonshift-{{ $group->id }}"
                        role="tabpanel">
                        <form action="{{ route('hr.attendance.group.update-time', $group->id) }}" method="POST"
                            class="form-nonshift" data-group-id="{{ $group->id }}">
                            @csrf
                            @method('PUT')
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="15%">Hari</th>
                                            <th>Jam Masuk</th>
                                            <th width="10%">Toleransi Masuk</th>
                                            <th>Jam Keluar</th>
                                            <th width="10%">Toleransi Keluar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($days as $key => $label)
                                            @php $time = $dayTimes[$key] ?? null; @endphp
                                            <tr>
                                                <td class="text-capitalize fw-medium">{{ $label }}</td>
                                                <td>
                                                    <input type="time"
                                                        name="days[{{ $key }}][check_in_time]"
                                                        value="{{ $time->check_in_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        name="days[{{ $key }}][tolerance_in]"
                                                        value="{{ $time->tolerance_in ?? '' }}"
                                                        class="form-control form-control-sm" placeholder="Menit"
                                                        min="0">
                                                </td>
                                                <td>
                                                    <input type="time"
                                                        name="days[{{ $key }}][check_out_time]"
                                                        value="{{ $time->check_out_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        name="days[{{ $key }}][tolerance_out]"
                                                        value="{{ $time->tolerance_out ?? '' }}"
                                                        class="form-control form-control-sm" placeholder="Menit"
                                                        min="0">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>

                    {{-- TAB 2: MULTI-SHIFT --}}
                    <div class="tab-pane fade {{ $activeShift }}" id="shift-{{ $group->id }}" role="tabpanel">
                        <form action="{{ route('hr.attendance.group.update-time-shift', $group->id) }}" method="POST"
                            class="form-shift" data-group-id="{{ $group->id }}">
                            @csrf
                            @method('PUT')
                            <div class="alert alert-info py-2 mb-3">
                                <i class="bx bx-info-circle me-1"></i>
                                Isi hanya shift yang digunakan. Shift yang tidak dipakai boleh dikosongkan.
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead class="table">
                                        <tr>
                                            <th width="12%" rowspan="2">Hari</th>
                                            <th colspan="2" class="text-center">Shift 1</th>
                                            <th colspan="2" class="text-center">Shift 2</th>
                                            <th colspan="2" class="text-center">Shift 3</th>
                                            <th rowspan="2" width="8%">Toleransi Masuk</th>
                                            <th rowspan="2" width="8%">Toleransi Keluar</th>
                                        </tr>
                                        <tr>
                                            <th>Masuk</th>
                                            <th>Keluar</th>
                                            <th>Masuk</th>
                                            <th>Keluar</th>
                                            <th>Masuk</th>
                                            <th>Keluar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($days as $key => $label)
                                            @php
                                                $shift = $shiftTimes[$key] ?? null;
                                                $tol = $dayTimes[$key] ?? null;
                                            @endphp
                                            <tr>
                                                <td class="text-capitalize fw-medium">{{ $label }}</td>

                                                {{-- SHIFT 1 --}}
                                                <td>
                                                    <input type="time"
                                                        name="shifts[{{ $key }}][shift1_check_in_time]"
                                                        value="{{ $shift->shift1_check_in_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <input type="time"
                                                        name="shifts[{{ $key }}][shift1_check_out_time]"
                                                        value="{{ $shift->shift1_check_out_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>

                                                {{-- SHIFT 2 --}}
                                                <td>
                                                    <input type="time"
                                                        name="shifts[{{ $key }}][shift2_check_in_time]"
                                                        value="{{ $shift->shift2_check_in_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <input type="time"
                                                        name="shifts[{{ $key }}][shift2_check_out_time]"
                                                        value="{{ $shift->shift2_check_out_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>

                                                {{-- SHIFT 3 --}}
                                                <td>
                                                    <input type="time"
                                                        name="shifts[{{ $key }}][shift3_check_in_time]"
                                                        value="{{ $shift->shift3_check_in_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <input type="time"
                                                        name="shifts[{{ $key }}][shift3_check_out_time]"
                                                        value="{{ $shift->shift3_check_out_time ?? '' }}"
                                                        class="form-control form-control-sm">
                                                </td>

                                                {{-- TOLERANSI --}}
                                                <td>
                                                    <input type="number"
                                                        name="shifts[{{ $key }}][tolerance_in]"
                                                        value="{{ $tol->tolerance_in ?? '' }}"
                                                        class="form-control form-control-sm" placeholder="Mnt"
                                                        min="0">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        name="shifts[{{ $key }}][tolerance_out]"
                                                        value="{{ $tol->tolerance_out ?? '' }}"
                                                        class="form-control form-control-sm" placeholder="Mnt"
                                                        min="0">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            {{-- MODAL FOOTER --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i> Tutup
                </button>
                <button type="button" class="btn btn-primary btn-save-schedule"
                    data-group-id="{{ $group->id }}">
                    <i class="bx bx-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-item.show .nav-link,
    .nav-tabs .nav-link.active {
        background: #fcab15 !important;
        color: #fff !important;
    }
</style>
