<tbody>
    @foreach ($report as $index => $r)
        @php
        $progress = ($r->total == 0) ? 0 : ($r->paid / $r->total) * 100;

        if ($progress >= 90)
            $progress_color = 'bg-success';
        else if ($progress >= 26)
            $progress_color = 'bg-primary';
        else
            $progress_color = 'bg-danger';
        @endphp

        <tr>
            <td class="ps-0">{{ $index + 1 }}</td>
            <td>{{ 'Kelas ' . $r->level }}</td>
            <td class="text-end">{{ number_format($r->total, 0, '', '.') }}</td>
            <td class="text-end">{{ number_format($r->paid, 0, '', '.') }}</td>
            <td class="text-end">{{ number_format($r->remaining, 0, '', '.') }}</td>
            <td style="width: 100px;">
                <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar {{ $progress_color }} progress-bar-striped progress-bar-animated" style="width: {{ $progress }}%"></div>
                </div>
            </td>
            <td style="width: 80px;">
                {{ Common::decimalFormat($progress) . '%' }}
            </td>
        </tr>
    @endforeach
</tbody>

<tfoot>
    <tr>
        <th class="ps-0 fw-bold" colspan="2">{{ __('label.total') }}</th>
        <th class="text-end fw-bold">{{ number_format($total->bill, 0, '', '.') }}</th>
        <th class="text-end fw-bold">{{ number_format($total->paid, 0, '', '.') }}</th>
        <th class="text-end fw-bold">{{ number_format($total->remaining, 0, '', '.') }}</th>
        <th style="width: 100px;">
            <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar {{ $progress_color }} progress-bar-striped progress-bar-animated" style="width: {{ $progress }}%"></div>
            </div>
        </th>
        <th class="fw-bold" style="width: 80px;">
            {{ Common::decimalFormat($progress) . '%' }}
        </th>
    </tr>
</tfoot>
