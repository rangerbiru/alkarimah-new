<!DOCTYPE html>
<html>
<head>
    <title>{{ __('label.bill_not_paid') }}</title>

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
        :label="__('label.report_bill_not_paid')"
        label-width="180"
    />

    <div class="section">
        <table class="table-padding" style="margin-bottom: 10px;">
            <tr>
                <td><b>{{ __('label.level_education') }}</b></td>
                <td class="divide">:</td>
                <td>{{ strtoupper($education) }}</td>
            </tr>
            <tr>
                <td><b>{{ __('label.level_class') }}</b></td>
                <td class="divide">:</td>
                <td>{{ $class }}</td>
            </tr>
        </table>

        <table class="table">
            <tr>
                <th style="width: 40px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 100px;text-align: center;">{{ __('label.school_year') }}</th>
                <th style="width: 338px;">{{ __('label.bill_name') }}</th>
                <th style="width: 120px;">{{ __('label.nis') }}</th>
                <th style="width: 230px;">{{ __('label.student_name') }}</th>
                <th style="text-align: right;" colspan="2">{{ __('label.total') }}</th>
            </tr>

            @php
            $total = 0;
            @endphp

            @foreach ($bill as $index => $b)
                @php
                $total += $b->total;
                @endphp

                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">{{ $b->bill->year->year_name }}</td>
                    <td>
                        {{ $b->bill->name }}

                        @if ($b->bill->type->period->value == $period->monthly)
                            - Bulan {{ Common::monthFormat($b->months) . ' ' . $b->years }}
                        @elseif ($b->bill->type->period->value == $period->semester)
                            - Semester {{ $b->semester }}
                        @endif
                    </td>
                    <td>{{ $b->student->nis }}</td>
                    <td>{{ $b->student->name }}</td>
                    <td style="width: 20px;border-right: 0;">Rp.</td>
                    <td style="width: 100px;border-left: 0;text-align: right;">{{ number_format($b->total, 0, '', '.') }}</td>
                </tr>
            @endforeach

            <tr>
                <th colspan="5">Total</th>
                <th style="border-right: 0;">Rp.</th>
                <th style="border-left: 0;text-align: right;">{{ number_format($total, 0, '', '.') }}</th>
            </tr>
        </table>
    </div>
</body>
</html>
