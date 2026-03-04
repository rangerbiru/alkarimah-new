<!DOCTYPE html>
<html>
<head>
    <title>{{ __('label.ongoing_collection_spp') }}</title>

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
        :label="__('label.report_ongoing_collection_spp')"
        label-width="180"
    />

    <div class="section">
        <table class="table-padding" style="margin-bottom: 10px;">
            <tr>
                <td><b>s/d {{ __('label.month') }}</b></td>
                <td class="divide">:</td>
                <td>{{ Common::monthFormat($month) . ' ' . $year }}</td>
            </tr>

            @if (!empty($education))
                <tr>
                    <td><b>{{ __('label.level_education') }}</b></td>
                    <td class="divide">:</td>
                    <td>{{ strtoupper($education) }}</td>
                </tr>
            @endif

             @if (!empty($class))
                <tr>
                    <td><b>{{ __('label.level_class') }}</b></td>
                    <td class="divide">:</td>
                    <td>{{ $class }}</td>
                </tr>
            @endif
        </table>

        <table class="table">
            <tr>
                <th style="width: 40px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 90px;text-align: center;">{{ __('label.nis') }}</th>
                <th style="width: 150px;">{{ __('label.name') }}</th>
                <th style="width: 120px;">{{ __('label.spp_total') }}</th>

                @for ($t = 1; $t <= $data->count; $t++)
                    <th style="width: 120px;">{{ __('label.spp') . ' ' . $t }}</th>
                @endfor
            </tr>

            @php
            $no = 1;
            @endphp

            @foreach ($data->bills as $b)
                @php
                if (empty(@$b->nis))
                    continue;
                @endphp

                <tr>
                    <td style="text-align: center;">{{ $no }}</td>
                    <td>{{ $b->nis }}</td>
                    <td>{{ $b->name }}</td>
                    <td>{{ 'Rp. ' . number_format($b->total, 0, '', '.') }}</td>

                    @foreach ($b->bills as $bi)
                        <td>{{ $bi->name . ' - Rp. ' . number_format($bi->total, 0, '', '.') }}</td>
                    @endforeach
                </tr>

                @php
                $no++;
                @endphp
            @endforeach
        </table>
    </div>
</body>
</html>
