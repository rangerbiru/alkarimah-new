<!DOCTYPE html>
<html>
<head>
    <title>{{ __('label.bill_per_student') }}</title>

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

    .bg-success {
        color: rgb(34, 168, 130);
    }
    .bg-danger {
        color: rgb(230, 83, 60);
    }
    </style>
</head>
<body>
    <x-section-pdf
        :label="__('label.report_bill_per_student')"
    />

    <div class="section">
        <table class="table-padding" style="margin-bottom: 10px;">
            <tr>
                <td><b>{{ __('label.nis') }}</b></td>
                <td class="divide">:</td>
                <td>{{ $student->nis }}</td>
            </tr>
            <tr>
                <td><b>{{ __('label.name') }}</b></td>
                <td class="divide">:</td>
                <td>{{ $student->name }}</td>
            </tr>

            @if (!empty($bill_name))
                <tr>
                    <td><b>{{ __('label.bill') }}</b></td>
                    <td class="divide">:</td>
                    <td>{{ $bill_name }}</td>
                </tr>
            @endif
        </table>

        <table class="table">
            <tr>
                <th style="width: 40px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 80px;text-align: center;">{{ __('label.school_year') }}</th>
                <th style="width: 210px;">{{ __('label.bill_name') }}</th>
                <th style="width: 170px;">{{ __('label.student_name') }}</th>
                <th style="width: 80px;text-align: center;">{{ __('label.class') }}</th>
                <th style="width: 110px;">{{ __('label.nominal') }}</th>
                <th style="width: 100px;text-align: center;">{{ __('label.status') }}</th>
                <th style="width: 140px;">{{ __('label.payment_date') }}</th>
            </tr>

            @foreach ($transaction as $index => $t)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">{{ $t->bill->year->year_name }}</td>
                    <td>
                        {{ $t->bill->name }}

                        @if ($t->bill->type->period->value == $period->monthly)
                            - Bulan {{ Common::monthFormat($t->months) . ' ' . $t->years }}
                        @elseif ($t->bill->type->period->value == $period->semester)
                            - Semester {{ $t->semester }}
                        @endif
                    </td>
                    <td>{{ $t->student->name }}</td>
                    <td style="text-align: center;">{{ $t->student->class->name }}</td>
                    <td>{{ 'Rp. ' . number_format($t->total, 0, '', '.') }}</td>
                    <td style="text-align: center;">{!! $t->status_badge !!}</td>
                    <td>{{ ($t->status->value == $status_paid) ? Common::dateFormat($t->transaction->paid_at, 'dd mmm yyyy, hh:ii WIB') : '-' }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</body>
</html>
