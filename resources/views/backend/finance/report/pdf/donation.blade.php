<!DOCTYPE html>
<html>
<head>
    <title>{{ __('label.donation') }}</title>

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

    .table-padding td {
        padding: 3px 5px;
        padding-left: 0;
        vertical-align: top;
    }
    .table-padding .divide {
        width: 21px;
        text-align: center;
    }

    .section {
        padding-left: 35px;
    }
    </style>
</head>
<body>
    <x-section-pdf
        :label="__('label.report_donation')"
    />

    <div class="section">
        <table class="table-padding" style="margin-bottom: 10px;">
            <tr>
                <td><b>{{ __('label.date') }}</b></td>
                <td class="divide">:</td>
                <td>{{ Common::dateFormat($start_date, 'dd mmm yyyy') . ' - ' . Common::dateFormat($end_date, 'dd mmm yyyy') }}</td>
            </tr>

            @if (!empty($donatur_name))
                <tr>
                    <td><b>{{ __('label.donatur_name') }}</b></td>
                    <td class="divide">:</td>
                    <td>{{ $donatur_name }}</td>
                </tr>
            @endif
        </table>

        <table class="table">
            <tr>
                <th style="width: 40px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 190px;">{{ __('label.donatur_name') }}</th>
                <th style="width: 120px;">{{ __('label.nis') }}</th>
                <th style="width: 190px;">{{ __('label.student_name') }}</th>
                <th style="width: 140px;text-align: center;">{{ __('label.transaction_number') }}</th>
                <th style="width: 140px;">{{ __('label.payment_date') }}</th>
                <th style="width: 129px;">{{ __('label.nominal') }}</th>
            </tr>

            @foreach ($donation as $index => $d)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $d->donation->name }}</td>
                    <td>{{ $d->student->nis }}</td>
                    <td>{{ $d->student->name }}</td>
                    <td style="text-align: center;">{{ $d->transaction->number }}</td>
                    <td>{{ Common::dateFormat($d->paid_at, 'dd mmm yyyy, hh:ii WIB') }}</td>
                    <td>{{ 'Rp. ' . number_format($d->total, 0, '', '.') }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</body>
</html>
