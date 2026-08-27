<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Tagihan Per Jenis</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>

    <h2 class="text-center mb-10">LAPORAN TAGIHAN PER JENIS</h2>

    <span><b>Tingkat Pendidikan:</b> {{ $education ?: 'Semua Tingkatan' }}</span><br>
    <span><b>Jenis Tagihan:</b> {{ $bill_name }}</span>


    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th class="text-center" style="width: 80px;">NIS</th>
                <th>Nama Santri</th>
                <th class="text-center" style="width: 70px;">Kelas</th>
                <th class="text-right" style="width: 90px;">Total Tagihan</th>
                <th class="text-right" style="width: 90px;">Sudah Dibayar</th>
                <th class="text-right" style="width: 90px;">Sisa Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $row->nis }}</td>
                    <td>{{ $row->name }}</td>
                    <td class="text-center">{{ $row->class_name }}</td>
                    <td class="text-right">{{ number_format($row->total_amount, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->total_paid, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->total_remaining, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data transaksi.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">TOTAL</th>
                <th class="text-right">{{ number_format($grand_total, 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($grand_paid, 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($grand_remaining, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

</body>

</html>
