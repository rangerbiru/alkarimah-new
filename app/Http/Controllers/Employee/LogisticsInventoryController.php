<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ActualSubmissionItems;
use App\Models\InventoryItems;
use App\Models\LocationMaster;
use App\Models\SubmissionLocation;
use App\Models\Submissions;
use App\Models\UnitMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogisticsInventoryController extends Controller
{
    private $title = 'label.inventory_item';
    private $icon = 'ti ti-package';
    private $path = 'backend.employee.inventory.';

    public function index()
    {
        $submission = ActualSubmissionItems::with('items', 'submissions')->first();

        $location = SubmissionLocation::where('submissions_id', $submission->submissions_id)->first();

        $unit = UnitMaster::where('id', $location->unit_id)->first();

        dd($unit);

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
        ]);
    }

    public function showModal($id)
    {
        $item = InventoryItems::findOrFail($id);

        $documents = [];
        if ($item->documents) {
            $decoded = json_decode($item->documents, true);
            if (is_array($decoded)) {
                $documents = $decoded;
            }
        }

        return view($this->path . 'modal', [
            'item' => $item,
            'documents' => $documents,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $item = ActualSubmissionItems::findOrFail($id);
        $item->delete();

        return redirect()->route('hr.inventory-item.index')
            ->with('success', 'Data inventaris berhasil dihapus.');
    }

    public function inputInventory(Request $request, $id)
    {
        $item = ActualSubmissionItems::with('items', 'submissions')->findOrFail($id);

        $categoryId = $item->items && $item->items->category ?
            $item->items->category->id :
            null;

        $countExisting = 0;
        if ($categoryId) {
            $categoryName = $item->items->category->id;
            $countExisting = InventoryItems::where('category', $categoryName)->count();
        }
        $nextSequence = str_pad($countExisting + 1, 3, '0', STR_PAD_LEFT);

        $submission = $item->submissions->first();

        $locationCode = 'LOC';
        $locationId = null;
        $locationUnit = null;

        if ($submission) {
            if ($submission->location) {
                $locationCode = $submission->location->code;
                $locationId = $submission->location->id;
                $locationUnit = $submission->location->unit_id;
            }
        }

        $categoryCode = $item->items && $item->items->category ?
            $item->items->category->code :
            'CAT';

        $noNota = $submission ? $submission->activity_name : 'NO_NOTA';
        $inventoryCode = "{$locationCode}/{$categoryCode}/{$noNota}/{$nextSequence}";

        try {
            $inventoryItem = InventoryItems::create([
                'asset_id' => $item->id,
                'inventory_code' => $inventoryCode,
                'name' => $item->items ? $item->items->name : 'Unknown',
                'category' => $categoryId ? $categoryId : '-',
                'brand' => $item->items ? $item->items->merk : null,
                'specification' => $item->items ? $item->items->description : null,
                'location' => $locationId,
                'unit' => $locationUnit,
                'responsible_person' => $submission ? $submission->employee_id : null,
                'acquisition_date' => now()->format('Y-m-d'),
                'source_funding' => 'bos',
                'acquisition_price' => $item->price ?? 0,
                'quantity' => $item->quantity ?? 1,
                'total_acquisition_value' => ($item->price ?? 0) * ($item->quantity ?? 1),
                'residual_value' => 0,
                'useful_life_years' => 5,
                'depreciation_method' => 'Garis Lurus',
                'used_until_date' => '0 Bulan',
                'depreciation_amount_per_year' => 0,
                'depreciation_amount_per_month' => 0,
                'accumulated_depreciation' => 0,
                'book_value' => ($item->price ?? 0) * ($item->quantity ?? 1),
                'condition' => 'Baik',
                'status' => 'Aktif',
                'serial_number' => null,
                'documents' => $item->photo ? json_encode([$item->photo]) : null,
                'description' => $submission ? $submission->description : null,
            ]);

            return response()->json(['success' => true, 'message' => 'Data berhasil ditambahkan ke inventaris']);
        } catch (\Exception $e) {
            Log::error('Error auto-storing inventory: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search.value');
        $limit = $request->input('length');
        $start = $request->input('start');

        $query = ActualSubmissionItems::with('items', 'submissions')
            ->whereHas('items', function ($q) {
                $q->where('type', 'inventaris');
            });

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->whereHas('items', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })->orWhere('name', 'like', '%' . $search . '%');
        }

        // Hitung filtered records
        $recordsFiltered = $query->count();

        // Ambil data dengan pagination
        $inventoryData = $query->skip($start)
            ->take($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        $inventoryArr = $inventoryData->map(function ($item) {
            return array_merge($item->toArray(), [
                'encrypted_id' => $item->encrypted_id,
                'item_type' => $item->items?->first()?->type ?? null
            ]);
        })->toArray();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $inventoryArr
        ]);
    }
}
