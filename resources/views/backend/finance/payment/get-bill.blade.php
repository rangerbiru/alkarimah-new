@foreach ($bill as $index => $b)
    @if ($index == 2)
        <div id="bill-hide">
    @endif

    <div class="bill d-flex border-bottom pb-2 mb-2">
        <div class="me-2">
            <div class="form-check form-check-md">
                <input type="checkbox" name="bill[]" value="{{ $b->id }}" class="form-check-input form-check-bill">
            </div>
        </div>
        <div>
            {{ $b->name }}

            @if ($b->period == $period->monthly)
                <br />
                <small class="text-muted">Bulan {{ Common::monthFormat($b->months) . ' ' . $b->years }}</small>
            @elseif ($b->period == $period->semester)
                <br />
                <small class="text-muted">Semester {{ $b->semester }}</small>
            @endif
        </div>
        <div class="ms-auto">
            Rp. {{ number_format($b->total, 0, '', '.') }}
        </div>
    </div>

    @if ($index == $bill_end)
        </div>
    @endif
@endforeach
