<!DOCTYPE html>
<html>
<head>
    <title>{{ __('label.absence_report') }}</title>

    <style>
    @page { margin: 0; }
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
        :label="__('label.absence_report')"
        label-width="180"
    />

    <div class="section">
        <table class="table-padding" style="margin-bottom: 10px;">
            <tr>
                <td><b>{{ __('label.month') }}</b></td>
                <td class="divide">:</td>
                <td>{{ Common::monthFormat($month) }}</td>
            </tr>
            <tr>
                <td><b>{{ __('label.year') }}</b></td>
                <td class="divide">:</td>
                <td>{{ $year }}</td>
            </tr>
        </table>

        <table class="table">
            <tr>
                <th style="width: 40px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 110px;text-align: center;">{{ __('label.nis') }}</th>
                <th style="width: 330px;">{{ __('label.name') }}</th>
                <th style="width: 110px;">{{ __('label.class') }}</th>
                <th style="width: 168px;">{{ __('label.absence_type') }}</th>
                <th style="width: 130px;">{{ __('label.date') }}</th>
                <th style="width: 60px;text-align: center;">{{ __('label.status') }}</th>
            </tr>

            @foreach ($absence as $index => $a)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $a->student->nis }}</td>
                    <td>{{ $a->student->name }}</td>
                    <td>{{ $a->student->class->name }}</td>
                    <td>{{ $a->absence->type->name }}</td>
                    <td>{{ Common::dateFormat($a->absence->dates, 'dd mmm yyyy') . ', ' . Common::dateFormat($a->absence->created_at, 'hh:ii WIB') }}</td>
                    <td style="text-align: center;color: {{ $status_color[$a->status->value] }}">
                        <b>{{ $a->status_name }}</b>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</body>
</html>
