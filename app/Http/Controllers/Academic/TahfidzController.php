<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Student;
use App\Models\TargetManzil;
use App\Models\TargetSabqi;
use App\Models\TargetZiyadah;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TahfidzController extends Controller
{
    private $tahfidz = 'backend.academic.tahfidz.';
    private $icon = 'fa-solid fa-book-quran';

    public function index()
    {
        $students = Student::where('id_parent', Auth::user()->parent->id)->get();

        $studentIds = $students->pluck('id');

        $targetZiyadah = TargetZiyadah::all();
        $targetManzil = TargetManzil::all();
        $targetSabqi = TargetSabqi::all();

        $targets = $targetZiyadah->merge($targetManzil)->merge($targetSabqi)->whereIn('id_santri', $studentIds);

        return view($this->tahfidz . 'index', [
            'title' => 'Laporan Penilaian Tahfidz',
            'icon' => 'bx bxs-book-open',
            'icon' => $this->icon,
            'targets' => $targets,
            'count' => $targets->count()
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? '';
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $studentIds = Student::where('id_parent', Auth::user()->parent->id)
            ->pluck('id');

        $targetZiyadah = TargetZiyadah::on('mysql_second')
            ->select('target_ziyadah.*')
            ->addSelect(DB::raw('(
        SELECT SUM(capaian_target)
        FROM proses_absensi_kbm_tahfidz
        WHERE id_santri = target_ziyadah.id_santri
    ) as total_capaian_target'))
            ->with('proses')
            ->whereIn('id_santri', $studentIds)
            ->get();

        $targetManzil = TargetManzil::on('mysql_second')
            ->select('target_murojaah_manzil.*')
            ->addSelect(DB::raw('(
        SELECT SUM(capaian_target)
        FROM proses_absensi_kbm_tahfidz
        WHERE id_santri = target_murojaah_manzil.id_santri
    ) as total_capaian_target'))
            ->with('proses')
            ->whereIn('id_santri', $studentIds)
            ->get();

        $targetSabqi = TargetSabqi::on('mysql_second')
            ->select('target_murojaah.*')
            ->addSelect(DB::raw('(
        SELECT SUM(capaian_target)
        FROM proses_absensi_kbm_tahfidz
        WHERE id_santri = target_murojaah.id_santri
    ) as total_capaian_target'))
            ->with('proses')
            ->whereIn('id_santri', $studentIds)
            ->get();

        $targets = $targetZiyadah
            ->merge($targetManzil)
            ->merge($targetSabqi);

        if (!empty($search)) {
            $targets = $targets->filter(function ($item) use ($search) {
                return stripos($item->nama_santri, $search) !== false
                    || stripos($item->nama_kaldik, $search) !== false
                    || stripos($item->jenis_kaldik, $search) !== false
                    || stripos($item->jenis_target, $search) !== false
                    || stripos($item->periode_kaldik, $search) !== false;
            });
        }

        $recordsTotal = $targetZiyadah->count() + $targetManzil->count() + $targetSabqi->count();
        $recordsFiltered = $targets->count();

        $data = $targets->slice($start, $limit)->values();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    public function printTahfidz($id, $jenis_kaldik)
    {
        $studentIds = Student::where('id_parent', Auth::user()->parent->id)
            ->pluck('id');

        if ($jenis_kaldik === 'Ziyadah') {
            $printData = TargetZiyadah::on('mysql_second')
                ->join('proses_absensi_kbm_tahfidz', function ($join) {
                    $join->on('target_ziyadah.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri');
                })
                ->join('active_target_ziyadah', function ($join) {
                    $join->on('target_ziyadah.id', '=', 'active_target_ziyadah.id_target_ziyadah')
                        ->on('active_target_ziyadah.hari', '=', 'proses_absensi_kbm_tahfidz.pertemuan');
                })
                ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
                ->where('target_ziyadah.id', $id)
                ->whereIn('target_ziyadah.id_santri', $studentIds)
                ->where('proses_absensi_kbm_tahfidz.id_target_ziyadah', $id)
                ->select('target_ziyadah.*', 'proses_absensi_kbm_tahfidz.*', 'active_target_ziyadah.target_ziyadah', 'active_target_ziyadah.target_baris', 'active_absensi_kbm_tahfidz.kehadiran', DB::raw('(SELECT SUM(capaian_target) FROM proses_absensi_kbm_tahfidz WHERE id_santri = target_ziyadah.id_santri) as total_capaian_target'))
                ->distinct()
                ->get();

            if (!$printData->isEmpty()) {
                $idPengampu = User::where('id', $printData[0]->id_pengampu)->value('name');
                $presences = $printData->pluck('kehadiran');

                $totalAttendance = $presences->countBy();

                $attendance = [
                    'hadir' => $totalAttendance->get('hadir', 0),
                    'sakit' => $totalAttendance->get('sakit', 0),
                    'izin' => $totalAttendance->get('izin', 0),
                    'alpha' => $totalAttendance->get('alpha', 0),
                ];

                $pdf = FacadePdf::loadView($this->tahfidz . 'print', [
                    'title' => 'Laporan Penilaian Tahfidz - ' . $jenis_kaldik,
                    'icon' => asset('images/favicon/favicon.ico'),
                    'data' => $printData,
                    'jenis_kaldik' => $jenis_kaldik,
                    'idPengampu' => $idPengampu,
                    'attendance' => $attendance
                ]);

                $option = new \Dompdf\Options();
                $option->set('isRemoteEnabled', true);
                $pdf->setOptions([$option]);

                return $pdf->stream("tahfidz-{$jenis_kaldik}-{$id}.pdf");
            } else {
                return redirect()->back()->with('error', 'Pertemuan belum diisi.');
            }
        } elseif ($jenis_kaldik === 'Murojaah Sabqi') {
            $printData = TargetSabqi::on('mysql_second')
                ->join('proses_absensi_kbm_tahfidz', function ($join) {
                    $join->on('target_murojaah.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri');
                })
                ->join('active_target_murojaah', function ($join) {
                    $join->on('target_murojaah.id', '=', 'active_target_murojaah.id_target_murojaah')
                        ->on('active_target_murojaah.hari', '=', 'proses_absensi_kbm_tahfidz.pertemuan');
                })
                ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
                ->where('target_murojaah.id', $id)
                ->whereIn('target_murojaah.id_santri', $studentIds)
                ->where('proses_absensi_kbm_tahfidz.id_target_murojaah', $id)
                ->select('target_murojaah.*', 'proses_absensi_kbm_tahfidz.*', 'active_target_murojaah.target_murojaah', 'active_target_murojaah.target_baris', 'active_absensi_kbm_tahfidz.kehadiran', DB::raw('(SELECT SUM(capaian_target) FROM proses_absensi_kbm_tahfidz WHERE id_santri = target_murojaah.id_santri) as total_capaian_target'))
                ->distinct()
                ->get();


            if (!$printData->isEmpty()) {
                $idPengampu = User::where('id', $printData[0]->id_pengampu)->value('name');
                $presences = $printData->pluck('kehadiran');

                $totalAttendance = $presences->countBy();

                $attendance = [
                    'hadir' => $totalAttendance->get('hadir', 0),
                    'sakit' => $totalAttendance->get('sakit', 0),
                    'izin' => $totalAttendance->get('izin', 0),
                    'alpha' => $totalAttendance->get('alpha', 0),
                ];

                $pdf = FacadePdf::loadView($this->tahfidz . 'print', [
                    'title' => 'Laporan Penilaian Tahfidz - ' . $jenis_kaldik,
                    'icon' => asset('images/favicon/favicon.ico'),
                    'data' => $printData,
                    'jenis_kaldik' => $jenis_kaldik,
                    'idPengampu' => $idPengampu,
                    'attendance' => $attendance
                ]);

                $option = new \Dompdf\Options();
                $option->set('isRemoteEnabled', true);
                $pdf->setOptions([$option]);

                return $pdf->stream("tahfidz-{$jenis_kaldik}-{$id}.pdf");
            } else {
                return redirect()->back()->with('error', 'Pertemuan belum diisi.');
            }
        } elseif ($jenis_kaldik === 'Murojaah Manzil') {
            $printData = TargetManzil::on('mysql_second')
                ->join('proses_absensi_kbm_tahfidz', function ($join) {
                    $join->on('target_murojaah_manzil.id_santri', '=', 'proses_absensi_kbm_tahfidz.id_santri');
                })
                ->join('active_target_murojaah_manzil', function ($join) {
                    $join->on('target_murojaah_manzil.id', '=', 'active_target_murojaah_manzil.id_target_murojaah')
                        ->on('active_target_murojaah_manzil.hari', '=', 'proses_absensi_kbm_tahfidz.pertemuan');
                })
                ->join('active_absensi_kbm_tahfidz', 'proses_absensi_kbm_tahfidz.id_absensi', '=', 'active_absensi_kbm_tahfidz.id_absensi')
                ->where('target_murojaah_manzil.id', $id)
                ->whereIn('target_murojaah_manzil.id_santri', $studentIds)
                ->where('proses_absensi_kbm_tahfidz.id_target_murojaah', $id)
                ->select('target_murojaah_manzil.*', 'proses_absensi_kbm_tahfidz.*', 'active_target_murojaah_manzil.target_murojaah', 'active_target_murojaah_manzil.target_halaman', 'active_absensi_kbm_tahfidz.kehadiran', DB::raw('(SELECT SUM(capaian_target) FROM proses_absensi_kbm_tahfidz WHERE id_santri = target_murojaah_manzil.id_santri) as total_capaian_target'))
                ->distinct()
                ->get();


            if (!$printData->isEmpty()) {
                $idPengampu = User::where('id', $printData[0]->id_pengampu)->value('name');
                $presences = $printData->pluck('kehadiran');

                $totalAttendance = $presences->countBy();

                $attendance = [
                    'hadir' => $totalAttendance->get('hadir', 0),
                    'sakit' => $totalAttendance->get('sakit', 0),
                    'izin' => $totalAttendance->get('izin', 0),
                    'alpha' => $totalAttendance->get('alpha', 0),
                ];

                $pdf = FacadePdf::loadView($this->tahfidz . 'print', [
                    'title' => 'Laporan Penilaian Tahfidz - ' . $jenis_kaldik,
                    'icon' => asset('images/favicon/favicon.ico'),
                    'data' => $printData,
                    'jenis_kaldik' => $jenis_kaldik,
                    'idPengampu' => $idPengampu,
                    'attendance' => $attendance
                ]);

                $option = new \Dompdf\Options();
                $option->set('isRemoteEnabled', true);
                $pdf->setOptions([$option]);

                return $pdf->stream("tahfidz-{$jenis_kaldik}-{$id}.pdf");
            } else {
                return redirect()->back()->with('error', 'Pertemuan belum diisi.');
            }
        } else {
            abort(404, 'Jenis Kaldik tidak ditemukan.');
        }

        // dd($printData);


    }
}
