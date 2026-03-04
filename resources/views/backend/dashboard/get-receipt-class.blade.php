<tbody>
    @php
    $total = 0;
    @endphp

    @if ($class->count() == 0)
        <tr>
            <td class="ps-0" colspan="4"><span class="text-muted">{{ __('string.no_transaction_yet') }}</span></td>
        </tr>
    @else
        @foreach ($class as $index => $r)
            <tr>
                <td class="ps-0">{{ $index + 1 }}</td>
                <td>{{ $r->class->name }}</td>
                <td>{{ $r->class->waliKelas->name }}</td>
                <td class="text-end">{{ number_format($r->total, 0, '', '.') }}</td>
            </tr>

            @php
            $total += $r->total;
            @endphp
        @endforeach
    @endif
</tbody>
<tfoot>
    <tr>
        <th class="ps-0 fw-bold" colspan="3">{{ __('label.total') }}</th>
        <th class="text-end fw-bold">{{ number_format($total, 0, '', '.') }}</th>
    </tr>
</tfoot>
