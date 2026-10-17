<?php

namespace App\Http\Controllers\Hr;

use App\Helpers\Phone;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Parents;
use App\Models\Scopes\BranchScope;
use App\Models\Student;
use App\Services\AccountLinkException;
use App\Services\AccountLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Endpoint admin untuk menghubungkan data orang tua ke akun pegawai (form pegawai dan form orang tua).
 */
class AccountLinkController extends Controller
{
    public function __construct(private AccountLinker $linker) {}

    /** Cari pegawai (nama / NIP / nomor HP) untuk form orang tua; pegawai dengan nomor sama disarankan. */
    public function employees(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('term'));
        $parent = $this->decrypt($request->query('parent'), Parents::class);
        $parentTen = $parent ? Phone::lastTen($parent->phone) : null;

        $query = Employee::withoutGlobalScopes()->where('status', 1)->with('user:id,phone');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%");
            });
        } elseif ($parentTen === null) {
            return response()->json(['data' => []]);
        }

        $rows = $query->orderBy('name')->limit(200)->get()->map(function (Employee $e) use ($parentTen) {
            return [
                'id' => $e->encrypted_id,
                'label' => trim(($e->nip ? $e->nip.' - ' : '').$e->name.' ('.$e->phone.')'),
                'suggested' => $parentTen !== null && Phone::lastTen($e->user?->phone ?? $e->phone) === $parentTen,
            ];
        });

        if ($term === '') {
            $rows = $rows->where('suggested', true);
        }

        return response()->json(['data' => $rows->sortByDesc('suggested')->take(20)->values()]);
    }

    /** Datatable santri untuk modal di form pegawai (hanya santri yang punya data orang tua). */
    public function students(Request $request): JsonResponse
    {
        $search = $request->input('search')['value'] ?? '';
        $query = Student::withoutGlobalScopes()
            ->from('student as s')
            ->leftJoin('parent as p', function ($join) {
                $join->on('p.id', '=', 's.id_parent')->whereNull('p.deleted_at');
            })
            ->leftJoin('class as c', 'c.id', '=', 's.id_class')
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', 'p.id_user')->whereNull('u.deleted_at');
            })
            ->whereNull('s.deleted_at');

        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('s.name', 'like', "%{$search}%")->orWhere('s.nis', 'like', "%{$search}%")
                    ->orWhere('p.name', 'like', "%{$search}%")->orWhere('p.phone', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();
        $rows = $query->orderBy('s.name')->offset((int) $request->input('start', 0))->limit(min((int) $request->input('length', 10), 100))
            ->get([
                's.id as id', 's.id_parent as student_parent', 's.name as name', 's.nis as nis', 'c.name as class_name',
                'p.id as parent_id', 'p.name as parent_name', 'p.phone as parent_phone',
                'u.id as account_id', 'u.role as account_role',
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(fn ($r) => [
                'encrypted_id' => Crypt::encrypt($r->id),
                'name' => $r->name,
                'nis' => $r->nis,
                'class' => $r->class_name ?? '-',
                'parent_name' => $r->parent_name,
                'parent_phone' => $r->parent_phone,
                // Santri tanpa data orang tua aktif (id_parent kosong / data orang tua tidak ada atau dihapus) = "none":
                // bisa dipilih, data orang tua dibuat dari data pegawai
                'parent_key' => $r->parent_id ? Crypt::encrypt($r->parent_id) : 'none',
                // Orang tua santri ini sudah punya akun (akun orang tua biasa atau sudah terhubung ke pegawai):
                // tidak dipilih dari form pegawai; penggabungan akun yang ada lewat form edit orang tua.
                'parent_has_account' => $r->account_id !== null,
            ]),
        ]);
    }

    /**
     * Seluruh anak dari data orang tua milik santri ini. Yang dihubungkan ke akun pegawai adalah data
     * orang tuanya, sehingga semua anaknya ikut.
     */
    public function children(Request $request): JsonResponse
    {
        $student = Student::withoutGlobalScope(BranchScope::class)->find($this->decryptId($request->input('student')));

        if (! $student) {
            return response()->json(['status' => false, 'message' => __('message.account_link_parent_required')], 422);
        }

        // Santri tanpa data orang tua aktif dipilih satu per satu
        $activeParent = $this->linker->activeParentIds([$student->id])[$student->id] ?? null;
        $children = $activeParent
            ? Student::withoutGlobalScope(BranchScope::class)->where('id_parent', $activeParent)->orderBy('name')->get(['id', 'name', 'nis'])
            : collect([$student]);

        return response()->json([
            'status' => true,
            'data' => [
                'parent_key' => $activeParent ? Crypt::encrypt($activeParent) : 'none',
                'children' => $children->map(fn ($c) => ['id' => Crypt::encrypt($c->id), 'name' => $c->name, 'nis' => $c->nis])->values(),
            ],
        ]);
    }

    /**
     * Preview penggabungan. Parent ditentukan langsung (`parent`) atau dari santri terpilih (`students`):
     * santri yang dipilih harus berasal dari satu data orang tua.
     */
    public function preview(Request $request): JsonResponse
    {
        $employee = $this->decrypt($request->input('employee'), Employee::class);
        $parentId = null;

        if ($request->filled('parent')) {
            $parentId = $this->decrypt($request->input('parent'), Parents::class)?->id;
        } elseif (is_array($request->input('students')) && $request->input('students')) {
            $ids = [];

            foreach ($request->input('students') as $enc) {
                $ids[] = $this->decryptId($enc);
            }

            $parentIds = collect($this->linker->activeParentIds($ids))->unique()->values();

            if ($parentIds->isEmpty()) {
                return response()->json(['status' => false, 'message' => __('message.account_link_parent_required')], 422);
            }

            // Semua santri belum punya data orang tua: data orang tua baru dibuat dari data pegawai
            if ($parentIds->count() === 1 && $parentIds->first() === null) {
                $preview = $this->linker->previewNew($employee?->id, $ids);
                $preview['parent_id'] = null;

                return response()->json(['status' => true, 'data' => $preview]);
            }

            if ($parentIds->contains(fn ($id) => $id === null) && $parentIds->count() > 1) {
                return response()->json(['status' => false, 'message' => __('message.account_link_mixed_selection')], 422);
            }

            if ($parentIds->count() !== 1) {
                return response()->json([
                    'status' => false,
                    'message' => __('message.account_link_students_one_parent'),
                ], 422);
            }

            $parentId = (int) $parentIds->first();
        }

        if (! $parentId) {
            return response()->json(['status' => false, 'message' => __('message.account_link_parent_required')], 422);
        }

        $preview = $this->linker->preview($employee?->id, $parentId);
        $preview['parent_id'] = Crypt::encrypt($parentId);

        return response()->json(['status' => true, 'data' => $preview]);
    }

    public function link(Request $request): JsonResponse
    {
        $employee = $this->decrypt($request->input('employee'), Employee::class);
        $parent = $this->decrypt($request->input('parent'), Parents::class);

        if (! $employee || ! $parent) {
            return response()->json(['status' => false, 'message' => __('message.account_link_parent_required')], 422);
        }

        try {
            $result = $this->linker->link($employee->id, $parent->id);
        } catch (AccountLinkException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'blockers' => $e->blockers], 422);
        }

        return response()->json(['status' => true, 'message' => __('message.account_link_success'), 'data' => $result]);
    }

    public function unlink(Request $request): JsonResponse
    {
        $parent = $this->decrypt($request->input('parent'), Parents::class);

        if (! $parent) {
            return response()->json(['status' => false, 'message' => __('message.account_link_parent_required')], 422);
        }

        try {
            $result = $this->linker->unlink($parent->id);
        } catch (AccountLinkException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => true, 'message' => __('message.account_unlink_success'), 'data' => $result]);
    }

    private function decryptId(mixed $value): int
    {
        try {
            return (int) Crypt::decrypt((string) $value);
        } catch (\Throwable) {
            abort(422, 'ID tidak valid');
        }
    }

    /** @param  class-string  $model */
    private function decrypt(mixed $value, string $model): mixed
    {
        if (empty($value)) {
            return null;
        }

        $id = $this->decryptId($value);

        return $model::withoutGlobalScopes()->find($id);
    }
}
