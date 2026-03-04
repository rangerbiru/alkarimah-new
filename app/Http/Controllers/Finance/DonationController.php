<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonationRequest;
use App\Models\Donation;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    private $title = 'label.donation';
    private $icon = 'bx bx bx-donate';
    private $path = 'backend.donation.';

    public function index()
    {
        $count = Donation::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $donation = Donation::select('id', 'name', 'total', 'used');
        $donation_count = $donation->count();
        $donation_filter = $donation->where('name', 'like', '%' . $search . '%');
        $donation_count_filter = $donation_filter->count();

        $donation_data = $donation_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $donation_arr = [];

        foreach ($donation_data as $d) {
            $push = $d->toArray();
            $push['encrypted_id'] = $d->encrypted_id;

            array_push($donation_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $donation_count,
            'recordsFiltered' => $donation_count_filter,
            'data' => $donation_arr
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
        ]);
    }

    public function store(DonationRequest $request)
    {

        Donation::create($request->all());

        return redirect()->route('finance.donation.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Donation $donation)
    {
        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'donation' => $donation
        ]);
    }

    public function update(DonationRequest $request, Donation $donation)
    {
        $input = $request->all();
        $input['total'] = str_replace('.', '', $input['total']);
        $input['used'] = str_replace('.', '', $input['used']);

        $donation->update($input);

        return redirect()->route('finance.donation.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Donation $donation)
    {
        $donation->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }

}
