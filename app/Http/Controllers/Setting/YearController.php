<?php

namespace App\Http\Controllers\Setting;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\YearRequest;
use App\Models\ReportStudent;
use App\Models\Year;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class YearController extends Controller
{
    private $title = 'label.school_year';
    private $icon = 'bx bx-calendar-week';
    private $path = 'backend.setting.year.';

    public function index()
    {
        $count = Year::count();
        $years = Common::option('year');
        $months = Common::option('month');

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
            'years' => $years,
            'months' => $months,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $year = Year::select('id', 'start_year', 'start_month', 'end_year', 'end_month', 'status');
        $year_count = $year->count();
        $year_filter = $year->where(function ($query) use ($search) {
            $query->where('start_year', 'like', '%' . $search . '%')
                ->orWhere('end_year', 'like', '%' . $search . '%');
        });
        $year_count_filter = $year_filter->count();
        $year_data = $year_filter->limit($limit)
            ->offset($start)
            ->orderBy('status', 'desc')
            ->orderBy('start_year', 'desc')
            ->get();

        $year_arr = [];

        foreach ($year_data as $y) {
            $push = $y->toArray();
            $push['encrypted_id'] = $y->encrypted_id;
            $push['year_name'] = $y->year_name;
            $push['month_range'] = $y->month_range;
            $push['status_label'] = $y->status_label;

            array_push($year_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $year_count,
            'recordsFiltered' => $year_count_filter,
            'data' => $year_arr
        ];

        return response()->json($response);
    }

    public function store(YearRequest $request)
    {
        Year::create($request->all());

        $response = [
            'status' => Response::HTTP_OK,
            'message' => __('message.create_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }

    public function update(YearRequest $request, Year $year)
    {
        $year->update($request->all());

        $response = [
            'status' => Response::HTTP_OK,
            'message' => __('message.update_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }

    public function updateStatus(Request $request, Year $year)
    {
        DB::transaction(function() use($request, $year) {
            Year::active()->update(['status' => false]);
            $year->update($request->all());

            ReportStudent::where('id', '>', 0)->update([
                'bill_paid' => 0,
            ]);
        });

        $response = [
            'status' => Response::HTTP_OK,
            'message' => __('message.update_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }

    public function destroy(Year $year)
    {
        $year->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }
}
