<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemsController extends Controller
{
    private $title = 'label.item_data';
    private $icon = 'ti ti-package';
    private $path = 'backend.hr.item.';

    public function index()
    {
        $count = Items::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        $types = [
            'habis pakai' => 'Habis Pakai',
            'inventaris' => 'Inventaris'
        ];

        $category = ItemCategory::selectRaw("id, CONCAT(code, ' - ', name) as label")
            ->pluck('label', 'id');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'types' => $types,
            'category' => $category
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'required|exists:item_categories,id',
            'name' => 'required|string|max:255',
            'merk' => 'nullable|string|max:255',
            'type' => 'required|string|max:255',
            'unit' => 'required|in:kg,pcs,unit,liter,box,roll,meter,meterkuadrat,meterkubik,score,rim',
            'description' => 'nullable|string',
            'photo' => 'nullable|mimes:jpeg,png,jpg|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
            $file->storeAs('items', $filename, 'public');
            $photoPath = $filename;
        }

        Items::create([
            'barcode' => $request->barcode,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'merk' => $request->merk,
            'type' => $request->type,
            'unit' => $request->unit,
            'description' => $request->description,
            'price' => $request->price,
            'photo' => $photoPath,
        ]);

        return redirect()->route('hr.item.index')
            ->with('success', 'Barang berhasil ditambahkan.');
    }

    public function destroy(Request $request, $id)
    {
        $item = Items::findOrFail($id);

        if ($item->photo) {
            Storage::disk('public')->delete('items/' . $item->photo);
        }

        $item->delete();

        return redirect()->route('hr.item.index')
            ->with('success', 'Barang berhasil dihapus.');
    }

    public function showModal($id)
    {
        $item = Items::findOrFail($id);

        $photo = [];
        if ($item->photo) {
            $decoded = json_decode($item->photo, true);
            if (is_array($decoded)) {
                $photo = $decoded;
            }
        }

        return view($this->path . 'modal', [
            'item' => $item,
            'photo' => $photo
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $item = Items::with('submissionItems', 'category');
        $item_count = $item->count();

        if (empty($search))
            $item_filter = $item;
        else
            // $item_filter = $item->whereHas('position', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
            //     ->orWhereHas('employee', fn($q) => $q->where('name', 'like', '%' . $search . '%'));
            $item_filter = $item->where('name', 'like', '%' . $search . '%')
                ->orWhere('barcode', 'like', '%' . $search . '%');

        $item_count_filter = $item_filter->count();
        $item_data = $item_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $item_arr = [];

        foreach ($item_data as $a) {
            $push = $a->toArray();
            $push['encrypted_id'] = $a->encrypted_id;

            array_push($item_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $item_count,
            'recordsFiltered' => $item_count_filter,
            'data' => $item_arr
        ]);
    }
}
