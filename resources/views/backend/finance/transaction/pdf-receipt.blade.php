<!DOCTYPE html>
<html>
<head>
    <title>{{ $transaction->number }}</title>

    <style>
    @page {
        margin: 35px
    }

    body{
        font-family: 'DejaVu Sans Mono';
        font-size: 12px;
        font-weight: bold;
        color: rgb(67, 72, 78);
    }

    .table th {
        text-align: left;
        border-bottom: 1px solid #caced3;
        padding-bottom: 5px;
    }
    .table td {
        padding-bottom: 5px;
    }
    .table td.border-top {
        border-top: 1px solid #caced3;
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

    .text-end {
        text-align: right !important;
    }
    .text-center {
        text-align: center !important;
    }
    </style>
</head>
<body>
    <div style="border-bottom: 1px solid #caced3;padding-bottom: 5px;">
        <table>
            <tr>
                <td style="width: 487px;">
                    <img src="{{ public_path('images/logo-text.png') }}" style="height: 50px;">
                </td>
                <td style="width: 235px;text-align: right;">
                    <h2 style="margin: 0;">{{ strtoupper(__('label.proof_payment')) }}</h2>
                </td>
            </tr>
        </table>
    </div>

    <div style="border-bottom: 1px solid #caced3;padding-bottom: 5px;">
        <table>
            <tr>
                <td style="width: 425px;">
                    <table class="table-padding">
                        @php
                        if ($transaction->is_topup_saldo) {
                            $name = $transaction->parent->name;
                            $attr = __('label.phone_number');
                            $attr_name = Common::phoneFormat($transaction->parent->phone);
                        } else if ($transaction->is_pengambilan_tabungan) {
                            $name = $transaction->personResponsible->name;
                            $attr = __('label.phone_number');
                            $attr_name = Common::phoneFormat($transaction->personResponsible->phone);
                        } else {
                            $name = $transaction->student->name;
                            $attr = __('label.nis') . ' / ' . __('label.class');
                            $attr_name = $transaction->student->nis . ' / ' . $transaction->student->class->name;
                        }

                        $cashier = ($transaction->is_method_cash) ? $transaction->cashier->name : Auth::user()->name;
                        @endphp

                        <tr>
                            <td>{{ __('label.name') }}</td>
                            <td class="divide">:</td>
                            <td>{{ $name }}</td>
                        </tr>
                        <tr>
                            <td>{{ $attr }}</td>
                            <td class="divide">:</td>
                            <td>{{ $attr_name }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('label.recipient') }}</td>
                            <td class="divide">:</td>
                            <td>{{ $cashier }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 320px;">
                    <table class="table-padding">
                        <tr>
                            <td>{{ __('label.transaction_date') }}</td>
                            <td class="divide">:</td>
                            <td>{{ Common::dateFormat($transaction->created_at, 'dd mmmm yyyy') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('label.print_date') }}</td>
                            <td class="divide">:</td>
                            <td>{{ Common::dateFormat(date('Y-m-d H:i:s'), 'dd mmm yyyy, hh:ii WIB') }}</td>
                        </tr>
                        <tr>
                            <td>{{ strtoupper(__('label.id')) }}</td>
                            <td class="divide">:</td>
                            <td>{{ $transaction->number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <div style="margin-top: 2px;border-bottom: 1px solid #caced3;padding-bottom: 5px;">
        <table class="table">
            <tr>
                <th style="width: 30px;">{{ __('label.no') }}</th>
                
                @if ($transaction->is_tagihan)
                    @php
                    $colspan = 4;
                    @endphp

                    @if ($transaction->discount == 0)
                        <th style="width: 100px;">{{ __('label.school_year') }}</th>
                        <th style="width: 135px;">{{ __('label.type') }}</th>
                        <th style="width: 315px;">{{ __('label.bill_name') }}</th>
                    @else
                        @php
                        $colspan = 6;
                        @endphp

                        <th style="width: 100px;">{{ __('label.school_year') }}</th>
                        <th style="width: 135px;">{{ __('label.type') }}</th>
                        <th style="width: 315px;">{{ __('label.bill_name') }}</th>
                        <th class="text-end">{{ __('label.cost') }}</th>
                        <th class="text-end">{{ __('label.discount') }}</th>
                    @endif
                @elseif ($transaction->is_pengambilan_tabungan)
                    @php
                    $colspan = 5;
                    @endphp

                    <th style="width: 130px;">{{ __('label.withdrawal_number') }}</th>
                    <th style="width: 105px;">{{ __('label.nis') }}</th>
                    <th style="width: 207px;">{{ __('label.student_name') }}</th>
                    <th style="width: 105px;">{{ __('label.class') }}</th>
                @else
                    @php
                    $colspan = 2;
                    @endphp
                    <th style="width: 560px;">{{ __('label.bill_name') }}</th>
                @endif

                <th class="text-end" colspan="2">{{ __('label.total') }}</th>
            </tr>

            @if ($transaction->is_tagihan)
                @foreach ($bills as $index => $b)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ str_replace(' - ', '-', $b->bill->year->year_name) }}</td>
                        <td>{{ $b->bill->type->name }}</td>
                        <td>
                            {{ $b->bill->name }}

                            @if ($b->bill->type->period->value == $period->monthly)
                                - Bulan {{ Common::monthFormat($b->months) . ' ' . $b->years }}
                            @elseif ($b->bill->type->period->value == $period->semester)
                                - Semester {{ $b->semester }}
                            @endif
                        </td>

                        @if ($transaction->discount > 0)
                            <td class="text-end">{{ number_format($b->subtotal, 0, '', '.') }}</td>
                            <td class="text-end">{{ number_format($b->discount, 0, '', '.') }}</td>
                        @endif

                        <td class="text-end" style="width: 25px;">Rp.</td>
                        <td class="text-end" style="width: 100px;">{{ number_format($b->total, 0, '', '.') }}</td>
                    </tr>
                @endforeach
            @elseif ($transaction->is_pengambilan_tabungan)
                @foreach ($withdrawals as $index => $w)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $w->number }}</td>
                        <td>{{ $w->nis }}</td>
                        <td>{{ $w->name }}</td>
                        <td>{{ $w->class_name }}</td>
                        <td>Rp.</td>
                        <td class="text-end">{{ number_format($w->total, 0, '', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>1</td>
                    <td>{{ $transaction->flag_detail->name }}</td>
                    <td>Rp.</td>
                    <td class="text-end">{{ number_format($transaction->subtotal, 0, '', '.') }}</td>
                </tr>
            @endif

            {{-- Table Footer --}}
            <tr>
                <td colspan="{{ $colspan }}" class="text-end border-top" style="padding-right: 5px;padding-top: 5px;">{{ __('label.subtotal') }}</td>
                <td class="text-primary text-end border-top" style="width: 25px;padding-top: 5px;">Rp.</td>
                <td class="text-end text-primary border-top" style="width: 100px;padding-top: 5px;">{{ number_format($transaction->subtotal, 0, '', '.') }}</td>
            </tr>

            @if ($transaction->discount > 0)
                <tr>
                    <td colspan="{{ $colspan }}" class="text-end" style="padding-right: 5px;">{{ __('label.discount') }}</td>
                    <td class="text-primary text-end pb-1 border-bottom-0" style="width: 25px;">Rp.</td>
                    <td class="text-end text-primary pb-1 border-bottom-0" style="width: 100px;">{{ number_format($transaction->discount, 0, '', '.') }}</td>
                </tr>
            @endif

            @if ($transaction->is_get_scholarship)
                <tr>
                    <td colspan="{{ $colspan }}" class="text-end" style="padding-right: 5px;">{{ __('label.scholarship') }}</td>
                    <td class="pb-1 border-bottom-0 text-end">Rp.</td>
                    <td class="text-end pb-1 border-bottom-0">{{ number_format($transaction->donation, 0, '', '.') }}</td>
                </tr>
            @endif

            @if ($transaction->unique_code > 0)
                <tr>
                    <td colspan="{{ $colspan }}" class="text-end" style="padding-right: 5px;">{{ __('label.unique_code') }}</td>
                    <td class="pb-1 border-bottom-0 text-end">Rp.</td>
                    <td class="text-end pb-1 border-bottom-0">{{ number_format($transaction->unique_code, 0, '', '.') }}</td>
                </tr>
            @endif

            <tr>
                <td colspan="{{ $colspan }}" class="text-end" style="padding-right: 5px;">{{ __('label.total') }}</td>
                <td class="text-success text-end fw-bold pb-1 border-bottom-0">Rp.</td>
                <td class="text-end text-success fw-bold pb-1 border-bottom-0">{{ number_format($transaction->total, 0, '', '.') }}</td>
            </tr>
        </table>
    </div>
    <div style="margin-top: 5px;">
        {{ __('string.please_keep_this_slip') }}.
    </div>
    <div style="margin-top: 5px;">
        <table>
            <tr>
                <td style="width: 220px;"></td>
                <td style="width: 200px;text-align: center;">
                    {{ __('label.teller') }} / {{ __('label.recipient') }}<br /><br /><br /><br />
                    [ {{ $cashier }} ]
                </td>
                <td style="width: 30px;"></td>
                <td style="width: 200px;text-align: center;">
                    {{ __('label.depositor') }}<br /><br /><br /><br />
                    [ ................. ]
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
