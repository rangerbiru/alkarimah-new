@if ($withdrawal->count() == 0)
    <tr>
        <td colspan="5" class="text-center">Belum ada pengajuan pengambilan</td>
    </tr>
@else
    @foreach ($withdrawal as $index => $w)
        <tr>
            <td class="align-top">
                <div class="form-check form-check-md">
                    <input class="form-check-input form-check-withdrawal" type="checkbox" name="withdrawal[]" value="{{ $w->id }}">
                </div>
            </td>
            <td class="align-top">
                <span class="text-info fw-bold">{{ $w->number }}</span><br />
                <small>{{ Common::dateFormat($w->dates) }}</small>
            </td>
            <td class="align-top">
                {{ $w->student->name }}<br />
                <small>{{ $w->student->nis }}</small>
            </td>
            <td class="align-top">{{ strtoupper($w->student->class->level_education->value) . ' ' . $w->student->class->name }}</td>
            <td class="align-top">{{ 'Rp. ' . number_format($w->total, 0, '', '.') }}</td>
        </tr>
    @endforeach
@endif
