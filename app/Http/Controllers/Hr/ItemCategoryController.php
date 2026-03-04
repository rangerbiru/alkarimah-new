<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ItemCategoryController extends Controller
{
    private $title = 'label.item_category_master';
    private $icon = 'ti ti-package';
    private $path = 'backend.hr.master.category.';

    public function index()
    {
        $count = ItemCategory::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:item_categories,code',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'code.required' => 'Kode kategori wajib diisi.',
            'code.unique' => 'Kode kategori sudah digunakan.',
        ]);

        ItemCategory::create([
            'name' => $request->name,
            'code' => Str::upper($request->code),
        ]);

        return redirect()->route('hr.item-category.index')
            ->with('success', 'Kategori barang berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $item = ItemCategory::findOrFail($id);
        $item->delete();

        return redirect()->route('hr.item-category.index')
            ->with('success', 'Kategori barang berhasil dihapus.');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $item = ItemCategory::query();
        $item_count = $item->count();

        if (empty($search))
            $item_filter = $item;
        else
            $item_filter = $item->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%');

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
