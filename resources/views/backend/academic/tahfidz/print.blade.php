<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <link rel="shortcut icon" href="{{ asset('images/favicon/favicon-16x16.png') }}" type="image/x-icon">
</head>

<body>

    <div class="logo">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-text.png'))) }}"
            width="180">
    </div>

    <div class="rekap">
        <h6>rekap capaian hafalan santri</h6>
        <div class="laporan">

            <div class="top">
                <h6 style="color: green">Si-Alka (Sistem Informasi Al Karimah)</h6>
                <h6 class="tanggal">Tanggal Print : {{ date('d-m-Y H:i') }}</h6>
            </div>

            <table>
                <tr>
                    <td><strong>Periode Kaldik</strong></td>
                    <td>: <span>{{ $data[0]->periode_kaldik }}</span></td>
                </tr>
                <tr>
                    <td><strong>Nama Pengajar</strong></td>
                    <td>: <span>{{ $idPengampu }}</span></td>
                </tr>
                <tr>
                    <td><strong>Nama Santri</strong></td>
                    <td>: <span>{{ $data[0]->nama_santri }}</span></td>
                </tr>
                <tr>
                    <td><strong>Target Ziyadah</strong></td>
                    @if ($jenis_kaldik === 'Ziyadah')
                        <td>: <span>Juz {{ $data[0]->mulai_target_juz }}/Halaman
                                {{ $data[0]->mulai_target_halaman }} Baris ke
                                {{ $data[0]->mulai_target_baris }} sd Juz
                                {{ $data[0]->akhir_target_juz }}/Halaman
                                {{ $data[0]->akhir_target_halaman }} Baris ke
                                {{ $data[0]->akhir_target_baris }}</span></td>
                    @elseif($jenis_kaldik === 'Murojaah Sabqi')
                        <td>: <span>Juz {{ $data[0]->mulai_target_juz }}/Halaman
                                {{ $data[0]->mulai_target_halaman }} Baris ke
                                {{ $data[0]->mulai_target_baris }} sd Juz
                                {{ $data[0]->akhir_target_juz }}/Halaman
                                {{ $data[0]->akhir_target_halaman }} Baris ke
                                {{ $data[0]->akhir_target_baris }}</span></td>
                    @elseif($jenis_kaldik === 'Murojaah Manzil')
                        <td>: <span>Juz {{ $data[0]->mulai_target_juz }}/Halaman
                                {{ $data[0]->mulai_target_halaman }} sd Juz
                                {{ $data[0]->akhir_target_juz }}/Halaman
                                {{ $data[0]->akhir_target_halaman }}</span></td>
                    @endif
                </tr>
                <tr>
                    <td><strong>Kehadiran</strong></td>
                    <td>: <span>Hadir ({{ $attendance['hadir'] }}), Izin ({{ $attendance['izin'] }}), Sakit
                            ({{ $attendance['sakit'] }}), Alpha ({{ $attendance['alpha'] }})</span></td>
                </tr>
                <tr>
                    @if ($jenis_kaldik === 'Ziyadah')
                        <td><strong>Jumlah Baris Tercapai</strong></td>
                        <td>: <span>{{ $data[0]->total_capaian_target }} Baris</span></td>
                    @elseif ($jenis_kaldik === 'Murojaah Sabqi')
                        <td><strong>Jumlah Halaman Tercapai</strong></td>
                        <td>: <span>{{ $data[0]->total_capaian_target }} Halaman</span></td>
                    @elseif ($jenis_kaldik === 'Murojaah Manzil')
                        <td><strong>Jumlah Halaman Tercapai</strong></td>
                        <td>: <span>{{ $data[0]->total_capaian_target }} Halaman</span></td>
                    @endif
                </tr>
                @php
                    if ($jenis_kaldik === 'Ziyadah') {
                        $persentase = ($data[0]->total_capaian_target / $data[0]->total_target) * 100;
                        $persentaseDibulatkan = ceil($persentase);
                        $status = $persentase >= 100 ? 'Tercapai' : 'Tidak Tercapai';
                    } elseif ($jenis_kaldik === 'Murojaah Sabqi') {
                        $persentase = ($data[0]->total_capaian_target / $data[0]->total_target) * 100;
                        $persentaseDibulatkan = ceil($persentase);
                        $status = $persentase >= 100 ? 'Tercapai' : 'Tidak Tercapai';
                    } elseif ($jenis_kaldik === 'Murojaah Manzil') {
                        $persentase = ($data[0]->total_capaian_target / $data[0]->total_target) * 100;
                        $persentaseDibulatkan = ceil($persentase);
                        $status = $persentase >= 100 ? 'Tercapai' : 'Tidak Tercapai';
                    }
                @endphp

                <tr>
                    <td><strong>Ketercapaian Target</strong></td>
                    <td>: <span>{{ $persentaseDibulatkan }} %</span></td>
                </tr>

                <tr>
                    <td><strong>Keterangan On-Target</strong></td>
                    <td>: <span>{{ $status }}</span></td>
                </tr>
            </table>
        </div>

        <div class="data">
            <table class="table table-bordered" id="data-tahfidz">
                <thead>
                    <tr>
                        <th rowspan="2" style="text-align: center; width: 10px;">No.</th>
                        <th rowspan="2" style="text-align: center;">Pertemuan</th>
                        <th colspan="2" style="text-align: center !important;">Target</th>
                        <th colspan="2" style="text-align: center !important;">Perolehan</th>
                        <th rowspan="2" style="text-align: center;">Keterangan On-Target</th>
                    </tr>
                    <tr>
                        <th style="text-align: center;">Maqra'</th>
                        <th style="text-align: center;">Jumlah</th>

                        <th style="text-align: center;">Maqra'</th>
                        <th style="text-align: center;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $no = 1;
                    @endphp
                    @if ($jenis_kaldik === 'Ziyadah')
                        @foreach ($data as $item)
                            @php
                                $persentase = ($item->capaian_target / $item->target_baris) * 100;
                                $persentaseDibulatkan = ceil($persentase);
                                $status = $persentase >= 100 ? 'Tercapai' : 'Tidak Tercapai';
                            @endphp
                            <tr>
                                <td style="text-align: center;">{{ $no++ }}</td>
                                <td style="width: 15%;">Pertemuan ke-{{ $item->pertemuan }}</td>
                                <td style="width: 25%;">{{ $item->target_ziyadah }}</td>
                                <td style="width: 8%; text-align: center;">
                                    {{ $item->target_baris }} Baris</td>
                                <td style="width: 25%;">Juz {{ $item->mulai_target_juz }} Halaman
                                    {{ $item->mulai_target_halaman }} Baris
                                    {{ $item->mulai_target_baris }} sd Juz {{ $item->capaian_target_juz }}
                                    Halaman
                                    {{ $item->capaian_target_halaman }} Baris
                                    {{ $item->capaian_target_baris }}</td>
                                </td>
                                <td style="width: 8%; text-align: center;">{{ $item->capaian_target }}
                                    Baris
                                </td>
                                <td style="color: {{ $status == 'Tercapai' ? 'green' : 'red' }}; text-align: center;">
                                    {{ $status }}
                                </td>
                            </tr>
                        @endforeach
                    @elseif ($jenis_kaldik === 'Murojaah Sabqi')
                        @foreach ($data as $item)
                            @php
                                $persentase = ($item->capaian_target / $item->target_baris) * 100;
                                $persentaseDibulatkan = ceil($persentase);
                                $status = $persentase >= 100 ? 'Tercapai' : 'Tidak Tercapai';
                            @endphp
                            <tr>
                                <td style="text-align: center;">{{ $no++ }}</td>
                                <td style="width: 15%;">Pertemuan ke-{{ $item->pertemuan }}</td>
                                <td style="width: 25%;">{{ $item->target_murojaah }}</td>
                                <td style="width: 8%; text-align: center;">
                                    {{ $item->target_baris }} Baris</td>
                                <td style="width: 25%;">Juz {{ $item->mulai_target_juz }} Halaman
                                    {{ $item->mulai_target_halaman }} Baris
                                    {{ $item->mulai_target_baris }} sd Juz {{ $item->capaian_target_juz }}
                                    Halaman
                                    {{ $item->capaian_target_halaman }} Baris
                                    {{ $item->capaian_target_baris }}</td>
                                </td>
                                <td style="width: 8%; text-align: center;">{{ $item->capaian_target }}
                                    Baris
                                </td>
                                <td style="color: {{ $status == 'Tercapai' ? 'green' : 'red' }}; text-align: center;">
                                    {{ $status }}
                                </td>
                            </tr>
                        @endforeach
                    @elseif ($jenis_kaldik === 'Murojaah Manzil')
                        @foreach ($data as $item)
                            @php
                                $persentase = ($item->capaian_target / $item->target_halaman) * 100;
                                $persentaseDibulatkan = ceil($persentase);
                                $status = $persentase >= 100 ? 'Tercapai' : 'Tidak Tercapai';
                            @endphp
                            <tr>
                                <td style="text-align: center;">{{ $no++ }}</td>
                                <td style="width: 15%;">Pertemuan ke-{{ $item->pertemuan }}</td>
                                <td style="width: 25%;">{{ $item->target_murojaah }}</td>
                                <td style="width: 8%; text-align: center;">{{ $item->target_halaman }} Halaman</td>
                                <td style="width: 25%;">Juz {{ $item->mulai_target_juz }} Halaman
                                    {{ $item->mulai_target_halaman }} sd Juz {{ $item->capaian_target_juz }} Halaman
                                    {{ $item->capaian_target_halaman }}</td>
                                </td>
                                <td style="width: 8%; text-align: center;">{{ $item->capaian_target }} Halaman</td>
                                <td style="color: {{ $status == 'Tercapai' ? 'green' : 'red' }}; text-align: center;">
                                    {{ $status }}
                                </td>

                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

    </div>
    </div>

    <footer>
        <h2>Si-Alka (Sistem Informasi Al Karimah)</h2>
    </footer>
