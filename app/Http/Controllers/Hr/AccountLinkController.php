<?php

namespace App\Http\Controllers\Hr;

use App\Helpers\Phone;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Parents;
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
