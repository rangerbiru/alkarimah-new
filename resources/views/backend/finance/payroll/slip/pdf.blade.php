<!DOCTYPE html>
<html>

<head>
    <title>{{ __('label.bill_progress') }}</title>

    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica';
            font-size: 14px;
            color: rgb(67, 72, 78);
        }

        .text-primary {
            color: rgb(252, 171, 21);
        }

        .table {
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            border: 1px solid #b0bbc7;
            padding: 7px;
        }

        .table td {
            word-wrap: break-word;
            width: 20%;
            vertical-align: middle;
            padding: 7px;
            border: 0;
            border-bottom: 1px dashed #cad4df;
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

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <x-section-pdf :label="__('label.payroll')" orientation="portrait" />

    <div class="section">
        <h3 class="text-center">
            RINCIAN MUKAFAAH PEGAWAI<br />
            PESANTREN AL-KARIMAH
        </h3>

        <table class="table-padding">
            <tr>
                <td>{{ __('label.name') }}</td>
                <td class="divide">:</td>
                <td>{{ $payroll->employee->name }}</td>
            </tr>
            <tr>
                <td>{{ __('label.nip') }}</td>
                <td class="divide">:</td>
                <td>{{ empty($payroll->employee->nip) ? '-' : $payroll->employee->nip }}</td>
            </tr>
            <tr>
                <td>{{ __('label.month') }}</td>
                <td class="divide">:</td>
                <td>{{ Common::monthFormat($payroll->months) . ' ' . $payroll->years }}</td>
            </tr>
            <tr>
                <td>{{ __('label.status') . ' / ' . __('label.education') }}</td>
                <td class="divide">:</td>
                <td>{{ $payroll->employee->status_employment_name . ' / ' }}{{ empty($payroll->employee->education) ? '-' : $payroll->employee->education }}
                </td>
            </tr>
            <tr>
                <td>{{ __('label.position') }}</td>
                <td class="divide">:</td>
                <td>{{ empty($payroll->employee->id_position) ? '-' : $payroll->employee->position->name }}</td>
            </tr>
        </table>

        <table class="table" style="margin-top: 15px;">
            <tr>
                <td style="width: 590px;"><b>{{ __('label.basic_salary') }}</b></td>
                <td style="width: 20px;">Rp.</td>
                <td class="text-right" style="width: 70px;">{{ number_format($payroll->salary, 0, '', '.') }}</td>
            </tr>

            @if (!empty($payroll->allowance_details->structural))
                <tr>
                    <td><b>{{ __('label.structural_allowance') }}</b></td>
                    <td></td>
                    <td></td>
                </tr>

                @foreach ($payroll->allowance_details->structural as $s)
                    <tr>
                        <td>&nbsp;&nbsp;&nbsp;{{ $s->name }}</td>
                        <td>Rp.</td>
                        <td class="text-right">{{ number_format($s->nominal, 0, '', '.') }}</td>
                    </tr>
                @endforeach
            @endif

            @if (!empty($payroll->allowance_details->liability))
                <tr>
                    <td><b>{{ __('label.liability_allowance') }}</b></td>
                    <td></td>
                    <td></td>
                </tr>

                @foreach ($payroll->allowance_details->liability as $l)
                    <tr>
                        <td>&nbsp;&nbsp;&nbsp;{{ $l->name }}</td>
                        <td>Rp.</td>
                        <td class="text-right">{{ number_format($l->nominal, 0, '', '.') }}</td>
                    </tr>
                @endforeach
            @endif

            @if (!empty($payroll->allowance_details->performance))
                <tr>
                    <td><b>{{ __('label.performance_allowance') }}</b></td>
                    <td></td>
                    <td></td>
                </tr>

                @foreach ($payroll->allowance_details->performance as $p)
                    <tr>
                        <td>&nbsp;&nbsp;&nbsp;{{ $p->name }}</td>
                        <td>Rp.</td>
                        <td class="text-right">{{ number_format($p->nominal, 0, '', '.') }}</td>
                    </tr>
                @endforeach
            @endif

            <tr>
                <td style="border-top: 1px solid #b0bbc7;border-bottom: 2px solid #5d636b;padding-top: 15px;">
                    <b>{{ __('label.total') }}</b></td>
                <td
                    style="border-top: 1px solid #b0bbc7;border-bottom: 2px solid rgb(253, 152, 0);padding-top: 15px;color:rgb(253, 152, 0)">
                    <b>Rp.</b></td>
                <td style="border-top: 1px solid #b0bbc7;border-bottom: 2px solid rgb(253, 152, 0);padding-top: 15px;color:rgb(253, 152, 0)"
                    class="text-right"><b>{{ number_format($payroll->total, 0, '', '.') }}</b></td>
            </tr>
        </table>

        <table style="margin-top: 25px;">
            <tr>
                <td style="width: 510px;"></td>
                <td class="text-center" style="width: 200px;">
                    Sragen, {{ Common::dateFormat(date('Y-m-d')) }}<br />
                    Pimpinan<br />
                    <img src="{{ public_path('images/ttd.jpeg') }}" style="height: 70px;" /><br />
                    <b>Kholid Syamhudi, Lc, M.Pd</b>
                </td>
            </tr>
        </table>

        <br /><br />
        Fasilitas Pegawai :<br />
        <table>
            <tr>
                <td style="vertical-align: top;width: 350px;">
                    <ul style="margin-left: -25px;margin-top: 5px;">
                        <li>BPJS Kesehatan</li>
                        <li>THR</li>
                        <li>SHU</li>
                        <li>Zakat Maal</li>
                        <li>Bantuan Aqiqah</li>
                        <li>Bantuan Sewa Rumah/Rumah Dinas</li>
                        <li>Beasiswa Kuliah</li>
                        <li>Bantuan Opname RS</li>
                        <li>Tiket Mudik untuk yang jauh</li>
                    </ul>
                </td>
                <td style="vertical-align: top;">
                    <ul style="margin-left: -25px;margin-top: 5px;">
                        <li>Pemeriksaan Kesehatan dan Obat-obatan di UKP</li>
                        <li>Bantuan Pernikahan</li>
                        <li>Bingkisan lebaran</li>
                        <li>Insentif Kinerja Tahunan (Berkala)</li>
                        <li>Tunjangan Beras</li>
                        <li>Pendidikan Anak di Pondok Gratis</li>
                        <li>Makan Siang untuk Pegawai yang tinggal diluar Pondok</li>
                        <li>3 kali makan untuk yang tinggal di asrama</li>
                    </ul>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