</body>

{{-- <body>
    @if ($jenis_kaldik === 'Ziyadah')
        <h2>Ini Target Ziyadah</h2>
    @elseif ($jenis_kaldik === 'Murojaah Sabqi')
        <h2>Ini Target Murojaah</h2>
    @endif
</body> --}}

</html>

<style>
    .rekap {
        margin-top: 30px;
    }

    .rekap h6 {
        text-transform: uppercase;
        font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        margin-bottom: 5px;
    }

    .laporan {
        border-top: 2px solid #000;
        position: relative;
        margin: 0;
    }

    .tanggal {
        position: absolute;
        top: -20px;
        right: 0;
        text-align: right;
    }

    .top {
        margin-top: -20px;
    }

    p {
        margin: 10px 0;
        font-size: 12px;
        font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        font-weight: 700;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        padding-top: 10px;
    }

    td {
        font-family: Arial, sans-serif;
        font-size: 12px;
        padding: 2px 0;
        font-weight: 700
    }

    td:first-child {
        text-align: left;
        /* Rata kiri */
        width: 40%;
        /* Kolom pertama lebar 40% */
    }

    td:last-child {
        text-align: left;
        /* Rata kiri */
    }

    #data-tahfidz {
        width: 100%;
        margin-top: 30px;
        font-size: 12px;
        border: 1px solid #000;
        font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        padding: 0;
    }

    #data-tahfidz th {
        background-color: #00b9b9;
    }

    #data-tahfidz td,
    #data-tahfidz th {
        border: 1px solid #000;
        padding: 3px;
    }

    #data-tahfidz td {
        font-weight: 400;
    }

    footer {
        position: fixed;
        bottom: -45px;
        left: -45px;
        padding: 0px 45px;
        background-color: green;
        width: 100%;
        color: #fff;
        font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        text-align: right;
        font-size: 12px;
    }
</style>
