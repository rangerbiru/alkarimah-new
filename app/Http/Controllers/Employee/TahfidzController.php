<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AbsensiKbmTahfidz;
use App\Models\ActiveAbsensiKbmTahfidz;
use App\Models\ActiveTargetSabqi;
use App\Models\Parents;
use App\Models\PembagianHalaqoh;
use App\Models\ProsesAbsensiKbmTahfidz;
use App\Models\Student;
use App\Models\TargetManzil;
use App\Models\TargetSabqi;
use App\Models\TargetZiyadah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TahfidzController extends Controller
{
    // private $title = 'label.absensi_tahfidz';
    private $path = 'backend.employee.tahfidz.';
    private $icon = 'bx bx-book-reader';
    private $iconProcess = 'bx bx-check-circle';

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view($this->path . 'index', [
            'title' => 'Absensi KBM Tahfidz',
            'icon' => $this->icon,
        ]);
    }

    public function anyDataAbsensiTahfidz(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $absensi = AbsensiKbmTahfidz::where('id_pegawai', Auth::user()->id);

        $absensi_count = $absensi->count();

        if (!empty($search)) {
            $absensi = $absensi->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('name_pengampu', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $absensi_count_filter = $absensi->count();

        $absensi_data = $absensi->offset($start)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'recordsTotal' => $absensi_count,
            'recordsFiltered' => $absensi_count_filter,
            'data' => $absensi_data
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

        return view($this->path . 'create', [
            'title' => 'Tambah Absensi KBM Tahfidz',
            'icon' => $this->icon,
            'dataPeriode' => $dataPeriode,
            'dataHalaqoh' => $dataHalaqoh,
        ]);
    }

    public function getPertemuanTerpakai($idHalaqoh)
    {
        $pertemuan = DB::connection('mysql_second')
            ->table('absensi_kbm_tahfidz')
            ->where('id_halaqoh', $idHalaqoh)
            ->pluck('pertemuan_kbm')
            ->toArray();

        return response()->json($pertemuan);
    }



    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     // Validasi input
    //     $validated = $request->validate([
    //         'id_halaqoh' => 'required',
    //         'id_pegawai' => 'required',
    //         'nama_lembaga' => 'required|string|max:255',
    //         'periode_akademik' => 'required|string|max:255',
    //         'nama_pengajar' => 'required|string|max:255',
    //         'nama_halaqoh' => 'required|string|max:255',
    //         'pertemuan_kbm' => 'required|integer|min:1',
    //         'materi_kelas' => 'required|string',
    //         'catatan' => 'nullable|string',
    //         'pembukaan_doa' => 'nullable|string',
    //         'apersepsi' => 'nullable|string',
    //         'evaluasi' => 'nullable|string',
    //         'doa_penutup' => 'nullable|string',
    //     ]);

    //     $validated['pembukaan_doa'] = $request->has('pembukaan_doa') ? 'ya' : 'tidak';
    //     $validated['apersepsi'] = $request->has('apersepsi') ? 'ya' : 'tidak';
    //     $validated['evaluasi'] = $request->has('evaluasi') ? 'ya' : 'tidak';
    //     $validated['doa_penutup'] = $request->has('doa_penutup') ? 'ya' : 'tidak';

    //     // Hitung jumlah santri
    //     $jumlahSantri = DB::connection('mysql_second')->select('SELECT JSON_LENGTH(siswa) AS jumlah_siswa FROM pembagian_halaqoh WHERE id_halaqoh = ?', [$request->id_halaqoh]);
    //     $validated['jumlah_santri'] = $jumlahSantri[0]->jumlah_siswa ?? 0;

    //     // Hitung jumlah kategori dalam array keterangan
    //     $keterangan = $request->keterangan;
    //     $validated['hadir'] = collect($keterangan)->filter(fn($item) => $item === 'hadir')->count();
    //     $validated['sakit'] = collect($keterangan)->filter(fn($item) => $item === 'sakit')->count();
    //     $validated['izin'] = collect($keterangan)->filter(fn($item) => $item === 'izin')->count();
    //     $validated['alpha'] = collect($keterangan)->filter(fn($item) => $item === 'alpha')->count();


    //     $absensi = AbsensiKbmTahfidz::create($validated);
    //     $idAbsensi = $absensi->id;

    //     $absensiData = [];
    //     foreach ($keterangan as $index => $kehadiran) {
    //         $idSantri = DB::connection('mysql_second')->table('pembagian_halaqoh')
    //             ->where('id_halaqoh', $request->id_halaqoh)
    //             ->pluck('siswa')
    //             ->first();

    //         $siswaArray = json_decode($idSantri, true);

    //         $santriData = isset($siswaArray[$index]) ? $siswaArray[$index] : null;
    //         $santriId = $santriData['id'] ?? null; 

    //         if ($santriId) {
    //             $absensiData[] = [
    //                 'id_halaqoh' => $request->id_halaqoh,
    //                 'id_absensi' => $idAbsensi,
    //                 'nama_halaqoh' => $request->nama_halaqoh,
    //                 'id_santri' => $santriId,
    //                 'pertemuan' => $request->pertemuan_kbm,
    //                 'kehadiran' => $kehadiran,
    //             ];

    //             if (in_array($kehadiran, ['hadir', 'sakit', 'izin', 'alpha'])) {
    //                 DB::connection('mysql_second')->table('data_santri')
    //                     ->where('id_santri', $santriId)
    //                     ->increment($kehadiran);
    //             }
    //         }
    //     }

    //     // Batch insert data absensi ke active_absensi_kbm_tahfidz
    //     if (!empty($absensiData)) {
    //         DB::connection('mysql_second')->table('active_absensi_kbm_tahfidz')->insert($absensiData);
    //     }

    //     return redirect()->route('employee.tahfidz.index')->with('success', 'Data absensi berhasil disimpan.');
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_halaqoh' => 'required',
            'id_pegawai' => 'required',
            'nama_lembaga' => 'required|string|max:255',
            'periode_akademik' => 'required|string|max:255',
            'nama_pengajar' => 'required|string|max:255',
            'nama_halaqoh' => 'required|string|max:255',
            'pertemuan_kbm' => 'required|integer|min:1',
            'materi_kelas' => 'required|string',
            'catatan' => 'nullable|string',
            'pembukaan_doa' => 'nullable|string',
            'apersepsi' => 'nullable|string',
            'evaluasi' => 'nullable|string',
            'doa_penutup' => 'nullable|string',
            'keterangan' => 'required|array|min:1',
        ], [
            'keterangan.required' => 'Data kehadiran santri tidak ditemukan.',
            'keterangan.min' => 'Minimal satu data kehadiran santri harus diisi.',
        ]);

        $validated['pembukaan_doa'] = $request->has('pembukaan_doa') ? 'ya' : 'tidak';
        $validated['apersepsi'] = $request->has('apersepsi') ? 'ya' : 'tidak';
        $validated['evaluasi'] = $request->has('evaluasi') ? 'ya' : 'tidak';
        $validated['doa_penutup'] = $request->has('doa_penutup') ? 'ya' : 'tidak';

        $jumlahSantri = DB::connection('mysql_second')->select('SELECT JSON_LENGTH(siswa) AS jumlah_siswa FROM pembagian_halaqoh WHERE id_halaqoh = ?', [$request->id_halaqoh]);
        $validated['jumlah_santri'] = $jumlahSantri[0]->jumlah_siswa ?? 0;

        $keterangan = $request->keterangan;
        $validated['hadir'] = collect($keterangan)->filter(fn($item) => $item === 'hadir')->count();
        $validated['sakit'] = collect($keterangan)->filter(fn($item) => $item === 'sakit')->count();
        $validated['izin'] = collect($keterangan)->filter(fn($item) => $item === 'izin')->count();
        $validated['alpha'] = collect($keterangan)->filter(fn($item) => $item === 'alpha')->count();

        $absensi = AbsensiKbmTahfidz::create($validated);
        $idAbsensi = $absensi->id;

        $idSantri = DB::connection('mysql_second')->table('pembagian_halaqoh')
            ->where('id_halaqoh', $request->id_halaqoh)
            ->pluck('siswa')
            ->first();

        $siswaArray = json_decode($idSantri, true);

        if (empty($siswaArray)) {
            return redirect()->back()->withErrors(['error' => 'Data santri pada halaqoh ini tidak ditemukan.'])->withInput();
        }

        $absensiData = [];
        foreach ($keterangan as $index => $kehadiran) {
            $santriData = isset($siswaArray[$index]) ? $siswaArray[$index] : null;
            $santriId = $santriData['id'] ?? null;

            if ($santriId) {
                $absensiData[] = [
                    'id_halaqoh' => $request->id_halaqoh,
                    'id_absensi' => $idAbsensi,
                    'nama_halaqoh' => $request->nama_halaqoh,
                    'id_santri' => $santriId,
                    'pertemuan' => $request->pertemuan_kbm,
                    'kehadiran' => $kehadiran,
                ];

                if (in_array($kehadiran, ['hadir', 'sakit', 'izin', 'alpha'])) {
                    DB::connection('mysql_second')->table('data_santri')
                        ->where('id_santri', $santriId)
                        ->increment($kehadiran);
                }
            }
        }

        if (empty($absensiData)) {
            return redirect()->back()->withErrors(['error' => 'Data absensi tidak valid atau tidak ditemukan.'])->withInput();
        }

        DB::connection('mysql_second')->table('active_absensi_kbm_tahfidz')->insert($absensiData);

        return redirect()->route('employee.tahfidz.index')->with('success', 'Data absensi berhasil disimpan.');
    }


    public function getSiswaByPengampu(Request $request)
    {
        $namaKaldik = $request->input('nama_kaldik');

        if (!$namaKaldik) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $dataHalaqoh = PembagianHalaqoh::where('id_pegawai', Auth::user()->id)
            ->where('nama_kaldik', $namaKaldik)
            ->first();

        if (!$dataHalaqoh) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $idHalaqoh = $dataHalaqoh->id_halaqoh;

        $data = DB::connection('mysql_second')->table('pembagian_halaqoh')
            ->where('id_halaqoh', $idHalaqoh)
            ->value('siswa');

        $siswaList = json_decode($data, true) ?? [];

        foreach ($siswaList as $index => &$siswa) {
            $siswa['nomor'] = $index + 1;
            $siswa['hari'] = '';
            $siswa['keterangan'] = 'hadir';
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => count($siswaList),
            'recordsFiltered' => count($siswaList),
            'data' => $siswaList,
        ]);
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
    public function edit(string $id)
    {
        //
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
    public function destroy(string $id)
    {
        $absensi = AbsensiKbmTahfidz::findOrFail($id);
        $activeAbsensi = ActiveAbsensiKbmTahfidz::where('id_absensi', $absensi->id);
        $prosesAbsensi = ProsesAbsensiKbmTahfidz::where('id_absensi', $absensi->id);

        if ($absensi) {
            $activeAbsensi->delete();
            $prosesAbsensi->delete();
            $absensi->delete();
            return redirect()->route('employee.tahfidz.index')->with('success', 'Data absensi berhasil dihapus.');
        }
    }

    public function process(string $id)
    {
        $absensi = AbsensiKbmTahfidz::findOrFail($id)->where('id', $id)->where('id_pegawai', Auth::user()->id)->first();

        $jenisKaldik = DB::connection('mysql_second')->table('absensi_kbm_tahfidz')
            ->join('pembagian_halaqoh', 'absensi_kbm_tahfidz.id_halaqoh', '=', 'pembagian_halaqoh.id_halaqoh')
            ->where('absensi_kbm_tahfidz.id', $id)
            ->value('pembagian_halaqoh.jenis_kaldik');

        $target = DB::connection('mysql_second')->table('data_satuan_baris')->get();

        $activeAbsensi = collect();

        // Cek jenis_kaldik
        if ($jenisKaldik === 'Ziyadah') {
            $activeAbsensi = DB::connection('mysql_second')->table('active_absensi_kbm_tahfidz')
                ->join('target_ziyadah', 'active_absensi_kbm_tahfidz.id_santri', '=', 'target_ziyadah.id_santri')
                ->leftJoin('proses_absensi_kbm_tahfidz', function ($join) use ($id) {
                    $join->on('active_absensi_kbm_tahfidz.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri')
                        ->where('proses_absensi_kbm_tahfidz.id_absensi', '=', $id);
                })
                ->where('active_absensi_kbm_tahfidz.id_absensi', $id)
                ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
                ->whereNull('proses_absensi_kbm_tahfidz.id_santri') // Filter santri yang belum ada di proses_absensi_kbm_tahfidz
                ->select('active_absensi_kbm_tahfidz.*', 'target_ziyadah.*')
                ->get();
        } elseif ($jenisKaldik === 'Murojaah Sabqi') {
            $activeAbsensi = DB::connection('mysql_second')->table('active_absensi_kbm_tahfidz')
                ->join('target_murojaah', 'active_absensi_kbm_tahfidz.id_santri', '=', 'target_murojaah.id_santri')
                ->leftJoin('proses_absensi_kbm_tahfidz', function ($join) use ($id) {
                    $join->on('active_absensi_kbm_tahfidz.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri')
                        ->where('proses_absensi_kbm_tahfidz.id_absensi', '=', $id);
                })
                ->where('active_absensi_kbm_tahfidz.id_absensi', $id)
                ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
                ->whereNull('proses_absensi_kbm_tahfidz.id_santri') // Filter santri yang belum ada di proses_absensi_kbm_tahfidz
                ->select('active_absensi_kbm_tahfidz.*', 'target_murojaah.*')
                ->get();
        } elseif ($jenisKaldik === 'Murojaah Manzil') {
            $activeAbsensi = DB::connection('mysql_second')->table('active_absensi_kbm_tahfidz')
                ->join('target_murojaah_manzil', 'active_absensi_kbm_tahfidz.id_santri', '=', 'target_murojaah_manzil.id_santri')
                ->leftJoin('proses_absensi_kbm_tahfidz', function ($join) use ($id) {
                    $join->on('active_absensi_kbm_tahfidz.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri')
                        ->where('proses_absensi_kbm_tahfidz.id_absensi', '=', $id);
                })
                ->where('active_absensi_kbm_tahfidz.id_absensi', $id)
                ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
                ->whereNull('proses_absensi_kbm_tahfidz.id_santri') // Filter santri yang belum ada di proses_absensi_kbm_tahfidz
                ->select('active_absensi_kbm_tahfidz.*', 'target_murojaah_manzil.*')
                ->get();
        }

        return view($this->path . 'process', [
            'absensi' => $absensi,
            'title' => 'Proses Absensi KBM Tahfidz',
            'icon' => $this->iconProcess,
            'activeAbsensi' => $activeAbsensi,
            'jenisKaldik' => $jenisKaldik,
            'target' => $target
        ]);
    }

    public function getTargetZiyadah(Request $request, $idSantri)
    {
        // Ambil parameter 'hari' dari request
        $hari = $request->input('hari');

        // Query utama tanpa join ke tabel proses_absensi_kbm_tahfidz
        $query = DB::connection('mysql_second')->table('target_ziyadah')
            ->join('active_target_ziyadah', 'target_ziyadah.id', '=', 'active_target_ziyadah.id_target_ziyadah')
            ->where('target_ziyadah.id_santri', $idSantri)
            ->where('active_target_ziyadah.hari', $hari)
            ->where('target_ziyadah.jenis_kaldik', 'Ziyadah')
            ->where('target_ziyadah.jenis_target', 'Baris')
            ->select(
                'target_ziyadah.*',
                'active_target_ziyadah.target_baris',
                'active_target_ziyadah.target_ziyadah'
            );

        // Jika hari > 1, tambahkan join ke tabel proses_absensi_kbm_tahfidz
        if ($hari > 1) {
            $query->join('proses_absensi_kbm_tahfidz', 'target_ziyadah.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri')
                ->addSelect(
                    'proses_absensi_kbm_tahfidz.capaian_target_juz',
                    'proses_absensi_kbm_tahfidz.capaian_target_halaman',
                    'proses_absensi_kbm_tahfidz.capaian_target_baris'
                );
        }

        // Ambil data dari query
        $data = $query->first();

        // Jika data tidak ditemukan
        if (!$data) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        // Cari pertemuan maksimal
        $maxPertemuan = DB::connection('mysql_second')->table('proses_absensi_kbm_tahfidz')
            ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
            ->where('proses_absensi_kbm_tahfidz.id_santri', $idSantri)
            ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
            ->max('proses_absensi_kbm_tahfidz.pertemuan');

        // Ambil data pertemuan sebelumnya
        $previousData = DB::connection('mysql_second')->table('proses_absensi_kbm_tahfidz')
            ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
            ->where('proses_absensi_kbm_tahfidz.id_santri', $idSantri)
            ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
            ->where('proses_absensi_kbm_tahfidz.pertemuan', $maxPertemuan)
            ->whereNotNull('proses_absensi_kbm_tahfidz.id_target_ziyadah')
            ->select('capaian_target_juz', 'capaian_target_halaman', 'capaian_target_baris', 'proses_absensi_kbm_tahfidz.pertemuan')
            ->first();

        // Jika data hari sebelumnya ditemukan, set nilai mulai_target_*
        if ($previousData) {
            $data->mulai_proses_juz = $previousData->capaian_target_juz;
            $data->mulai_proses_halaman = $previousData->capaian_target_halaman;
            $data->mulai_proses_baris = $previousData->capaian_target_baris;
        }

        // Kembalikan data sebagai JSON
        return response()->json($data);
    }



    public function getHalamanByJuz($juz)
    {
        $subQuery = DB::connection('mysql_second')
            ->table('data_satuan_baris')
            ->where('juz', $juz)
            ->select('halaman', DB::raw('MAX(id) as last_id'))
            ->groupBy('halaman');

        $data = DB::connection('mysql_second')
            ->table('data_satuan_baris as d')
            // ->select('d.id', 'd.halaman', 'd.ayat')
            ->joinSub($subQuery, 't', function ($join) {
                $join->on('d.id', '=', 't.last_id');
            })
            ->orderByRaw('CAST(d.halaman AS UNSIGNED) ASC')
            ->get();

        return response()->json($data);
    }

    public function getBarisByHalaman($halaman)
    {
        $target = DB::connection('mysql_second')->table('data_satuan_baris')->get();

        $barisList = $target->where('halaman', $halaman)->pluck('baris', 'baris');
        return response()->json($barisList);
    }

    public function anyDataProses($id)
    {
        $absensiData = ProsesAbsensiKbmTahfidz::where('id_absensi', $id)->get();

        $santriIds = $absensiData->pluck('id_santri')->toArray();

        $santriData = DB::table('student')
            ->whereIn('id', $santriIds)
            ->get()
            ->keyBy('id');


        $result = $absensiData->map(function ($item) use ($santriData) {
            $item->student = $santriData->get($item->id_santri) ?? null;
            return $item;
        });

        // return response()->json($result);
        return response()->json([
            'data' => $result
        ]);
    }


    public function storeProsesAbsensi(Request $request, $id)
    {
        $validatedData = $request->validate([
            'id_santri' => 'required',
            'jml_target' => 'required',
            'mulai_proses_juz' => 'required',
            'mulai_proses_halaman' => 'required',
            'capaian_target_juz' => 'required',
            'capaian_target_halaman' => 'required',
            'capaian_target' => 'required',
            'pertemuan' => 'required',
        ]);

        $idAbsensi = AbsensiKbmTahfidz::findOrFail($id);

        $validatedData['id_absensi'] = $idAbsensi->id;

        $jenisKaldik = DB::connection('mysql_second')->table('absensi_kbm_tahfidz')
            ->join('pembagian_halaqoh', 'absensi_kbm_tahfidz.id_halaqoh', '=', 'pembagian_halaqoh.id_halaqoh')
            ->where('absensi_kbm_tahfidz.id', $id)
            ->value('pembagian_halaqoh.jenis_kaldik');

        if ($jenisKaldik === "Ziyadah") {
            $validatedData['id_target_ziyadah'] = $request->input('id_target');
            $validatedData['mulai_proses_baris'] = $request->input('mulai_proses_baris');
            $validatedData['capaian_target_baris'] = $request->input('capaian_target_baris');
        } else if ($jenisKaldik === "Murojaah Sabqi") {
            $validatedData['id_target_murojaah'] = $request->input('id_target');
            $validatedData['mulai_proses_baris'] = $request->input('mulai_proses_baris');
            $validatedData['capaian_target_baris'] = $request->input('capaian_target_baris');
        } else if ($jenisKaldik === "Murojaah Manzil") {
            $validatedData['id_target_murojaah_manzil'] = $request->input('id_target');
        }

        // Simpan data ke database
        // DB::connection('mysql_second')->table('proses_absensi_kbm_tahfidz')->create($validatedData);
        ProsesAbsensiKbmTahfidz::create($validatedData);
        return redirect()->back()->with('success', 'Data berhasil disimpan.');
    }

    public function destroyProsesAbsensi($id)
    {
        $deleteAbsensi = ProsesAbsensiKbmTahfidz::where('id', $id)->delete();

        if ($deleteAbsensi) {
            return redirect()->back()->with('success', 'Data berhasil dihapus.');
        }
    }

    public function getIdSurat(Request $request)
    {
        $juz = $request->input('juz');
        $halaman = $request->input('halaman');
        $baris = $request->input('baris');

        $data = DB::connection('mysql_second')->table('data_satuan_baris')->where('juz', $juz)
            ->where('halaman', $halaman)
            ->where('baris', $baris)
            ->orderBy('baris', 'desc')
            ->first();
        if ($data) {
            return response()->json($data);
        }

        return response()->json(['error' => 'Data not found'], 404);
    }


    public function getIdSuratMurojaah(Request $request)
    {
        $juz = $request->input('juz');
        $halaman = $request->input('halaman');

        $data = DB::connection('mysql_second')->table('data_satuan_baris')->where('juz', $juz)
            ->where('halaman', $halaman)
            ->orderBy('baris', 'desc')
            ->first();

        if ($data) {
            return response()->json($data);
        }

        return response()->json(['error' => 'Data not found'], 404);
    }

    public function getTargetMurojaahSabqi(Request $request, $idSantri)
    {
        // Ambil parameter 'hari' dari request
        $hari = $request->input('hari');

        $jenisKaldik = $request->input('jenis_kaldik');

        $query = DB::connection('mysql_second')->table('target_murojaah')
            ->join('active_target_murojaah', 'target_murojaah.id', '=', 'active_target_murojaah.id_target_murojaah')
            ->where('target_murojaah.id_santri', $idSantri)
            ->where('active_target_murojaah.hari', $hari)
            ->where('target_murojaah.jenis_kaldik', 'Murojaah Sabqi')
            ->where('target_murojaah.jenis_target', 'Baris')
            ->select(
                'target_murojaah.*',
                'active_target_murojaah.target_baris',
                'active_target_murojaah.target_murojaah'
            );

        // Jika hari > 1, tambahkan join ke tabel proses_absensi_kbm_tahfidz
        if ($hari > 1) {
            $query->join('proses_absensi_kbm_tahfidz', 'target_murojaah.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri')
                ->addSelect(
                    'proses_absensi_kbm_tahfidz.capaian_target_juz',
                    'proses_absensi_kbm_tahfidz.capaian_target_halaman',
                    'proses_absensi_kbm_tahfidz.capaian_target_baris'
                );
        }


        // Ambil data dari query
        $data = $query->first();

        // Jika data tidak ditemukan
        if (!$data) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $maxPertemuan = DB::connection('mysql_second')->table('proses_absensi_kbm_tahfidz')
            ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
            ->where('proses_absensi_kbm_tahfidz.id_santri', $idSantri)
            ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
            ->max('proses_absensi_kbm_tahfidz.pertemuan');

        $previousData = DB::connection('mysql_second')->table('proses_absensi_kbm_tahfidz')
            ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
            ->where('proses_absensi_kbm_tahfidz.id_santri', $idSantri)
            ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
            ->where('proses_absensi_kbm_tahfidz.pertemuan', $maxPertemuan)
            ->whereNotNull('proses_absensi_kbm_tahfidz.id_target_murojaah')
            ->select('capaian_target_juz', 'capaian_target_halaman', 'capaian_target_baris', 'proses_absensi_kbm_tahfidz.pertemuan')
            ->first();


        // Jika data hari sebelumnya ditemukan, set nilai mulai_target_*
        if ($previousData) {
            $data->mulai_proses_juz = $previousData->capaian_target_juz;
            $data->mulai_proses_halaman = $previousData->capaian_target_halaman;
            $data->mulai_proses_baris = $previousData->capaian_target_baris;
        }

        // Kembalikan data sebagai JSON
        return response()->json($data);
    }

    public function getTargetMurojaahManzil(Request $request, $idSantri)
    {
        // Ambil parameter 'hari' dari request
        $hari = $request->input('hari');

        $jenisKaldik = $request->input('jenis_kaldik');

        $query = DB::connection('mysql_second')->table('target_murojaah_manzil')
            ->join('active_target_murojaah_manzil', 'target_murojaah_manzil.id', '=', 'active_target_murojaah_manzil.id_target_murojaah')
            ->where('target_murojaah_manzil.id_santri', $idSantri)
            ->where('active_target_murojaah_manzil.hari', $hari)
            ->where('target_murojaah_manzil.jenis_kaldik', 'Murojaah Manzil')
            ->where('target_murojaah_manzil.jenis_target', 'Halaman')
            ->select(
                'target_murojaah_manzil.*',
                'active_target_murojaah_manzil.target_halaman',
                'active_target_murojaah_manzil.target_murojaah'
            );

        // Jika hari > 1, tambahkan join ke tabel proses_absensi_kbm_tahfidz
        if ($hari > 1) {
            $query->join('proses_absensi_kbm_tahfidz', 'target_murojaah_manzil.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri')
                ->addSelect(
                    'proses_absensi_kbm_tahfidz.capaian_target_juz',
                    'proses_absensi_kbm_tahfidz.capaian_target_halaman',
                );
        }


        // Ambil data dari query
        $data = $query->first();

        // Jika data tidak ditemukan
        if (!$data) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $maxPertemuan = DB::connection('mysql_second')->table('proses_absensi_kbm_tahfidz')
            ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
            ->where('proses_absensi_kbm_tahfidz.id_santri', $idSantri)
            ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
            ->max('proses_absensi_kbm_tahfidz.pertemuan');

        $previousData = DB::connection('mysql_second')->table('proses_absensi_kbm_tahfidz')
            ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
            ->where('proses_absensi_kbm_tahfidz.id_santri', $idSantri)
            ->where('active_absensi_kbm_tahfidz.kehadiran', 'hadir')
            ->where('proses_absensi_kbm_tahfidz.pertemuan', $maxPertemuan)
            ->whereNotNull('proses_absensi_kbm_tahfidz.id_target_murojaah')
            ->select('capaian_target_juz', 'capaian_target_halaman', 'capaian_target_baris', 'proses_absensi_kbm_tahfidz.pertemuan')
            ->first();


        // Jika data hari sebelumnya ditemukan, set nilai mulai_target_*
        if ($previousData) {
            $data->mulai_proses_juz = $previousData->capaian_target_juz;
            $data->mulai_proses_halaman = $previousData->capaian_target_halaman;
        }

        // Kembalikan data sebagai JSON
        return response()->json($data);
    }

    public function sendMessage(Request $request, $id)
    {
        $student = Student::withoutGlobalScopes()->withTrashed()->find($id);
        $parent = Parents::withoutGlobalScopes()
            ->withTrashed()
            ->where('id', $student->id_parent)
            ->first();

        $absensiData = ProsesAbsensiKbmTahfidz::where('id_santri', $id)
            ->latest('pertemuan')
            ->first();

        if ($absensiData->id_target_murojaah) {
            $activeTarget = TargetSabqi::where('id_santri', $id)
                ->first()?->activeTargetSabqi()
                ->where('hari', $absensiData->pertemuan)
                ->first();

            $totalTargetCapaian = DB::connection('mysql_second')->table(DB::raw("(SELECT hari, MAX(target_baris) AS target_baris
                FROM active_target_murojaah
                WHERE id_target_murojaah = {$activeTarget->id_target_murojaah}
                  AND hari <= {$absensiData->pertemuan}
                GROUP BY hari) t"))
                ->select(DB::raw("SUM(t.target_baris) as total_baris"))
                ->value('total_baris');

            $status = $activeTarget->target_baris < $absensiData->capaian_target
                ? 'Melampaui Target'
                : ($activeTarget->target_baris == $absensiData->capaian_target
                    ? 'Sesuai Target'
                    : 'Dibawah Target');
        } else if ($absensiData->id_target_murojaah_manzil) {
            $activeTarget = TargetManzil::where('id_santri', $id)
                ->first()?->activeTargetManzil()
                ->where('hari', $absensiData->pertemuan)
                ->first();

            $totalTargetCapaian = DB::connection('mysql_second')->table(DB::raw("(SELECT hari, MAX(target_halaman) AS target_halaman
                FROM active_target_murojaah_manzil
                WHERE id_target_murojaah = {$activeTarget->id_target_murojaah}
                  AND hari <= {$absensiData->pertemuan}
                GROUP BY hari) t"))
                ->select(DB::raw("SUM(t.target_halaman) as total_halaman"))
                ->value('total_halaman');

            $status = $activeTarget->target_halaman < $absensiData->capaian_target
                ? 'Melampaui Target'
                : ($activeTarget->target_halaman == $absensiData->capaian_target
                    ? 'Sesuai Target'
                    : 'Dibawah Target');
        } else if ($absensiData->id_target_ziyadah) {
            $activeTarget = TargetZiyadah::where('id_santri', $id)
                ->first()?->activeTargetZiyadah()
                ->where('hari', $absensiData->pertemuan)
                ->first();

            $totalTargetCapaian = DB::connection('mysql_second')->table(DB::raw("(SELECT hari, MAX(target_baris) AS target_baris
                FROM active_target_ziyadah
                WHERE id_target_ziyadah = {$activeTarget->id_target_ziyadah}
                  AND hari <= {$absensiData->pertemuan}
                GROUP BY hari) t"))
                ->select(DB::raw("SUM(t.target_baris) as total_baris"))
                ->value('total_baris');

            $status = $activeTarget->target_baris < $absensiData->capaian_target
                ? 'Melampaui Target'
                : ($activeTarget->target_baris == $absensiData->capaian_target
                    ? 'Sesuai Target'
                    : 'Dibawah Target');
        }

        if (!$absensiData) {
            return response()->json(['error' => 'Data absensi tidak ditemukan'], 404);
        }

        $studentName = $student->name;
        $phoneNo     = $parent->phone;
        $tanggal     = Carbon::parse($absensiData->tanggal)->translatedFormat('l, d F Y');
        $totalCapaian = DB::connection('mysql_second')->table(DB::raw("(SELECT pertemuan, MAX(capaian_target) AS target_capaian
                FROM proses_absensi_kbm_tahfidz
                WHERE id_santri = {$absensiData->id_santri}
                  AND pertemuan <= {$absensiData->pertemuan}
                GROUP BY pertemuan) t"))
            ->select(DB::raw("SUM(t.target_capaian) as total_capaian"))
            ->value('total_capaian');

        // Buat pesan berdasarkan tipe target
        $message = $this->buildMessage($absensiData, $studentName, $tanggal, $totalTargetCapaian, $status, $totalCapaian);

        if ($message) {
            $this->sendToWa($phoneNo, $message);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Membuat pesan WA berdasarkan tipe target hafalan
     */
    private function buildMessage($absensiData, $studentName, $tanggal, $totalTargetCapaian, $status, $totalCapaian)
    {
        $header = "*LAPORAN PROSES KBM TAHFIDZ*\n\n"
            . "Kepada Yth:\nOrang Tua/Wali *{$studentName}*\n\n"
            . "Berikut laporan capaian hafalan santri:\n";

        if ($absensiData->id_target_ziyadah != null) {
            return $header
                . "📘 Jenis Target : Ziyadah\n"
                . "📌 Pertemuan ke-{$absensiData->pertemuan} pada hari {$tanggal}\n"
                . "🎯 Target: {$absensiData->jml_target}\n"
                . "📖 Mulai dari Juz {$absensiData->mulai_proses_juz}, Halaman {$absensiData->mulai_proses_halaman}, Baris {$absensiData->mulai_proses_baris}\n"
                . "✅ Capaian: Juz {$absensiData->capaian_target_juz}, Halaman {$absensiData->capaian_target_halaman}, Baris {$absensiData->capaian_target_baris}\n"
                . "📊 Total capaian baris: {$absensiData->capaian_target} baris\n\n"
                . "=======================\n"
                . "*Rekapitulasi Capaian*\n\n"
                . "Total capaian saat ini : {$totalCapaian} baris\n"
                . "Target capaian saat ini : {$totalTargetCapaian} baris\n"
                . "Status : {$status}\n\n"
                . "*Program Unggulan PPIA (Tahfidz Al Quran)*";
        }

        if ($absensiData->id_target_murojaah) {
            return $header
                . "📘 Jenis Target : Murojaah Sabqi\n"
                . "📌 Pertemuan ke-{$absensiData->pertemuan} pada hari {$tanggal}\n"
                . "🎯 Target: {$absensiData->jml_target}\n"
                . "📖 Mulai dari Juz {$absensiData->mulai_proses_juz}, Halaman {$absensiData->mulai_proses_halaman}, Baris {$absensiData->mulai_proses_baris}\n"
                . "✅ Capaian: Juz {$absensiData->capaian_target_juz}, Halaman {$absensiData->capaian_target_halaman}, Baris {$absensiData->capaian_target_baris}\n"
                . "📊 Total capaian baris: {$absensiData->capaian_target} baris\n\n"
                . "=======================\n"
                . "*Rekapitulasi Capaian*\n\n"
                . "Total capaian saat ini : {$totalCapaian} baris\n"
                . "Target capaian saat ini : {$totalTargetCapaian} baris\n"
                . "Status : {$status}\n\n"
                . "*Program Unggulan PPIA (Tahfidz Al Quran)*";
        }

        if ($absensiData->id_target_murojaah_manzil) {
            return $header
                . "📘 Jenis Target : Murojaah Manzil\n"
                . "📌 Pertemuan ke-{$absensiData->pertemuan} pada hari {$tanggal}\n"
                . "🎯 Target: {$absensiData->jml_target}\n"
                . "📖 Mulai dari Juz {$absensiData->mulai_proses_juz}, Halaman {$absensiData->mulai_proses_halaman}\n"
                . "✅ Capaian: Juz {$absensiData->capaian_target_juz}, Halaman {$absensiData->capaian_target_halaman}\n"
                . "📊 Total capaian halaman: {$absensiData->capaian_target} Halaman\n\n"
                . "=======================\n"
                . "*Rekapitulasi Capaian*\n\n"
                . "Total capaian saat ini : {$totalCapaian} Halaman\n"
                . "Target capaian saat ini : {$totalTargetCapaian} Halaman\n"
                . "Status : {$status}\n\n"
                . "*Program Unggulan PPIA (Tahfidz Al Quran)*";
        }

        return null;
    }



    private function sendToWa($phone_no, $message)
    {
        $message = preg_replace("/(\n)/", "<ENTER>", $message);
        $message = preg_replace("/(\r)/", "<ENTER>", $message);

        $phone_no = preg_replace("/(\n)/", ",", $phone_no);
        $phone_no = preg_replace("/(\r)/", "", $phone_no);

        $data = [
            "phone_no" => $phone_no,
            "key" => "edf3fba125169941c4fe3355145fe8c9cd71b2db16f7feaa",
            "message" => $message
        ];
        $data_string = json_encode($data);

        $ch = curl_init('http://116.203.92.59/api/send_message');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length' => strlen($data_string)
            )
        );
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;

        // Kirim ke CS
        $nowa_cs = "+6282137017491";
        $phone_no = $nowa_cs;
        $message = preg_replace("/(\n)/", "<ENTER>", $message);
        $message = preg_replace("/(\r)/", "<ENTER>", $message);

        $phone_no = preg_replace("/(\n)/", ",", $phone_no);
        $phone_no = preg_replace("/(\r)/", "", $phone_no);

        $data = [
            "phone_no" => $phone_no,
            "key" => "edf3fba125169941c4fe3355145fe8c9cd71b2db16f7feaa",
            "message" => $message
        ];
        $data_string = json_encode($data);

        $ch = curl_init('http://116.203.92.59/api/send_message');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length' => strlen($data_string)
            )
        );
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
