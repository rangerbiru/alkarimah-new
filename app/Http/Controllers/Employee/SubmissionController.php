<?php

namespace App\Http\Controllers\Employee;

use App\Helpers\Whatsapp;
use App\Http\Controllers\Controller;
use App\Models\ActualSubmissionItems;
use App\Models\AllowedSubmissionEmployee;
use App\Models\Employee;
use App\Models\Items;
use App\Models\Submissions;
use App\Models\UnitMaster;
use App\Notifications\EmployeeNotification;
use App\Notifications\SubmissionStatusUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmissionController extends Controller
{
    private $title = 'label.submission_item';
    private $path = 'backend.employee.submission.';
    private $icon = 'bx bxs-package';

    public function index(Request $request)
    {
        $currentEmployeeId = Auth::user()?->employee?->id;

        $allowedRecord = AllowedSubmissionEmployee::where('employee_id', $currentEmployeeId)->first();
        $canCreate = false;

        $isWakil = AllowedSubmissionEmployee::where('employee_id', $currentEmployeeId)->where('position', 'like', '%wadir%')->exists();
        $isMudir = AllowedSubmissionEmployee::where('employee_id', $currentEmployeeId)->where('position', 'like', '%mudir%')->exists();
        $isBendahara = AllowedSubmissionEmployee::where('employee_id', $currentEmployeeId)->where('position', 'like', '%bendahara%')->exists();
        $isLogistik = AllowedSubmissionEmployee::where('employee_id', $currentEmployeeId)->where('position', 'like', '%logistik%')->exists();


        if ($allowedRecord) {
            $canCreate = in_array($allowedRecord->position, ['atk', 'it', 'sarpras']);
        }

        $search = $request->input('search');

        $bulanIndo = [
            'januari' => 1,
            'februari' => 2,
            'maret' => 3,
            'april' => 4,
            'mei' => 5,
            'juni' => 6,
            'juli' => 7,
            'agustus' => 8,
            'september' => 9,
            'oktober' => 10,
            'november' => 11,
            'desember' => 12,
        ];

        $query = Submissions::with([
            'employee' => fn($q) => $q->select('id', 'name', 'task_main'),
            'items',
            'submissionItems',
            'location.unit:id,unit',
            'actualSubmissionItems',
            'employee.member.attendanceGroup:id,group_name',
        ])
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc');

        if ($isMudir) {
            $query->where('approve1', 'approved')
                ->where('approve2', 'pending')
                ->orWhere('approve2', 'approved')
                ->orWhere('approve2', 'rejected');
        }

        if ($isBendahara) {
            $query->where('approve2', 'approved')
                ->where('last_approve', 'pending')
                ->orWhere('last_approve', 'approved')
                ->orWhere('last_approve', 'rejected');
        }

        if ($isLogistik) {
            $query->where('activity_type', 'item')
                ->where('approve1', 'approved')
                ->where('approve2', 'approved')
                ->where('last_approve', 'approved');
        }

        if ($search) {
            $searchLower = strtolower(trim($search));
            $bulanAngka = $bulanIndo[$searchLower] ?? null;

            $query->where(function ($q) use ($search, $bulanAngka) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });

                if ($bulanAngka) {
                    $q->orWhereMonth('created_at', $bulanAngka);
                }
            });
        }

        $submissions = $query->get();


        // Group by date
        $grouped = $submissions->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });

        // Hitung total per tanggal
        $amount = [];
        foreach ($grouped as $date => $subs) {
            $total = 0;
            foreach ($subs as $submission) {
                foreach ($submission->items as $item) {
                    $quantity = $item->pivot->quantity ?? 0;
                    $price = $item->price ?? 0;
                    $total += $quantity * $price;
                }
            }
            $amount[$date] = $total;
        }

        if (!$isWakil && !$isMudir && !$isBendahara && !$isLogistik) {
            DB::table('notifications')
                ->where('notifiable_id', $currentEmployeeId)
                ->update(['read_at' => now()]);
        }


        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'groupedSubmissions' => $grouped,
            'submissionAmounts' => $amount,
            'canCreate' => $canCreate,
            'isWakil' => $isWakil,
            'isMudir' => $isMudir,
            'isBendahara' => $isBendahara,
            'isLogistik' => $isLogistik,
        ]);
    }

    public function create()
    {
        $idEmployee = Auth::user()->employee->id;

        $items = Items::all();

        $location = UnitMaster::pluck('unit', 'id');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'idEmployee' => $idEmployee,
            'items' => $items,
            'location' => $location
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employee,id',
            'name_activity' => 'required|string|max:255',
            'location' => 'required|array|min:1',
            'activity_type' => 'required|in:service,item,fund',
            'description' => 'nullable|string',
            'selected_items' => 'required',
        ], [
            'name_activity.required' => 'Nama kegiatan harus diisi.',
            'location.required' => 'Lokasi harus dipilih.',
            'activity_type.required' => 'Jenis kegiatan harus dipilih.',
        ]);

        $selectedItems = json_decode($request->input('selected_items'), true);

        if (empty($selectedItems) || !is_array($selectedItems)) {
            return back()->withErrors(['items' => 'Minimal pilih satu barang.'])->withInput();
        }

        foreach ($selectedItems as $item) {
            if (!isset($item['id']) || !isset($item['quantity'])) {
                return back()->withErrors(['items' => 'Data barang tidak valid.'])->withInput();
            }

            $exists = Items::where('id', $item['id'])->exists();
            if (!$exists) {
                return back()->withErrors(['items' => 'Salah satu barang tidak ditemukan.'])->withInput();
            }

            if ($item['quantity'] < 1) {
                return back()->withErrors(['items' => 'Jumlah barang minimal 1.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $submission = Submissions::with(['employee' => fn($q) => $q->select('id', 'name', 'task_main')])->create([
                'activity_name' => $request->name_activity,
                'activity_type' => $request->activity_type,
                'description' => $request->description,
                'employee_id' => $request->employee_id,
                'status' => 'pending',
            ]);

            $pivotData = [];
            foreach ($selectedItems as $item) {

                DB::table('submission_items')->insert([
                    'submissions_id' => $submission->id,
                    'items_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'note' => $item['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($request->location as $loc) {
                DB::table('submission_locations')->insert([
                    'submissions_id' => $submission->id,
                    'unit_id' => $loc,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('submission_items')->insert($pivotData);

            DB::commit();

            $this->sendWhatsAppToWadir($submission);

            $this->sendSubmissionNotification($submission);

            return redirect()->route('employee.submission.index')
                ->with('success', 'Pengajuan berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error saat menyimpan pengajuan: ' . $e->getMessage());
            return back()->withErrors(['system' => 'Terjadi kesalahan sistem. Silakan coba lagi.'])->withInput();
        }
    }

    private function sendSubmissionNotification($submission)
    {
        try {
            $submitter = Employee::find($submission->employee_id);
            $submitterName = $submitter?->name ?? 'Pegawai';

            $wakilRecords = AllowedSubmissionEmployee::with('employee')
                ->where('position', 'like', '%wadir%')
                ->get();

            foreach ($wakilRecords as $record) {
                $targetEmployee = $record->employee;

                if (!$targetEmployee) {
                    continue;
                }

                Log::info('Mengirim ke: ' . $targetEmployee->email);

                $url = route('employee.submission.index');

                $targetEmployee->notify(new EmployeeNotification(
                    'Pengajuan Baru',
                    "Ada pengajuan {$submission->activity_type} baru dari {$submitterName}",
                    $url,
                    'submission',
                    'pending',
                    $submission->id,
                ));
            }
        } catch (\Exception $e) {
            Log::error('Error kirim notifikasi: ' . $e->getMessage());
        }
    }

    public function destroy(Submissions $submission): JsonResponse
    {
        DB::beginTransaction();
        try {
            DB::table('submission_locations')->where('submissions_id', $submission->id)->delete();
            DB::table('submission_items')->where('submissions_id', $submission->id)->delete();
            DB::table('notifications')
                ->where('data->notifId', $submission->id)
                ->where('data->type', 'submission')
                ->where('data->status', 'pending')->delete();

            $submission->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error saat menghapus pengajuan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
            ], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'level' => 'required|in:approve1,approve2,last_approve,status',
            'actual_items' => 'nullable|array',
            'actual_items.*.items_id' => 'required_with:actual_items|exists:items,id',
            'actual_items.*.price' => 'required_with:actual_items|numeric|min:0',
            'actual_items.*.quantity' => 'required_with:actual_items|integer|min:1',
        ]);

        $employeeId = Auth::user()?->employee?->id;
        if (!$employeeId) {
            return response()->json(['message' => 'Pengguna tidak memiliki data pegawai.'], 403);
        }

        $submission = Submissions::with('employee')->findOrFail($id);

        // Cek otorisasi berdasarkan posisi
        $isWakil = AllowedSubmissionEmployee::where('employee_id', $employeeId)->where('position', 'like', '%wadir%')->exists();
        $isMudir = AllowedSubmissionEmployee::where('employee_id', $employeeId)->where('position', 'like', '%mudir%')->exists();
        $isBendahara = AllowedSubmissionEmployee::where('employee_id', $employeeId)->where('position', 'like', '%bendahara%')->exists();
        $isLogistik = AllowedSubmissionEmployee::where('employee_id', $employeeId)->where('position', 'like', '%logistik%')->exists();

        $allowed = match ($request->level) {
            'approve1' => $isWakil,
            'approve2' => $isMudir,
            'last_approve' => $isBendahara,
            'status' => $isLogistik,
            default => false,
        };

        if (!$allowed) {
            return response()->json(['message' => 'Anda tidak berwenang menyetujui tahap ini.'], 403);
        }

        if ($request->level === 'approve1' && $submission->approve1 !== 'pending') {
            return response()->json(['message' => 'Tahap approve1 tidak dalam status pending.'], 400);
        }

        if ($request->level === 'approve2') {
            if ($submission->approve1 !== 'approved') {
                return response()->json(['message' => 'Approve1 harus disetujui terlebih dahulu.'], 400);
            }
            if ($submission->approve2 !== 'pending') {
                return response()->json(['message' => 'Tahap approve2 tidak dalam status pending.'], 400);
            }
        }

        if ($request->level === 'last_approve') {
            if ($submission->approve2 !== 'approved') {
                return response()->json(['message' => 'Approve2 harus disetujui terlebih dahulu.'], 400);
            }
            if ($submission->last_approve !== 'pending') {
                return response()->json(['message' => 'Tahap last_approve tidak dalam status pending.'], 400);
            }

            if (in_array($submission->activity_type, ['fund', 'service'])) {
                $submission->status = 'approved';
                $submission->save();
            }
        }

        if ($request->level === 'status') {
            return DB::transaction(function () use ($request, $submission, $employeeId) {
                if ($request->has('actual_items')) {
                    foreach ($request->actual_items as $itemData) {
                        ActualSubmissionItems::updateOrCreate(
                            [
                                'submissions_id' => $submission->id,
                                'items_id' => $itemData['items_id'],
                            ],
                            [
                                'price' => $itemData['price'],
                                'quantity' => $itemData['quantity'],
                            ]
                        );
                    }
                }

                // Update status
                if ($submission->status === 'pending') {
                    $submission->status = 'process';
                } else {
                    $submission->status = 'approved';
                }
                $submission->save();

                $applicantUser = Employee::find($submission->employee_id);

                if ($submission->status === 'process') {
                    if ($applicantUser) {
                        $applicantUser->notify(new SubmissionStatusUpdated(
                            $submission->id,
                            'Pengajuan Diproses',
                            "Pengajuan Anda sedang diproses oleh tim logistik.",
                            'process',
                            'submission'
                        ));

                        $this->sendWhatsAppProcessToSubmitter($submission);
                    }
                }

                if ($submission->status === 'approved') {
                    if ($applicantUser) {
                        $applicantUser->notify(new SubmissionStatusUpdated(
                            $submission->id,
                            'Pengajuan Disetujui',
                            "Pengajuan Anda telah disetujui dan siap diambil.",
                            'approved',
                            'submission'
                        ));

                        $this->sendWhatsAppItemReadyToSubmitter($submission);
                    }
                }

                return response()->json(['message' => 'Data logistik berhasil disimpan.']);
            });
        }

        $submission->{$request->level} = 'approved';
        $submission->save();

        DB::table('notifications')
            ->where('data->notifId', (string) $submission->id)
            ->update([
                'data->status' => 'approved',
                'read_at' => now(),
            ]);

        if ($request->level === 'approve1') {
            $this->sendNotificationToRole(
                $submission,
                'mudir',
                'Menunggu Persetujuan',
                "Pengajuan {$submission->activity_type} menunggu persetujuan Anda."
            );
            $this->sendWhatsAppToRole($submission, 'mudir');
        } elseif ($request->level === 'approve2') {
            $this->sendNotificationToRole(
                $submission,
                'bendahara',
                'Menunggu Verifikasi Anggaran',
                "Pengajuan {$submission->activity_type} menunggu verifikasi anggaran Anda."
            );

            $this->sendWhatsAppToRole($submission, 'bendahara');
        } elseif ($request->level === 'last_approve') {
            if ($submission->activity_type === 'item') {
                $this->sendNotificationToRole(
                    $submission,
                    'logistik',
                    'Menunggu Input Data',
                    "Pengajuan barang menunggu input data realisasi oleh Anda."
                );

                $this->sendWhatsAppToRole($submission, 'logistik');
            } else {
                $applicantUser = Employee::find($submission->employee_id);
                if ($applicantUser) {
                    $applicantUser->notify(new SubmissionStatusUpdated(
                        $submission->id,
                        'Pengajuan Disetujui',
                        "Pengajuan {$submission->activity_type} Anda telah disetujui. Silahkan hubungi Bendahara untuk proses selanjutnya.",
                        'approved',
                        'submission'
                    ));

                    $this->sendWhatsAppFundServiceReadyToSubmitter($submission);
                }
            }
        }

        return response()->json(['message' => 'Pengajuan berhasil disetujui.']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'level' => 'required|in:approve1,approve2,last_approve',
            'reject_reason' => 'required|string|max:1000',
        ]);

        $userEmployeeId = Auth::user()->employee->id;
        $submission = Submissions::findOrFail($id);

        // Cek otorisasi (sama seperti sebelumnya)
        $isWakil = AllowedSubmissionEmployee::where('employee_id', $userEmployeeId)
            ->where('position', 'like', '%wadir%')->exists();
        $isMudir = AllowedSubmissionEmployee::where('employee_id', $userEmployeeId)
            ->where('position', 'like', '%mudir%')->exists();
        $isBendahara = AllowedSubmissionEmployee::where('employee_id', $userEmployeeId)
            ->where('position', 'like', '%bendahara%')->exists();

        $allowed = match ($request->level) {
            'approve1' => $isWakil,
            'approve2' => $isMudir,
            'last_approve' => $isBendahara,
            default => false
        };

        if (!$allowed) {
            return response()->json(['message' => 'Anda tidak berwenang menolak tahap ini.'], 403);
        }

        if ($submission->{$request->level} !== 'pending') {
            return response()->json(['message' => 'Tahap ini tidak dalam status pending.'], 400);
        }

        if ($request->level === 'last_approve' && $submission->activity_type == 'fund') {
            $submission->status = 'rejected';
            $submission->reject_reason = $request->reject_reason;
            $submission->rejected_by = $userEmployeeId;
            $submission->save();
            return response()->json(['message' => 'Pengajuan berhasil ditolak.']);
        }

        // Simpan penolakan hanya pada level yang sesuai
        // $submission->approve1 = 'rejected';
        // $submission->approve2 = 'rejected';
        // $submission->last_approve = 'rejected';
        // $submission->status = 'rejected';

        $submission->{$request->level} = 'rejected';
        $submission->status = 'rejected';

        DB::table('notifications')
            ->where('data->notifId', (string) $submission->id)
            ->update([
                'data->status' => 'rejected',
                'read_at' => now(),
            ]);

        $submitter = Employee::find($submission->employee_id);

        if ($submitter) {
            $submitter->notify(new EmployeeNotification(
                'Pengajuan Ditolak',
                "Alasan: {$request->reject_reason}",
                route('employee.submission.index'),
                'submission',
                'rejected',
                (string) $submission->id
            ));
        }

        // Simpan alasan & penolak (jika belum ada)
        if (!$submission->reject_reason) {
            $submission->reject_reason = $request->reject_reason;
            $submission->rejected_by = $userEmployeeId;
        }


        $submission->save();

        $rejectedBy = Employee::find($submission->rejected_by);
        $rejectedName = $rejectedBy?->name ?? 'Admin';
        $rejectedTask = $rejectedBy?->task_main ?? 'Umum';

        $this->sendWhatsAppRejectToSubmitter($submission, $request->reject_reason, $rejectedName, $rejectedTask);

        return response()->json(['message' => 'Pengajuan berhasil ditolak.']);
    }

    private function sendNotificationToRole($submission, string $roleKeyword, string $title, string $message)
    {
        try {
            $records = AllowedSubmissionEmployee::with('employee')
                ->where('position', 'like', "%{$roleKeyword}%")
                ->get();

            foreach ($records as $record) {
                $employee = $record->employee;
                if (!$employee) continue;

                $employee->notify(new EmployeeNotification(
                    $title,
                    $message,
                    route('employee.submission.index'),
                    'submission',
                    'pending',
                    (string) $submission->id
                ));
            }
        } catch (\Exception $e) {
            Log::error("Error kirim notifikasi ke {$roleKeyword}: " . $e->getMessage());
        }
    }

    private function sendWhatsAppToWadir($submission)
    {
        try {
            $records = AllowedSubmissionEmployee::with('employee')
                ->where('position', 'like', '%wadir%')
                ->get();

            $unitName = $submission->employee?->task_main;
            $submitterName = $submission->employee?->name ?? 'Pegawai';
            $message = $this->buildNewSubmissionMessage($submission, $unitName, $submitterName);

            foreach ($records as $record) {
                $employee = $record->employee;
                if (!$employee || empty($employee->phone)) continue;

                Whatsapp::send($employee->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Error kirim WhatsApp ke Wadir: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppToRole($submission, string $roleKeyword)
    {
        try {
            $records = AllowedSubmissionEmployee::with('employee')
                ->where('position', 'like', "%{$roleKeyword}%")
                ->get();

            $unitName = $submission->location->first()?->unit ?? 'Umum';

            foreach ($records as $record) {
                $employee = $record->employee;
                if (!$employee || empty($employee->phone)) continue;

                $message = match ($roleKeyword) {
                    'mudir' => $this->buildWadirApprovedMessage($submission, $unitName),
                    'bendahara' => $this->buildMudirApprovedMessage($submission, $unitName),
                    'logistik' => $this->buildBendaharaApprovedMessage($submission, $unitName),
                    default => null
                };

                if ($message) {
                    Whatsapp::send($employee->phone, $message);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error kirim WhatsApp ke {$roleKeyword}: " . $e->getMessage());
        }
    }

    private function sendWhatsAppProcessToSubmitter($submission)
    {
        try {
            $submitter = Employee::find($submission->employee_id);
            if (!$submitter || empty($submitter->phone)) {
                return;
            }

            $unitNames = $submission->employee?->task_main;
            $message = $this->buildProcessMessage($submission, $unitNames);
            Whatsapp::send($submitter->phone, $message);
        } catch (\Exception $e) {
            Log::error('Error kirim WhatsApp process: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppItemReadyToSubmitter($submission)
    {
        try {
            $submitter = Employee::find($submission->employee_id);
            if (!$submitter || empty($submitter->phone)) {
                return;
            }

            $unitNames = $submission->employee?->task_main;
            $message = $this->buildItemReadyMessage($submission, $unitNames);
            Whatsapp::send($submitter->phone, $message);
        } catch (\Exception $e) {
            Log::error('Error kirim WhatsApp barang siap: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppFundServiceReadyToSubmitter($submission)
    {
        try {
            $submitter = Employee::find($submission->employee_id);
            if (!$submitter || empty($submitter->phone)) {
                return;
            }

            $unitNames = $submission->employee?->task_main;
            $message = $this->buildFundServiceReadyMessage($submission, $unitNames);
            Whatsapp::send($submitter->phone, $message);
        } catch (\Exception $e) {
            Log::error('Error kirim WhatsApp fund/service ready: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppRejectToSubmitter($submission, string $reason, string $rejectedName, string $rejectedTask)
    {
        try {
            $submitter = Employee::find($submission->employee_id);
            if (!$submitter || empty($submitter->phone)) {
                return;
            }

            $message = $this->buildRejectedMessage($submission, $reason, $rejectedName, $rejectedTask);
            Whatsapp::send($submitter->phone, $message);
        } catch (\Exception $e) {
            Log::error('Error kirim WhatsApp reject: ' . $e->getMessage());
        }
    }

    private function generateSubmissionCode($submission): string
    {
        $submitterName = $submission->employee?->name ?? 'PEG';
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $submitterName), 0, 3));

        // Pastikan minimal 3 karakter
        $prefix = str_pad($prefix, 3, 'X', STR_PAD_RIGHT);

        return "{$prefix}-{$submission->id}";
    }

    private function buildNewSubmissionMessage($submission, string $unitName, string $submitterName): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Pengadaan *#{$submissionCode} ({$submission->activity_type})*\n";
        $message .= "Diajukan oleh: *{$submitterName}*\n";
        $message .= "Mohon persetujuan Wakil Mudir.\n";
        $message .= "Silahkan buka menu pengadaan\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }

    private function buildWadirApprovedMessage($submission, string $unitName): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Pengadaan *#{$submissionCode} ({$submission->activity_type})*\n";
        $message .= "Sudah disetujui Wakil Mudir. Menunggu persetujuan Mudir.\n";
        $message .= "Silahkan buka menu pengadaan\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }

    /**
     * Build pesan approval Mudir → Bendahara
     */
    private function buildMudirApprovedMessage($submission, string $unitName): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);
        $nextStep = in_array($submission->activity_type, ['fund', 'service'])
            ? "Mohon verifikasi dan proses pencairan/koordinasi."
            : "Mohon proses/approve Bendahara.";

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Pengadaan *#{$submissionCode} ({$submission->activity_type})*\n";
        $message .= "Telah disetujui Mudir. {$nextStep}\n";
        $message .= "Silahkan buka menu pengadaan\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }

    /**
     * Build pesan approval Bendahara → Logistik (khusus item)
     */
    private function buildBendaharaApprovedMessage($submission, string $unitName): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Pengadaan *#{$submissionCode} ({$submission->activity_type})*\n";
        $message .= "Telah diverifikasi. Silakan menuju Bendahara untuk proses belanja.\n";
        $message .= "Silahkan buka menu pengadaan\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }

    private function buildProcessMessage($submission, string $unitNames): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Pengadaan *#{$submissionCode} ({$submission->activity_type})*\n";
        $message .= "Pengajuan Anda sedang diproses oleh tim Logistik.\n\n";
        $message .= "Kami akan menginformasikan kembali setelah barang siap diambil.\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }

    private function buildItemReadyMessage($submission, string $unitNames): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Alhamdulillah, Pengadaan *#{$submissionCode} ({$submission->activity_type})*\n";
        $message .= "Barang telah dibelanjakan dan siap diambil!\n\n";
        $message .= "Silahkan menghubungi bagian Logistik untuk pengambilan.\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }

    private function buildFundServiceReadyMessage($submission, string $unitNames): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);
        $typeName = $submission->activity_type === 'fund' ? 'Dana' : 'Jasa';

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Alhamdulillah, Pengadaan *#{$submissionCode} ({$submission->activity_type})*\n";
        $message .= "{$typeName} telah disetujui dan siap diproses!\n\n";
        $message .= "Silahkan menghubungi bagian *Bendahara* untuk proses pencairan/koordinasi selanjutnya.\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }

    private function buildRejectedMessage($submission, string $reason, string $rejectedBy, string $rejectedTask): string
    {
        $submissionCode = $this->generateSubmissionCode($submission);

        $message = "Bismillah\n";
        $message .= "Assalamu'alaikum Warohmatullah Wabarokaatuh\n\n";
        $message .= "Mohon maaf, Pengadaan *#{$submissionCode} ({$submission->activity_type})*";
        $message .= "Ditolak oleh *{$rejectedBy} ({$rejectedTask})*\n\n";
        $message .= "*Alasan:*\n{$reason}\n\n";
        $message .= "Silahkan perbaiki dan ajukan kembali jika diperlukan.\n\n";
        $message .= "Jazakumullahu khairan";

        return $message;
    }
}
