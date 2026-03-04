<tbody>
    @php
    $total = 0;
    @endphp

    @if (empty($type))
        <tr>
            <td class="ps-0" colspan="3"><span class="text-muted">{{ __('string.no_transaction_yet') }}</span></td>
        </tr>
    @else
        @php
        $no = 1;
        @endphp

        @foreach ($type as $index => $r)
            <tr>
                <td class="ps-0 fw-bold">{{ $no }}</td>
                <td class="fw-bold" colspan="2">{{ __('label.level_class') . ' ' . $index }}</td>
            </tr>

            @foreach ($r as $index_t => $t)
                @php
                $no_t = $index_t + 1;
                @endphp

                <tr>
                    <td class="ps-0"></td>
                    <td>{{ $no . '.' . $no_t . ' : ' . $t->type }}</td>
                    <td class="text-end">{{ number_format($t->total, 0, '', '.') }}</td>
                </tr>

                @php
                $total += $t->total;
                @endphp
            @endforeach

            @php
            $no++;
            @endphp
        @endforeach
    @endif
</tbody>
<tfoot>
    <tr>
        <th class="ps-0 fw-bold" colspan="2">{{ __('label.total') }}</th>
        <th class="text-end fw-bold">{{ number_format($total, 0, '', '.') }}</th>
    </tr>
</tfoot>
