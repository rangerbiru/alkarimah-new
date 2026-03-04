@foreach ($student as $s)
    <div class="form-box-student mb-3">
        <div class="form-check-label form-check-student d-flex">
            <div class="me-2"><i class="bx bx-user bx-md text-muted"></i></div>
            <div class="form-check-text">
                <b>{{ $s->name }}</b><br />
                <small>NIS {{ $s->nis }} <span class="text-muted">/</span> Kelas {{ $s->class->name }}</small>
            </div>
        </div>

        <div class="btn-group btn-group-xs mt-1" role="group">
            <input type="radio" class="btn-check" name="status[{{ $s->id }}]" id="status-{{ $s->id }}1" value="1" checked="">
            <label class="btn btn-outline-success" for="status-{{ $s->id }}1">Hadir</label>

            <input type="radio" class="btn-check" name="status[{{ $s->id }}]" id="status-{{ $s->id }}2" value="2">
            <label class="btn btn-outline-info" for="status-{{ $s->id }}2">Izin</label>

            <input type="radio" class="btn-check" name="status[{{ $s->id }}]" id="status-{{ $s->id }}3" value="3">
            <label class="btn btn-outline-warning" for="status-{{ $s->id }}3">Sakit</label>

            <input type="radio" class="btn-check" name="status[{{ $s->id }}]" id="status-{{ $s->id }}4" value="0">
            <label class="btn btn-outline-danger" for="status-{{ $s->id }}4">Absen</label>
        </div>
    </div>
@endforeach
