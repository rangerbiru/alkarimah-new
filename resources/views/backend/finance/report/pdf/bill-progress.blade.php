<!DOCTYPE html>
<html>
<head>
    <title>{{ __('label.bill_progress') }}</title>

    <style>
    @page { margin: 30px 0; }
    body{
        font-family: 'Helvetica';
        font-size: 14px;
        color: rgb(67, 72, 78);
    }

    .text-primary {
        color: rgb(252, 171, 21);
    }

    .table {
        border-collapse:collapse;
        font-size: 11px;
    }
    .table th{
        text-align: left;
        border: 1px solid #b0bbc7;
        padding: 7px;
    }
    .table td {
        word-wrap:break-word;
        width: 20%;
        vertical-align: middle;
        padding: 7px;
        border: 1px solid #b0bbc7;
    }

    .section {
        padding-left: 35px;
    }
    </style>
</head>
<body>
    <x-section-pdf
        :label="__('label.report_bill_progress')"
        orientation="portrait"
    />

    <div class="section">
        <table class="table">
            <tr>
                <th style="width: 30px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 252px;text-align: center;">{{ __('label.payment_type') }}</th>
                <th style="width: 90px;text-align: right;">{{ __('label.liability') }}</th>
                <th style="width: 90px;text-align: right;">{{ __('label.paid_off2') }}</th>
                <th style="width: 90px;text-align: right;">{{ __('label.less') }}</th>
                <th style="width: 80px;text-align: center;">{{ __('label.progress') }}</th>
            </tr>

            @php
            $no = 1;
            @endphp

            @foreach ($bill_progress as $index => $progress)
                <tr>
                    <td style="text-align: center;"><b>{{ $no }}</b></td>
                    <td colspan="5"><b>{{ __('label.level_class') . ' ' . $index }}</b></td>
                </tr>

                @foreach ($progress['data'] as $index_p => $p)
                    @php
                    $no_p = $index_p + 1;
                    @endphp

                    <tr>
                        <td></td>
                        <td>{{ $no . '.' . $no_p . ' : ' . $p->type }}</td>
                        <td style="text-align: right;">{{ number_format($p->total, 0, '', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($p->paid, 0, '', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($p->remaining, 0, '', '.') }}</td>
                        <td style="text-align: center;">{{ Common::decimalFormat($p->progress) . '%' }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td></td>
                    <td><b>{{ __('label.total') . ' ' . __('label.level_class') . ' ' . $index }}</b></td>
                    <td style="text-align: right;"><b>{{ number_format($progress['total'], 0, '', '.') }}</b></td>
                    <td style="text-align: right;"><b>{{ number_format($progress['paid'], 0, '', '.') }}</b></td>
                    <td style="text-align: right;"><b>{{ number_format($progress['remaining'], 0, '', '.') }}</b></td>
                    <td style="text-align: center;"><b>{{ Common::decimalFormat($progress['progress']) . '%' }}</b></td>
                </tr>

                @php
                $no++;
                @endphp
            @endforeach

            <tr>
                <td colspan="2"><b>{{ __('label.total') }}</b></td>
                <td style="text-align: right;"><b>{{ number_format($sum->total, 0, '', '.') }}</b></td>
                <td style="text-align: right;"><b>{{ number_format($sum->paid, 0, '', '.') }}</b></td>
                <td style="text-align: right;"><b>{{ number_format($sum->remaining, 0, '', '.') }}</b></td>
                <td style="text-align: center;"><b>{{ Common::decimalFormat($sum->progress) . '%' }}</b></td>
            </tr>
        </table>
    </div>
</body>
</html>
