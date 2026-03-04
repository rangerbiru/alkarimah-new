<div class="modal fade" id="detail-{{ $att->id }}" tabindex="-1" aria-labelledby="modalLabel-{{ $att->id }}"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-capitalize" id="modalLabel-{{ $att->id }}">Detail Absensi -
                    {{ $att->status }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <h6>Nama Pegawai</h6>
                <p>{{ $att->employee->name ?? '-' }}</p>

                <h6>Waktu</h6>
                <p>{{ \Carbon\Carbon::parse($att->date)->translatedFormat('l, d F Y') }}</p>

                <hr>

                <h6>Waktu Masuk</h6>
                <p>{{ $att->check_in_time ?? '-' }}</p>

                @if ($att->reason_in)
                    <h6>Alasan Masuk Terlambat</h6>
                    <p>{{ $att->reason_in ?? '-' }}</p>
                @endif

                <h6>Foto Masuk</h6>
                <img src="{{ asset('storage/attendance_photos/' . $att->photo_in) }}" alt="Foto Masuk"
                    class="img-fluid">

                <hr>

                <h6>Waktu Keluar</h6>
                <p>{{ $att->check_out_time ?? '-' }}</p>

                @if ($att->reason_in)
                    <h6>Alasan Pulang Terlambat</h6>
                    <p>{{ $att->reason_out ?? '-' }}</p>
                @endif

                <h6>Foto Keluar</h6>
                <img src="{{ asset('storage/attendance_photos/' . $att->photo_out) }}" alt="Foto Keluar"
                    class="img-fluid">

            </div>
        </div>
    </div>
</div>
