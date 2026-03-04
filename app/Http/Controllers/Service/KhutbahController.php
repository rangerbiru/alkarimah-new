<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\Khutbah;
use Illuminate\Http\Request;

class KhutbahController extends Controller
{
    private $feature = 'backend.dashboard.feature.';

    public function index(Request $request)
    {
        $query = $request->input('q');

        $dataKhutbah = Khutbah::where('status', 'yes')
            ->when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->where('title', 'LIKE', "%$query%");
            })
            ->orderBy('id', 'asc')
            ->paginate(5);

        return view($this->feature . 'khutbah.index', [
            'title' => 'Halaman Khutbah',
            'icon' => 'bx bx-file',
            'dataKhutbah' => $dataKhutbah,
            'breadcrumbs' => [
                ['url' => '', 'label' => 'Khutbah']
            ]
        ]);
    }


    public function khutbahById($id)
    {
        $dataKhutbahById = Khutbah::find($id)->where('status', 'yes')->first();

        if (!$dataKhutbahById) {
            abort(404, 'Khutbah tidak ditemukan');
        }

        return view($this->feature . 'khutbah.detail', [
            'title' => 'Detail Khutbah: ' . $dataKhutbahById->title,
            'icon' => 'bx bx-file',
            'dataKhutbahById' => $dataKhutbahById,
            'breadcrumbs' => [
                ['url' => url('/khutbah'), 'label' => 'Khutbah'],
                ['url' => '', 'label' => $dataKhutbahById->title]
            ]
        ]);
    }
}
