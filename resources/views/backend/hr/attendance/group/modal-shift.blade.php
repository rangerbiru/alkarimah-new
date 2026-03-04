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

    $dayTimes = $group->shift->keyBy('day_name');
    $dayTimesTolerance = $group->days->keyBy('day_name');
@endphp

<div class="modal fade" id="detail-{{ $group->id }}" tabindex="-1" aria-labelledby="modalLabel-{{ $group->id }}"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('hr.attendance.group.update-time-shift', $group->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title text-capitalize">Detail Grup - {{ $group->group_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-capitalize">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hari</th>
                                    <th>Jam Masuk Shift 1</th>
                                    <th>Jam Keluar Shift 1</th>
                                    <th>Jam Masuk Shift 2</th>
                                    <th>Jam Keluar Shift 2</th>
                                    <th>Jam Masuk Shift 3</th>
                                    <th>Jam Keluar Shift 3</th>
                                    <th>Waktu Toleransi Masuk</th>
                                    <th>Waktu Toleransi Keluar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($days as $key => $label)
                                    @php
                                        $time = $dayTimes[$key] ?? null;
                                        $tol = $dayTimesTolerance[$key] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $label }}</td>

                                        {{-- SHIFT 1 --}}
                                        <td>
                                            <input type="time"
                                                name="shifts[{{ $key }}][shift1_check_in_time]"
                                                value="{{ $time->shift1_check_in_time ?? '' }}" class="form-control">
                                        </td>
                                        <td>
                                            <input type="time"
                                                name="shifts[{{ $key }}][shift1_check_out_time]"
                                                value="{{ $time->shift1_check_out_time ?? '' }}" class="form-control">
                                        </td>

                                        {{-- SHIFT 2 --}}
                                        <td>
                                            <input type="time"
                                                name="shifts[{{ $key }}][shift2_check_in_time]"
                                                value="{{ $time->shift2_check_in_time ?? '' }}" class="form-control">
                                        </td>
                                        <td>
                                            <input type="time"
                                                name="shifts[{{ $key }}][shift2_check_out_time]"
                                                value="{{ $time->shift2_check_out_time ?? '' }}" class="form-control">
                                        </td>

                                        {{-- SHIFT 3 --}}
                                        <td>
                                            <input type="time"
                                                name="shifts[{{ $key }}][shift3_check_in_time]"
                                                value="{{ $time->shift3_check_in_time ?? '' }}" class="form-control">
                                        </td>
                                        <td>
                                            <input type="time"
                                                name="shifts[{{ $key }}][shift3_check_out_time]"
                                                value="{{ $time->shift3_check_out_time ?? '' }}" class="form-control">
                                        </td>

                                        {{-- TOLERANSI --}}
                                        <td>
                                            <input type="number" name="shifts[{{ $key }}][tolerance_in]"
                                                value="{{ $tol->tolerance_in ?? '' }}" class="form-control"
                                                placeholder="Menit">
                                        </td>

                                        <td>
                                            <input type="number" name="shifts[{{ $key }}][tolerance_out]"
                                                value="{{ $tol->tolerance_out ?? '' }}" class="form-control"
                                                placeholder="Menit">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>
