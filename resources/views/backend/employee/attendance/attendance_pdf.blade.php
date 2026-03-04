<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi - Bulan {{ now()->translatedFormat('F Y') }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #eee;
        }
    </style>
</head>

<body>
    <h3 style="text-align:center;">Laporan Absensi Bulan {{ now()->translatedFormat('F Y') }}</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pegawai</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Alasan Masuk</th>
                <th>Alasan Keluar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $i => $att)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $att->employee->name ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($att->date)->translatedFormat('d F Y') }}</td>
                    <td>{{ ucfirst($att->status) }}</td>
                    <td>{{ $att->check_in_time ?? '-' }}</td>
                    <td>{{ $att->check_out_time ?? '-' }}</td>
                    <td>{{ $att->reason_in ?? '-' }}</td>
                    <td>{{ $att->reason_out ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
