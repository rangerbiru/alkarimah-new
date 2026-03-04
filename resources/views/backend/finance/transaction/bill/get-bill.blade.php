@if (empty($transactions))
    <tr>
        <td colspan="6"><span class="text-muted">{{ __('string.no_bill_yet') }}</span></td>
    </tr>
@else
    @foreach ($transactions as $index => $t)
        <tr>
            <td class="align-top">
                <div class="form-check form-check-md">
                    <input class="form-check-input form-check-bill" type="checkbox" name="bill[]" value="{{ $t->id }}">
                </div>
            </td>
            <td class="align-top">{{ $index + 1 }}</td>
            <td class="align-top">{{ $t->type }}</td>
            <td class="align-top">{{ $t->bill }}</td>
            <td class="align-top">{{ Common::dateFormat($t->due_date) }}</td>
            <td class="align-top">
                {{ 'Rp. ' . number_format($t->total, 0, '', '.') }}

                @if ($t->discount > 0)
                    <small class="text-muted text-decoration-line-through">({{ number_format($t->total + $t->discount, 0, '', '.') }})</small><br>
                    <small class="text-danger fw-light">Diskon : {{ 'Rp. ' . number_format($t->discount, 0, '', '.') }}</small>
                @endif
            </td>
        </tr>
    @endforeach
@endif
