<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ActiveTargetManzil;
use App\Models\ActiveTargetSabqi;
use App\Models\ActiveTargetZiyadah;
use App\Models\DataJenisKaldik;
use App\Models\DataKaldik;
use App\Models\DataSatuanBaris;
use App\Models\Student;
use App\Models\TargetManzil;
use App\Models\TargetSabqi;
use App\Models\TargetZiyadah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HafalanController extends Controller
{

    private $path = 'backend.employee.hafalan.';
    private $icon = 'bx bx-book-reader';
    private $iconProcess = 'bx bx-check-circle';
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $idPengampu = Auth::id();

        $sabqi = DB::connection('mysql_second')
            ->table('target_murojaah')
            ->join('data_kaldik', 'target_murojaah.nama_kaldik', '=', 'data_kaldik.nama_kaldik')
            ->select('*')
            ->where('id_pengampu', $idPengampu)
            ->get();

        $ziyadah = DB::connection('mysql_second')
            ->table('target_ziyadah')
            ->join('data_kaldik', 'target_ziyadah.nama_kaldik', '=', 'data_kaldik.nama_kaldik')
            ->select('*')
            ->where('id_pengampu', $idPengampu)
            ->get();

        $manzil = DB::connection('mysql_second')
            ->table('target_murojaah_manzil')
            ->join('data_kaldik', 'target_murojaah_manzil.nama_kaldik', '=', 'data_kaldik.nama_kaldik')
            ->select('*')
            ->where('id_pengampu', $idPengampu)
            ->get();

        $hasil = $sabqi->merge($ziyadah)->merge($manzil)->values();

        return view($this->path . 'index', [
            'title' => 'Target Hafalan',
            'icon' => $this->icon,
            'hasil' => $hasil
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $dataPeriode = DB::connection('mysql_second')->table('data_periode')->get();
        $dataHalaqoh = DB::connection('mysql_second')->table('pembagian_halaqoh')
            ->join('data_kaldik', 'pembagian_halaqoh.id_kaldik', '=', 'data_kaldik.id_kaldik')
            ->select('pembagian_halaqoh.*', 'data_kaldik.aktiv_tm')
            ->where('pembagian_halaqoh.id_pegawai', Auth::user()->id)
            ->get();

        $jenisKaldik = DataJenisKaldik::pluck('nama_kaldik', 'nama_kaldik');
        $dataKaldik = DataKaldik::all();

        $namaSantri = DB::table('student')->select('name', 'nis', 'id')->get();

        $target = DB::connection('mysql_second')->table('data_satuan_baris')->get();

        return view($this->path . 'create', [
            'title' => 'Tambah Hafalan',
            'icon' => $this->icon,
            'dataPeriode' => $dataPeriode,
            'dataHalaqoh' => $dataHalaqoh,
            'dataKaldik' => $dataKaldik,
            'jenisKaldik' => $jenisKaldik,
            'namaSantri' => $namaSantri,
            'target' => $target
        ]);
    }

    public function getHalamanByJuz($juz)
    {
        $target = DB::connection('mysql_second')->table('data_satuan_baris')->get();

        $halamanList = $target->where('juz', $juz)->pluck('halaman', 'halaman');
        return response()->json($halamanList);
    }

    public function getBarisByHalaman($halaman)
    {
        $target = DB::connection('mysql_second')->table('data_satuan_baris')->get();

        $barisList = $target->where('halaman', $halaman)->pluck('baris', 'baris');
        return response()->json($barisList);
    }

    public function checkStudent(Request $request)
    {
        $request->validate([
            'id_santri' => 'required',
            'jenis_kaldik' => 'required',
        ]);

        $exists = match ($request->jenis_kaldik) {
            'Ziyadah' => TargetZiyadah::where('id_santri', $request->id_santri)->exists(),
            'Murojaah Sabqi' => TargetSabqi::where('id_santri', $request->id_santri)->exists(),
            'Murojaah Manzil' => TargetManzil::where('id_santri', $request->id_santri)->exists(),
            default => false
        };

        if ($exists) {
            return response()->json([
                'exists' => true,
                'message' => 'Santri ini sudah memiliki target untuk jenis kaldik "' . $request->jenis_kaldik . '".'
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the data
        $validatedData = $request->validate([
            'nama_kaldik' => 'required',
            'id_santri' => 'required',
            'nama_santri' => 'required',
            'nama_lembaga' => 'required',
            'periode_kaldik' => 'required',
            'jenis_kaldik' => 'required',
            'jenis_target' => 'required',
            'target_perhari' => 'required',
            'id_pengampu' => 'required',
        ]);

        $exists = match ($request->jenis_kaldik) {
            'Ziyadah' => TargetZiyadah::where('id_santri', $request->id_santri)->exists(),
            'Murojaah Sabqi' => TargetSabqi::where('id_santri', $request->id_santri)->exists(),
            'Murojaah Manzil' => TargetManzil::where('id_santri', $request->id_santri)->exists(),
            default => false
        };

        if ($exists) {
            return back()->withErrors([
                'id_santri' => 'Santri ini sudah memiliki target untuk jenis kaldik "' . $request->jenis_kaldik . '".'
            ])->withInput();
        }

        if ($validatedData['jenis_kaldik'] == "Ziyadah") {
            $validatedData['mulai_target_juz'] = $request->mulai_target_juz;
            $validatedData['mulai_target_halaman'] = $request->mulai_target_halaman;
            $validatedData['mulai_target_baris'] = $request->mulai_target_baris;

            // Retrieve Kaldik data to calculate total target and days
            $dataKaldik = DataKaldik::where('nama_kaldik', $validatedData['nama_kaldik'])->first();

            if ($dataKaldik) {
                $validatedData['total_target'] = $validatedData['target_perhari'] * $dataKaldik->aktiv_tm;

                $dariTanggal = Carbon::parse($dataKaldik->dari_tanggal);
                $sampaiTanggal = Carbon::parse($dataKaldik->sampai_tanggal);
                // $totalHari = $dariTanggal->diffInDays($sampaiTanggal) + 1;

                $totalHari = $dataKaldik->aktiv_tm;

                // Find the starting target based on Juz, Halaman, and Baris
                $juzId = DataSatuanBaris::where('juz', $validatedData['mulai_target_juz'])
                    ->where('halaman', $validatedData['mulai_target_halaman'])
                    ->where('baris', $validatedData['mulai_target_baris'])
                    ->value('id');

                if ($juzId) {
                    // Kalkulasi target akhir
                    $targetAkhirByHari = $totalHari * $validatedData['target_perhari'];

                    if ($juzId >= 8288) {

                        $targetAkhir = $juzId - $targetAkhirByHari;
                        $idAkhir = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $targetAkhir);
                        $currentTargetId = $juzId;
                    } else {
                        $targetAkhir = $targetAkhirByHari + $juzId;
                        $idAkhir = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $targetAkhir);
                        $currentTargetId = $juzId;
                    }

                    $validatedData['akhir_target_juz'] = $idAkhir->value('juz');
                    $validatedData['akhir_target_halaman'] = $idAkhir->value('halaman');
                    $validatedData['akhir_target_baris'] = $idAkhir->value('baris');

                    // Save to TargetZiyadah table
                    $targetZiyadah = TargetZiyadah::create($validatedData);

                    $hariMap = [
                        'Minggu' => 0,
                        'Senin' => 1,
                        'Selasa' => 2,
                        'Rabu' => 3,
                        'Kamis' => 4,
                        'Jumat' => 5,
                        'Sabtu' => 6,
                    ];

                    // Konversi hari_belajar ke nilai numerik
                    $hariBelajar = array_map(function ($day) use ($hariMap) {
                        return $hariMap[trim($day)] ?? null;
                    }, explode(',', $dataKaldik->hari_belajar));

                    $hariBelajar = array_filter($hariBelajar, function ($day) {
                        return $day !== null || $day === 0;
                    });

                    if (empty($hariBelajar)) {
                        return redirect()->back()->withErrors(['error' => 'Tidak ada hari belajar yang valid ditemukan. Pastikan hari belajar benar.']);
                    }

                    // Generate daily entries for ActiveTargetZiyadah
                    $currentDate = $dariTanggal->copy();
                    $hariSekarang = 1;

                    while ($currentDate->lte($sampaiTanggal)) {
                        if ($juzId >= 8288) {
                            if (in_array($currentDate->dayOfWeek, $hariBelajar)) {
                                $endTargetId = $currentTargetId - $validatedData['target_perhari'] + 1;

                                $startData = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $currentTargetId)->first();
                                $endData = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $endTargetId)->first();

                                $targetZiyadahString = "Juz " . $startData->juz . "/Halaman " . $startData->halaman . " Baris ke " . $startData->baris .
                                    " sd Juz " . $endData->juz . "/Halaman " . $endData->halaman . " Baris ke " . $endData->baris;

                                $barisAyat = DB::connection('mysql_second')->table('data_satuan_baris')
                                    ->whereBetween('id', [$currentTargetId, $endTargetId])
                                    ->pluck('ayat')
                                    ->implode('🕌');

                                for ($i = 0; $i < $dataKaldik->sesi; $i++) {
                                    ActiveTargetZiyadah::create([
                                        'id_target_ziyadah' => $targetZiyadah->id,
                                        'id_kaldik' => $dataKaldik->id_kaldik,
                                        'hari' => $hariSekarang,
                                        'target_baris' => $validatedData['target_perhari'],
                                        'target_ziyadah' => $targetZiyadahString,
                                        'tanggal' => $currentDate->toDateString(),
                                        'baris_ayat' => $barisAyat,
                                        'id_satuan_baris' => $startData->id, // Tambahkan id_satuan_baris
                                    ]);
                                }

                                $hariSekarang++;
                                $currentTargetId -= $validatedData['target_perhari'];
                            }
                            $currentDate->addDay();
                        } else {
                            if (in_array($currentDate->dayOfWeek, $hariBelajar)) {
                                $endTargetId = $currentTargetId + $validatedData['target_perhari'] - 1;

                                $startData = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $currentTargetId)->first();
                                $endData = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $endTargetId)->first();

                                $targetZiyadahString = "Juz " . $startData->juz . "/Halaman " . $startData->halaman . " Baris ke " . $startData->baris .
                                    " sd Juz " . $endData->juz . "/Halaman " . $endData->halaman . " Baris ke " . $endData->baris;

                                $barisAyat = DB::connection('mysql_second')->table('data_satuan_baris')
                                    ->whereBetween('id', [$currentTargetId, $endTargetId])
                                    ->pluck('ayat')
                                    ->implode('🕌');

                                for ($i = 0; $i < $dataKaldik->sesi; $i++) {
                                    ActiveTargetZiyadah::create([
                                        'id_target_ziyadah' => $targetZiyadah->id,
                                        'id_kaldik' => $dataKaldik->id_kaldik,
                                        'hari' => $hariSekarang,
                                        'target_baris' => $validatedData['target_perhari'],
                                        'target_ziyadah' => $targetZiyadahString,
                                        'tanggal' => $currentDate->toDateString(),
                                        'baris_ayat' => $barisAyat,
                                        'id_satuan_baris' => $startData->id, // Tambahkan id_satuan_baris
                                    ]);
                                }

                                $hariSekarang++;
                                $currentTargetId += $validatedData['target_perhari'];
                            }
                            $currentDate->addDay();
                        }
                    }
                } else {
                    return redirect()->back()->withErrors(['error' => 'Data not found for the selected Juz, Halaman, and Baris.']);
                }
            } else {
                $validatedData['total_target'] = 0;
            }
        } else if ($validatedData['jenis_kaldik'] == "Murojaah Sabqi") {
            $validatedData['mulai_target_juz'] = $request->mulai_target_juz;
            $validatedData['mulai_target_halaman'] = $request->mulai_target_halaman;
            $validatedData['mulai_target_baris'] = $request->mulai_target_baris;
            $validatedData['akhir_target_juz'] = $request->akhir_target_juz;
            $validatedData['akhir_target_halaman'] = $request->akhir_target_halaman;
            $validatedData['akhir_target_baris'] = $request->akhir_target_baris;

            // Retrieve Kaldik data to calculate total target and days
            $dataKaldik = DataKaldik::where('nama_kaldik', $validatedData['nama_kaldik'])->first();

            if ($dataKaldik) {
                $validatedData['total_target'] = $validatedData['target_perhari'] * $dataKaldik->aktiv_tm;

                $dariTanggal = Carbon::parse($dataKaldik->dari_tanggal);
                $sampaiTanggal = Carbon::parse($dataKaldik->sampai_tanggal);
                // $totalHari = $dariTanggal->diffInDays($sampaiTanggal) + 1;

                $totalHari = $dataKaldik->aktiv_tm;

                // Find the starting target based on Juz, Halaman, and Baris
                $juzId = DataSatuanBaris::where('juz', $validatedData['mulai_target_juz'])
                    ->where('halaman', $validatedData['mulai_target_halaman'])
                    ->where('baris', $validatedData['mulai_target_baris'])
                    ->value('id');

                if ($juzId) {

                    $targetAkhirByHari = $totalHari * $validatedData['target_perhari'];

                    if ($juzId >= 8288) {

                        $targetAkhir = $juzId - $targetAkhirByHari;
                        $idAkhir = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $targetAkhir);
                        $currentTargetId = $juzId;
                    } else {
                        $targetAkhir = $targetAkhirByHari + $juzId;
                        $idAkhir = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $targetAkhir);
                        $currentTargetId = $juzId;
                    }

                    $targetMurojaah = TargetSabqi::create($validatedData);

                    $hariMap = [
                        'Minggu' => 0,
                        'Senin' => 1,
                        'Selasa' => 2,
                        'Rabu' => 3,
                        'Kamis' => 4,
                        'Jumat' => 5,
                        'Sabtu' => 6,
                    ];

                    $hariBelajar = array_map(function ($day) use ($hariMap) {
                        return $hariMap[trim($day)] ?? null;
                    }, explode(',', $dataKaldik->hari_belajar));

                    $hariBelajar = array_filter($hariBelajar, function ($day) {
                        return $day !== null || $day === 0;
                    });

                    if (empty($hariBelajar)) {
                        return redirect()->back()->withErrors(['error' => 'Tidak ada hari belajar yang valid ditemukan. Pastikan hari belajar benar.']);
                    }

                    $currentDate = $dariTanggal->copy();
                    $hariSekarang = 1;
                    $skipFirstEntry = true;

                    while ($currentDate->lte($sampaiTanggal)) {
                        if (in_array($currentDate->dayOfWeek, $hariBelajar)) {

                            if ($skipFirstEntry) {
                                $skipFirstEntry = false;
                                $currentDate->addDay();
                                continue; // Melewati entri pertama
                            }

                            $endTargetId = ($juzId >= 8288) ? $currentTargetId - $validatedData['target_perhari'] + 1 : $currentTargetId + $validatedData['target_perhari'] - 1;

                            // $startData = DB::connection('mysql_second')->table('data_satuan_baris')
                            //     ->where('halaman', $currentTargetId)
                            //     ->first();

                            // $endData = DB::connection('mysql_second')->table('data_satuan_baris')
                            //     ->where('halaman', $endTargetId)
                            //     ->first();

                            $startData = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $currentTargetId)->first();
                            $endData = DB::connection('mysql_second')->table('data_satuan_baris')->where('id', $endTargetId)->first();

                            // $targetMurojaahString = "Juz " . $startData->juz . "/Halaman " . $startData->halaman .
                            //     " sd Juz " . $endData->juz . "/Halaman " . $endData->halaman;

                            $targetMurojaahString = "Juz " . $startData->juz . "/Halaman " . $startData->halaman . " Baris ke " . $startData->baris .
                                " sd Juz " . $endData->juz . "/Halaman " . $endData->halaman . " Baris ke " . $endData->baris;

                            for ($i = 0; $i < $dataKaldik->sesi; $i++) {
                                ActiveTargetSabqi::create([
                                    'id_target_murojaah' => $targetMurojaah->id,
                                    'id_kaldik' => $dataKaldik->id_kaldik,
                                    'hari' => $hariSekarang,
                                    'target_baris' => $validatedData['target_perhari'],
                                    'target_murojaah' => $targetMurojaahString,
                                    'tanggal' => $currentDate->toDateString(),
                                ]);
                            }

                            $hariSekarang++;
                            $currentTargetId = ($juzId >= 8288) ? $currentTargetId - $validatedData['target_perhari'] : $currentTargetId + $validatedData['target_perhari'];

                            if (($juzId >= 8288 && $currentTargetId < $validatedData['akhir_target_halaman']) || ($juzId < 8288 && $currentTargetId > $validatedData['akhir_target_halaman'])) {
                                $currentTargetId = $validatedData['mulai_target_halaman'];
                            }
                        }

                        $currentDate->addDay();
                    }
                } else {
                    return redirect()->back()->withErrors(['error' => 'Data not found for the selected Juz, Halaman, and Baris.']);
                }
            } else {
                $validatedData['total_target'] = 0;
            }
        } elseif ($validatedData['jenis_kaldik'] == "Murojaah Manzil") {
            $validatedData['mulai_target_juz'] = $request->mulai_target_juz;
            $validatedData['mulai_target_halaman'] = $request->mulai_target_halaman;
            $validatedData['akhir_target_juz'] = $request->akhir_target_juz;
            $validatedData['akhir_target_halaman'] = $request->akhir_target_halaman;

            // Retrieve Kaldik data to calculate total target and days
            $dataKaldik = DataKaldik::where('nama_kaldik', $validatedData['nama_kaldik'])->first();

            if ($dataKaldik) {
                $validatedData['total_target'] = $validatedData['target_perhari'] * $dataKaldik->aktiv_tm;

                $dariTanggal = Carbon::parse($dataKaldik->dari_tanggal);
                $sampaiTanggal = Carbon::parse($dataKaldik->sampai_tanggal);
                $totalHari = $dariTanggal->diffInDays($sampaiTanggal) + 1;

                // Find the starting target based on Juz, Halaman, and Baris
                $juzId = DataSatuanBaris::where('juz', $validatedData['mulai_target_juz'])
                    ->where('halaman', $validatedData['mulai_target_halaman'])
                    ->value('id');

                if ($juzId) {
                    $orderedHalaman = DB::connection('mysql_second')->table('data_satuan_baris')
                        ->where('juz', $validatedData['mulai_target_juz'])
                        ->where('halaman', $validatedData['mulai_target_halaman'])
                        ->select('halaman')
                        ->groupBy('halaman')
                        ->first();
                    $halamanInt = intval($orderedHalaman->halaman);
                    $currentTargetId = $halamanInt;

                    $targetMurojaah = TargetManzil::create($validatedData);

                    $hariMap = [
                        'Minggu' => 0,
                        'Senin' => 1,
                        'Selasa' => 2,
                        'Rabu' => 3,
                        'Kamis' => 4,
                        'Jumat' => 5,
                        'Sabtu' => 6,
                    ];

                    // Konversi hari_belajar ke nilai numerik
                    $hariBelajar = array_map(function ($day) use ($hariMap) {
                        return $hariMap[trim($day)] ?? null;
                    }, explode(',', $dataKaldik->hari_belajar));

                    $hariBelajar = array_filter($hariBelajar, function ($day) {
                        return $day !== null || $day === 0;
                    });

                    if (empty($hariBelajar)) {
                        return redirect()->back()->withErrors(['error' => 'Tidak ada hari belajar yang valid ditemukan. Pastikan hari belajar benar.']);
                    }

                    // Generate daily entries for ActiveTargetMurojaahManzil
                    $currentDate = $dariTanggal->copy();
                    $hariSekarang = 1;

                    while ($currentDate->lte($sampaiTanggal)) {

                        if ($juzId >= 8288) {

                            if (in_array($currentDate->dayOfWeek, $hariBelajar)) {

                                $endTargetId = $currentTargetId - $validatedData['target_perhari'] + 1;

                                $startData = DB::connection('mysql_second')->table('data_satuan_baris')
                                    ->orderBy('urutan_ayat', 'asc')
                                    ->orderBy('halaman', 'desc')
                                    ->where('halaman', $currentTargetId)
                                    ->first();

                                $endData = DB::connection('mysql_second')->table('data_satuan_baris')
                                    ->orderBy('urutan_ayat', 'asc')
                                    ->orderBy('halaman', 'desc')
                                    ->where('halaman', $endTargetId)
                                    ->first();

                                $targetMurojaahString = "Juz " . $startData->juz . "/Halaman " . $startData->halaman .
                                    " sd Juz " . $endData->juz . "/Halaman " . $endData->halaman;

                                for ($i = 0; $i < $dataKaldik->sesi; $i++) {
                                    ActiveTargetManzil::create([
                                        'id_target_murojaah' => $targetMurojaah->id,
                                        'id_kaldik' => $dataKaldik->id_kaldik,
                                        'hari' => $hariSekarang,
                                        'target_halaman' => $validatedData['target_perhari'],
                                        'target_murojaah' => $targetMurojaahString,
                                        'tanggal' => $currentDate->toDateString(),
                                    ]);
                                }

                                $hariSekarang++;
                                $currentTargetId -= $validatedData['target_perhari'];

                                // Reset ke awal jika mencapai akhir
                                if ($currentTargetId < $validatedData['akhir_target_halaman']) {
                                    $currentTargetId = $validatedData['mulai_target_halaman'];
                                }
                            }

                            $currentDate->addDay();
                        } else {

                            if (in_array($currentDate->dayOfWeek, $hariBelajar)) {

                                $endTargetId = $currentTargetId + $validatedData['target_perhari'] - 1;

                                $startData = DB::connection('mysql_second')->table('data_satuan_baris')
                                    ->where('halaman', $currentTargetId)
                                    ->first();

                                $endData = DB::connection('mysql_second')->table('data_satuan_baris')
                                    ->where('halaman', $endTargetId)
                                    ->first();

                                $targetMurojaahString = "Juz " . $startData->juz . "/Halaman " . $startData->halaman .
                                    " sd Juz " . $endData->juz . "/Halaman " . $endData->halaman;

                                for ($i = 0; $i < $dataKaldik->sesi; $i++) {
                                    ActiveTargetManzil::create([
                                        'id_target_murojaah' => $targetMurojaah->id,
                                        'id_kaldik' => $dataKaldik->id_kaldik,
                                        'hari' => $hariSekarang,
                                        'target_halaman' => $validatedData['target_perhari'],
                                        'target_murojaah' => $targetMurojaahString,
                                        'tanggal' => $currentDate->toDateString(),
                                    ]);
                                }

                                $hariSekarang++;
                                $currentTargetId += $validatedData['target_perhari'];

                                // Reset ke awal jika mencapai akhir
                                if ($currentTargetId > $validatedData['akhir_target_halaman']) {
                                    $currentTargetId = $validatedData['mulai_target_halaman'];
                                }
                            }

                            $currentDate->addDay();
                        }
                    }
                } else {
                    return redirect()->back()->withErrors(['error' => 'Data not found for the selected Juz, Halaman, and Baris.']);
                }
            } else {
                $validatedData['total_target'] = 0;
            }
        }

        return redirect('/employee/hafalan')->with('success', 'Data hafalan berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, Request $request)
    {
        $jenisKaldik = $request->query('jenis_kaldik'); // Misal dikirim dari URL seperti ?jenis_kaldik=Ziyadah

        if (!$jenisKaldik) {
            abort(404, 'Jenis Kaldik tidak ditemukan');
        }

        // Ambil data sesuai jenis_kaldik
        switch ($jenisKaldik) {
            case 'Ziyadah':
                $hafalan = DB::connection('mysql_second')->table('target_ziyadah')->where('id', $id)->first();
                break;
            case 'Murojaah Sabqi':
                $hafalan = DB::connection('mysql_second')->table('target_murojaah')->where('id', $id)->first();
                break;
            case 'Murojaah Manzil':
                $hafalan = DB::connection('mysql_second')->table('target_murojaah_manzil')->where('id', $id)->first();
                break;
            default:
                abort(404, 'Jenis Kaldik tidak valid');
        }

        if (!$hafalan) {
            abort(404, 'Data hafalan tidak ditemukan');
        }

        // Data tambahan
        $dataPeriode = DB::connection('mysql_second')->table('data_periode')->get();
        $dataHalaqoh = DB::connection('mysql_second')->table('pembagian_halaqoh')
            ->join('data_kaldik', 'pembagian_halaqoh.id_kaldik', '=', 'data_kaldik.id_kaldik')
            ->select('pembagian_halaqoh.*', 'data_kaldik.aktiv_tm')
            ->where('pembagian_halaqoh.id_pegawai', Auth::user()->id)
            ->get();

        $dataKaldik = DB::connection('mysql_second')->table('data_kaldik')->get();
        $target = DB::connection('mysql_second')->table('data_satuan_baris')->get();
        $namaSantri = DB::table('student')->select('name', 'nis', 'id')->get();

        return view($this->path . 'edit', [
            'title' => 'Edit Hafalan',
            'icon' => $this->icon,
            'hafalan' => $hafalan,
            'dataPeriode' => $dataPeriode,
            'dataHalaqoh' => $dataHalaqoh,
            'dataKaldik' => $dataKaldik,
            'target' => $target,
            'namaSantri' => $namaSantri,
            'jenisKaldik' => $jenisKaldik,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $jenis = $request->input('jenis_kaldik');

        if ($jenis === 'Murojaah Sabqi') {
            ActiveTargetSabqi::where('id_target_murojaah', $id)->delete();
            TargetSabqi::where('id', $id)->delete();
        } elseif ($jenis === 'Ziyadah') {
            ActiveTargetZiyadah::where('id_target_ziyadah', $id)->delete();
            // $deleted = $db->table('target_ziyadah')->where('id', $id)->delete();
            TargetZiyadah::where('id', $id)->delete();
        } elseif ($jenis === 'Murojaah Manzil') {
            ActiveTargetManzil::where('id_target_murojaah', $id)->delete();
            // $deleted = $db->table('target_murojaah_manzil')->where('id', $id)->delete();
            TargetManzil::where('id', $id)->delete();
        } else {
            return response()->json(['success' => false, 'message' => 'Jenis kaldik tidak valid'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }



    public function datatable(Request $request)
    {
        $idPengampu = Auth::id();

        $sabqi = DB::connection('mysql_second')
            ->table('target_murojaah')
            ->join('data_kaldik', 'target_murojaah.nama_kaldik', '=', 'data_kaldik.nama_kaldik')
            ->select('*')
            ->where('id_pengampu', $idPengampu)
            ->get();

        $ziyadah = DB::connection('mysql_second')
            ->table('target_ziyadah')
            ->join('data_kaldik', 'target_ziyadah.nama_kaldik', '=', 'data_kaldik.nama_kaldik')
            ->select('*')
            ->where('id_pengampu', $idPengampu)
            ->get();

        $manzil = DB::connection('mysql_second')
            ->table('target_murojaah_manzil')
            ->join('data_kaldik', 'target_murojaah_manzil.nama_kaldik', '=', 'data_kaldik.nama_kaldik')
            ->select('*')
            ->where('id_pengampu', $idPengampu)
            ->get();

        $hasil = $sabqi->merge($ziyadah)->merge($manzil)->values();

        return response()->json([
            'data' => $hasil,
            'total' => $hasil->count()
        ]);
    }

    public function datatableById($id, $jenisKaldik)
    {

        if ($jenisKaldik === 'murojaah-sabqi') {
            $activeTargetSabqi = DB::connection('mysql_second')
                ->table('active_target_murojaah')
                ->join('active_kaldik', function ($join) {
                    $join->on('active_target_murojaah.id_kaldik', '=', 'active_kaldik.id_active_kaldik')
                        ->on('active_target_murojaah.tanggal', '=', 'active_kaldik.tanggal');
                })
                ->where('active_target_murojaah.id_target_murojaah', $id)
                ->select('active_target_murojaah.*', 'active_kaldik.keterangan')
                ->where('active_kaldik.keterangan', 'AKTIF')
                ->orderBy('active_target_murojaah.tanggal', 'asc')
                ->orderBy('active_target_murojaah.target_murojaah', 'desc')
                ->distinct()
                ->get();
            return response()->json([
                'data' => $activeTargetSabqi,
            ]);
        } elseif ($jenisKaldik === 'murojaah-manzil') {
            $activeTargetManzil = DB::connection('mysql_second')
                ->table('active_target_murojaah_manzil')
                ->join('active_kaldik', function ($join) {
                    $join->on('active_target_murojaah_manzil.id_kaldik', '=', 'active_kaldik.id_active_kaldik')
                        ->on('active_target_murojaah_manzil.tanggal', '=', 'active_kaldik.tanggal');
                })
                ->where('active_target_murojaah_manzil.id_target_murojaah', $id)
                ->select('active_target_murojaah_manzil.*', 'active_kaldik.keterangan')
                ->where('active_kaldik.keterangan', 'AKTIF')
                ->orderBy('active_target_murojaah_manzil.tanggal', 'asc')
                ->orderBy('active_target_murojaah_manzil.target_murojaah', 'desc')
                ->distinct()
                ->get();
            return response()->json([
                'data' => $activeTargetManzil,
            ]);
        } else {
            $activeTargetZiyadah = DB::connection('mysql_second')
                ->table('active_target_ziyadah')
                ->join('active_kaldik', function ($join) {
                    $join->on('active_target_ziyadah.id_kaldik', '=', 'active_kaldik.id_active_kaldik')
                        ->on('active_target_ziyadah.tanggal', '=', 'active_kaldik.tanggal');
                })
                ->where('active_target_ziyadah.id_target_ziyadah', $id)
                ->select('active_target_ziyadah.*', 'active_kaldik.keterangan')
                ->where('active_kaldik.keterangan', 'AKTIF')
                ->orderBy('active_target_ziyadah.tanggal', 'asc')
                ->orderBy('active_target_ziyadah.target_ziyadah', 'desc')
                ->distinct()
                ->get();

            return response()->json([
                'data' => $activeTargetZiyadah,
            ]);
        }
    }
}
