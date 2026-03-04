<!DOCTYPE html>
<html>
<head>
    <title>{{ __('label.payment_method') }}</title>

    <style>
    @page { margin: 30px 0; }
    body{
        font-family: 'Helvetica';
        font-size: 14px;
        color: rgb(67, 72, 78);
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

    .text-end {
        text-align: right !important;
    }
    </style>
</head>
<body>
    <x-section-pdf
        :label="__('label.payment_method')"
        orientation="portrait"
    />

    <div class="section">
        <table class="table-padding" style="margin-bottom: 10px;">
            <tr>
                <td><b>{{ __('label.date') }}</b></td>
                <td class="divide">:</td>
                <td>{{ ($filter->start == $filter->end) ? Common::dateFormat($filter->start) : Common::dateFormat($filter->start) . ' - ' . Common::dateFormat($filter->end) }}</td>
            </tr>
        </table>

        <table class="table">
            <tr>
                <th style="width: 30px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 252px;text-align: center;">{{ __('label.payment_method') }}</th>
                <th style="width: 90px;text-align: right;">{{ __('label.total') }}</th>
            </tr>
            <tr>
                <td>1</td>
                <td>{{ __('label.cash') }}</td>
                <td class="text-end">{{ number_format($cash, 0, '', '.') }}</td>
            </tr>
            <tr>
                <td>2</td>
                <td>{{ __('label.bank_bni') }}</td>
                <td class="text-end">{{ number_format($bni, 0, '', '.') }}</td>
            </tr>
            <tr>
                <td>3</td>
                <td>{{ __('label.bank_bsi') }}</td>
                <td class="text-end">{{ number_format($bsi, 0, '', '.') }}</td>
            </tr>
            <tr>
                <td>4</td>
                <td>{{ __('label.balance_topup') }}</td>
                <td class="text-end">{{ number_format($topup, 0, '', '.') }}</td>
            </tr>
            <tr>
                <th colspan="2">{{ __('label.total') }}</th>
                <th class="text-end">{{ number_format($cash + $bni + $bsi + $topup, 0, '', '.') }}</th>
            </tr>
        </table>
    </div>
</body>
</html>
