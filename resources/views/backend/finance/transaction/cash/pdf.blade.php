@php
use App\Models\TransactionBill;
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>{{ $deposit->number }}</title>

    <style>
    @page {
        margin: 35px
    }

    .page-break { page-break-before: always; }

    body{
        font-family: 'DejaVu Sans Mono';
        font-size: 12px;
        color: rgb(67, 72, 78);
    }

    .table {
        border-collapse:collapse;
        font-size: 11px;
    }
    .table th{
        text-align: left;
        border: 1px solid #939ca5;
        padding: 7px;
    }
    .table td {
        word-wrap:break-word;
        width: 20%;
        vertical-align: middle;
        padding: 7px;
        border: 1px solid #939ca5;
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

    .bg-yellow {
        background: #ecd485;
    }
    .bg-green {
        background: #85ec93;
    }
    </style>
</head>
<body>
    <section>
        <div style="border-bottom: 1px solid #caced3;padding-bottom: 5px;margin-bottom: 20px;">
            <table>
                <tr>
                    <td style="width: 487px;">
                        <img src="{{ public_path('images/logo-text.png') }}" style="height: 50px;">
                    </td>
                    <td style="width: 220px;text-align: right;">
                        <h2 style="margin: 0;">{{ strtoupper(__('label.proof_deposit')) }}</h2>
                    </td>
                </tr>
            </table>
        </div>

        <h3 style="margin-bottom: 0;">Rincian Transaksi</h3>
        <div style="margin-bottom: 15px;">
            {{ Common::dateFormat($deposit->start_date) . ' s/d ' . Common::dateFormat($deposit->end_date) }}
        </div>

        <table class="table">
            <tr>
                <th style="width: 35px;text-align: center;">{{ __('label.no') }}</th>
                <th style="width: 90px;text-align: center;">{{ __('label.transaction_number') }}</th>
                <th style="width: 100px;text-align: center;">{{ __('label.transaction_date') }}</th>
                <th style="width: 85px;text-align: center;">{{ __('label.nis') }}</th>
                <th style="width: 148px;text-align: center;">{{ __('label.student_name') }}</th>
                <th style="width: 70px;text-align: center;">{{ __('label.payment_method') }}</th>
                <th style="width: 90px;text-align: right;">{{ __('label.total') }}</th>
            </tr>

            @php
            $transaction_year = [];
            $transaction_method = [];
            $transaction_type = [];
            @endphp

            @foreach ($deposit->transaction_detail as $index => $t)
                @php
                $total = $t->total - $t->unique_code;

                foreach ($t->detail as $d) {
                    if (!array_key_exists($d->year->id, $transaction_year))
                        $transaction_year[$d->year->id] = ['year' => $d->year->start . ' - ' . $d->year->end, 'list' => []];

                    if (!array_key_exists($d->type->id, $transaction_year[$d->year->id]['list']))
                        $transaction_year[$d->year->id]['list'][$d->type->id] = ['name' => $d->type->name, 'total' => 0];

                    if (!array_key_exists($d->type->id, $transaction_type))
                        $transaction_type[$d->type->id] = ['id' => $d->type->id, 'name' => $d->type->name];

                    $transaction_year[$d->year->id]['list'][$d->type->id]['total'] += $d->total;
                }

                if (!array_key_exists($t->method_id, $transaction_method))
                    $transaction_method[$t->method_id] = ['name' => $t->method, 'total' => 0];

                $transaction_method[$t->method_id]['total'] += $total;
                @endphp

                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $t->number }}</td>
                    <td>{{ Common::dateFormat($t->dates, 'dd mmm yyyy') }}</td>
                    <td>{{ $t->student->nis }}</td>
                    <td>{{ $t->student->name }}</td>
                    <td style="text-align: center;">{{ $t->method_id }}</td>
                    <td style="text-align: right;">{{ number_format($total, 0, '', '.') }}</td>
                </tr>
            @endforeach
        </table>
    </section>

    <section class="page-break">
        <h3 style="margin-bottom: 0;">Berita Acara Serah Terima</h3>
        <div style="margin-bottom: 15px;">Laporan Pembayaran Tanggal {{ Common::dateFormat($deposit->start_date) . ' s/d ' . Common::dateFormat($deposit->end_date) }}</div>

        <table class="table" style="margin-bottom: 20px;">
            <tr>
                <th class="bg-yellow" style="width: 100px;text-align: center;">{{ __('label.school_year') }}</th>
                <th class="bg-yellow" style="width: 415px;text-align: center;">{{ __('label.bill_detail') }}</th>
                <th class="bg-yellow" colspan="2" style="text-align: right;">{{ __('label.total') }}</th>
            </tr>

            @php
            $total = 0;
            @endphp

            @foreach ($transaction_year as $t)
                @php
                $index= 0;
                @endphp

                @foreach ($t['list'] as $l)
                    <tr>
                        @if ($index == 0)
                            <td rowspan="{{ count($t['list']) }}" style="text-align: center;vertical-align: top;">{{ $t['year'] }}</td>
                        @endif

                        <td>{{ $l['name'] }}</td>
                        <td style="width: 25px;text-align: center;border-right: 0;">Rp.</td>
                        <td style="width: 125px;text-align: right;border-left: 0;">{{ number_format($l['total'], 0, '', '.') }}</td>
                    </tr>

                    @php
                    $total += $l['total'];
                    $index++;
                    @endphp
                @endforeach
            @endforeach

            <tr>
                <td colspan="2"><b>Total</b></td>
                <td class="bg-green" style="width: 25px;text-align: center;border-right: 0;"><b>Rp.</b></td>
                <td class="bg-green" style="width: 125px;text-align: right;border-left: 0;"><b>{{ number_format($total, 0, '', '.') }}</b></td>
            </tr>
        </table>

        <table class="table" style="margin-bottom: 20px;">
            @foreach ($transaction_method as $t)
                <tr>
                    <td style="width: 531px;">{{ $t['name'] }}</td>
                    <td style="width: 25px;text-align: center;border-right: 0;"><b>Rp.</b></td>
                    <td style="width: 125px;text-align: right;border-left: 0;"><b>{{ number_format($t['total'], 0, '', '.') }}</b></td>
                </tr>
            @endforeach
        </table>

        <table class="table">
            <tr>
                <th style="width: 250px;">Keterangan</th>
                <th style="width: 150px;text-align: center;">Jumlah</th>
            </tr>

            @php
            $type_count = count($transaction_type);
            $no = 1;
            @endphp

            @foreach ($transaction_type as $t)
                @php
                $paid = TransactionBill::with(['bill' => fn($query) => $query->select('id', 'id_type')])
                    ->whereHas('bill', function($query) use($t) {
                        $query->whereIdType($t['id']);
                    })
                    ->whereMonths($month)
                    ->whereYears($year)
                    ->paid()
                    ->count();

                $not_paid = TransactionBill::with(['bill' => fn($query) => $query->select('id', 'id_type')])
                    ->whereHas('bill', function($query) use($t) {
                        $query->whereIdType($t['id']);
                    })
                    ->whereMonths($month)
                    ->whereYears($year)
                    ->notPaid()
                    ->count();

                $count = $paid + $not_paid;
                $percent = ($count == 0) ? 0 : ($not_paid / $count) * 100;
                @endphp

                <tr>
                    <td>{{ __('label.bill') . ' ' . $t['name'] . ' ' . Common::monthFormat($month) }}</td>
                    <td style="text-align: center;">{{ number_format($count, 0, '', '.') }}</td>
                </tr>
                <tr>
                    <td>{{ __('label.not_paid') }}</td>
                    <td style="text-align: center;">{{ number_format($not_paid, 0, '', '.') }}</td>
                </tr>
                <tr>
                    <td>{{ __('label.percentage_of_arrears') }}</td>
                    <td style="text-align: center;">{{ Common::decimalFormat($percent) .'%' }}</td>
                </tr>

                @if ($no < $type_count)
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                @endif

                @php
                $no++;
                @endphp
            @endforeach
        </table>

        <table style="margin-top: 20px;">
            <tr>
                <td style="width: 250px;text-align: center;">
                    &nbsp;<br />
                    Bendahara
                    <br /><br /><br /><br />
                    Catur Winata, S.T
                </td>
                <td style="width: 250px;text-align: center;">
                    Sragen, {{ Common::dateFormat(date('Y-m-d')) }}<br />
                    Kasir
                    <br /><br /><br /><br />
                    {{ $deposit->creator->name }}
                </td>
            </tr>
        </table>
    </section>
</body>
</html>
