<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\InventoryItems;
use App\Models\ItemCategory;
use App\Models\Items;
use App\Models\LocationMaster;
use App\Models\UnitMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InventoryItemController extends Controller
{
    private $title = 'label.inventory_item';
    private $icon = 'ti ti-package';
    private $path = 'backend.hr.inventory-item.';

    public function index()
    {
        $count = InventoryItems::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        $items = Items::with('category', 'submissionItems')
            ->where('type', 'inventaris')
            ->orderBy('name')
            ->get();

        if ($items->isEmpty()) {
            return redirect()->route('hr.inventory-item.index')
                ->with('error', 'Data inventaris tidak ditemukan.');
        }

        $itemsData = $items->map(function ($item) {
            $nextSequence = 1; // Default

            if ($item->category) {
                $countExisting = InventoryItems::where('category', $item->category->id)
                    ->count();

                $nextSequence = $countExisting + 1;
            }

            return [
                'id' => $item->id,
                'name' => $item->name,
                'category_id' => $item->category_id,
                'category_name' => $item->category ? $item->category->code . ' - ' . $item->category->name : '-',
                'merk' => $item->merk,
                'unit' => $item->unit,
                'price' => $item->price,
                'description' => $item->description,
                'next_sequence' => $nextSequence,
            ];
        });

        $locations = LocationMaster::orderBy('name')->select('id', 'name', 'code')->get();
        $units = UnitMaster::with('location')->orderBy('unit')->get();

        $unitsData = $units->map(function ($unit) {
            return [
                'id' => $unit->id,
                'location_id' => $unit->location_id,
                'unit' => $unit->unit,
            ];
        });

        $employees = Employee::orderBy('name')->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'items' => $items,
            'itemsData' => $itemsData,
            'locations' => $locations,
            'units' => $units,
            'unitsData' => $unitsData,
            'employees' => $employees
        ]);
    }

    public function store(Request $request)
    {
        // Validasi
        $validated = $request->validate([
            'asset_id' => 'required|string|max:255',
            'inventory_code' => 'required|string|max:255',
            'no_nota' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'responsible_person' => 'nullable|string|max:255',
            'acquisition_date' => 'required|date',
            'source_funding' => 'nullable|string|max:255',
            'acquisition_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'total_acquisition_value' => 'nullable|numeric|min:0',
            'residual_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'nullable|integer|min:0',
            'depreciation_method' => 'nullable|string|max:255',
            'used_until_date' => 'required|string|max:50',
            'depreciation_amount_per_year' => 'nullable|numeric|min:0',
            'depreciation_amount_per_month' => 'nullable|numeric|min:0',
            'accumulated_depreciation' => 'nullable|numeric|min:0',
            'book_value' => 'nullable|numeric|min:0',
            'condition' => 'required|string|max:255',
            'status' => 'required|string',
            'serial_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'documents.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:10240',
        ], [
            'asset_id.required' => 'ID Aset wajib diisi',
            'no_nota.required' => 'Nomor Nota wajib diisi',
            'inventory_code.required' => 'Kode Inventaris wajib diisi',
            'inventory_code.unique' => 'Kode Inventaris sudah digunakan',
            'name.required' => 'Nama Aset wajib diisi',
            'category.required' => 'Kategori wajib diisi',
            'acquisition_date.required' => 'Tanggal Perolehan wajib diisi',
            'quantity.required' => 'Jumlah wajib diisi',
            'quantity.min' => 'Jumlah minimal 1',
            'used_until_date.required' => 'Bulan Terpakai wajib diisi',
            'condition.required' => 'Kondisi wajib diisi',
            'status.required' => 'Status wajib diisi',
            'documents.*.max' => 'Ukuran file tidak boleh lebih dari 10MB',
            'documents.*.mimes' => 'Format file harus: jpg, jpeg, png, pdf, doc, docx, xls, xlsx',
        ]);

        try {
            // Proses upload dokumen
            $documentPaths = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $filename = 'INV_' . now()->format('YmdHis') . '_' . Str::random(5) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('inventory-item', $filename, 'public');
                    $documentPaths[] = $path;
                }
            }

            $location = LocationMaster::find($validated['location']);
            $item = Items::find($validated['asset_id']);

            $locationCode = $location ? $location->code : '-';
            $categoryCode = $item && $item->category ? $item->category->code : '-';

            $inventoryCode = "{$locationCode}/{$categoryCode}/{$validated['no_nota']}/{$request->unique_id}";

            // Simpan ke database
            $inventoryItem = InventoryItems::create([
                'asset_id' => $validated['asset_id'],
                'inventory_code' => $inventoryCode,
                'name' => $validated['name'],
                'category' => $request->category_id,
                'brand' => $validated['brand'] ?? null,
                'specification' => $validated['specification'] ?? null,
                'location' => $validated['location'] ?? null,
                'unit' => $validated['unit'] ?? null,
                'responsible_person' => $validated['responsible_person'] ?? null,
                'acquisition_date' => $validated['acquisition_date'],
                'source_funding' => $validated['source_funding'] ?? null,
                'acquisition_price' => $validated['acquisition_price'] ?? 0,
                'quantity' => $validated['quantity'],
                'total_acquisition_value' => $validated['total_acquisition_value'] ?? 0,
                'residual_value' => $validated['residual_value'] ?? 0,
                'useful_life_years' => $validated['useful_life_years'] ?? 0,
                'depreciation_method' => $validated['depreciation_method'] ?? null,
                'used_until_date' => $validated['used_until_date'],
                'depreciation_amount_per_year' => $validated['depreciation_amount_per_year'] ?? 0,
                'depreciation_amount_per_month' => $validated['depreciation_amount_per_month'] ?? 0,
                'accumulated_depreciation' => $validated['accumulated_depreciation'] ?? 0,
                'book_value' => $validated['book_value'] ?? 0,
                'condition' => $validated['condition'],
                'status' => $validated['status'],
                'serial_number' => $validated['serial_number'] ?? null,
                'documents' => json_encode($documentPaths),
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()->route('hr.inventory-item.index')
                ->with('success', 'Data inventaris berhasil disimpan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error storing inventory item: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function showModal($id)
    {
        $item = InventoryItems::findOrFail($id);

        $locationName = LocationMaster::where('id', $item->location)->value('name') ?? '-';

        $category = ItemCategory::where('id', $item->category)->first();

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
            'locationName' => $locationName,
            'category' => $category
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $item = InventoryItems::findOrFail($id);
        $item->delete();

        return redirect()->route('hr.inventory-item.index')
            ->with('success', 'Data inventaris berhasil dihapus.');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $inventory = InventoryItems::with('location', 'category');
        $inventory_count = $inventory->count();

        if (empty($search))
            $inventory_filter = $inventory;
        else
            $inventory_filter = $inventory->whereHas('category', fn($q) => $q->where('code', 'like', '%' . $search . '%'))
                ->orWhere('name', 'like', '%' . $search . '%');

        $inventory_count_filter = $inventory_filter->count();
        $inventory_data = $inventory_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $inventory_arr = [];

        foreach ($inventory_data as $a) {
            $push = $a->toArray();
            $push['encrypted_id'] = $a->encrypted_id;

            array_push($inventory_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $inventory_count,
            'recordsFiltered' => $inventory_count_filter,
            'data' => $inventory_arr
        ]);
    }
}
