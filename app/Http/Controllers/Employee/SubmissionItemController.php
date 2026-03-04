<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionItemController extends Controller
{
    private $title = 'label.list_item';
    private $path = 'backend.employee.submission.item.';
    private $icon = 'bx bxs-package';

    public function index()
    {
        return redirect()->route('employee.submission.index')->with('error', 'Halaman ini tidak tersedia!');
    }

    public function create()
    {
        $idEmployee = Auth::user()->employee->id;
        $types = [
            'habis pakai' => 'Habis Pakai',
            'inventaris' => 'Inventaris'
        ];

        $category = ItemCategory::selectRaw("id, CONCAT(code, ' - ', name) as label")
            ->pluck('label', 'id');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'idEmployee' => $idEmployee,
            'types' => $types,
            'category' => $category
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_employee' => 'required|exists:employee,id',
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'required|exists:item_categories,id',
            'name' => 'required|string|max:255',
            'merk' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'unit' => 'required|in:kg,pcs,unit,liter,box,roll,meter,meterkuadrat,meterkubik,score,rim',
            'description' => 'nullable|string',
            'photo' => 'nullable|mimes:jpeg,png,jpg|max:2048',
        ], [
            'id_employee.required' => 'Pegawai wajib diisi.',
            'id_employee.exists' => 'Pegawai tidak ditemukan.',
            'category_id.required' => 'Kategori wajib diisi.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'type.required' => 'Tipe wajib diisi.',
            'unit.required' => 'Satuan wajib diisi.',
            'unit.in' => 'Satuan tidak valid.',
            'merk.required' => 'Merk wajib diisi.',
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

        return redirect()->route('employee.submission.create')
            ->with('success', 'Item berhasil ditambahkan.');
    }
}
