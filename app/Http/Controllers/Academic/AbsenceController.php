<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AbsenceStatus;
use App\Enums\AbsenceTypeFlag;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\AbsenceRequest;
use App\Http\Requests\AbsenceTypeRequest;
use App\Models\Absence;
use App\Models\AbsenceDetail;
use App\Models\AbsenceType;
use App\Models\Activity;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AbsenceController extends Controller
{
    private $title = [
        'absence' => 'label.absence',
        'type' => 'label.absence_type',
    ];
    private $icon = 'bx bx-fingerprint';
    private $path = [
        'absence' => 'backend.academic.absence.',
        'type' => 'backend.academic.absence.type.',
    ];

    public function type()
    {
        $count = AbsenceType::count();

        return view($this->path['type'] . 'index', [
            'title' => __($this->title['type']),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function report()
    {
        $months = Common::option('month');
        $years = Common::option('year');

        return view($this->path['absence'] . 'report', [
            'title' => __($this->title['absence']),
            'icon' => $this->icon,
            'months' => $months,
            'years' => $years,
        ]);
    }

    public function datatableType(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $type = AbsenceType::select('id', 'name', 'icon', 'flag');
        $type_count = $type->count();

        if (empty($search))
            $type_filter = $type;
        else {
            $type_filter = $type->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $type_count_filter = $type_filter->count();
        $type_data = $type_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $type_arr = [];

        foreach ($type_data as $d) {
            $push = $d->toArray();
            $push['encrypted_id'] = $d->encrypted_id;
            $push['flag_name'] = $d->flag_name;
            $push['is_umum'] = $d->is_umum;

            array_push($type_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $type_count,
            'recordsFiltered' => $type_count_filter,
            'data' => $type_arr
        ]);
    }

    public function datatableReport(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $absence = AbsenceDetail::select('id', 'id_absence', 'id_student', 'status')
            ->with([
                'absence' => function($query) {
                    $query->select('id', 'id_type', 'dates', 'created_at')
                        ->with(['type' => fn($qt) => $qt->select('id', 'name')]);
                },
                'student' => function($query) {
                    $query->select('id', 'id_class', 'nis', 'name')
                        ->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                }
            ])
            ->whereHas('absence', function($query) use($request) {
                $query->whereMonth('dates', $request->month)
                    ->whereYear('dates', $request->year);
            });

        $absence_count = $absence->count();

        if (empty($search))
            $absence_filter = $absence;
        else {
            $absence_filter = $absence->where(function ($query) use ($search) {
                $query->whereHas('absence', function($qa) use($search) {
                    $qa->whereHas('type', function($qt) use($search) {
                        $qt->where('name', 'like', '%' . $search . '%');
                    });
                })
                ->whereHas('student', function($qs) use($search) {
                    $qs->where('nis', 'like', '%' . $search . '%')
                        ->where('name', 'like', '%' . $search . '%')
                        ->whereHas('class', function($qc) use($search) {
                            $qc->where('name', 'like', '%' . $search . '%');
                        });
                });
            });
        }

        $absence_count_filter = $absence_filter->count();
        $absence_data = $absence_filter->limit($limit)
            ->offset($start)
            ->orderBy('id', 'desc')
            ->get();

        $absence_arr = [];

        foreach ($absence_data as $d) {
            $push = $d->toArray();
            $push['status_badge'] = $d->status_badge;

            array_push($absence_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $absence_count,
            'recordsFiltered' => $absence_count_filter,
            'data' => $absence_arr
        ]);
    }

    public function create()
    {
        $types = AbsenceType::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path['absence'] . 'create', [
            'title' => __($this->title['absence']),
            'icon' => $this->icon,
            'types' => $types
        ]);
    }

    public function createType()
    {
        return view($this->path['type'] . 'create', [
            'title' => __($this->title['type']),
            'icon' => $this->icon,
        ]);
    }

    public function store(AbsenceRequest $request)
    {
        $status = $request->status;

        DB::transaction(function() use($request, $status) {
            $total_present = 0;
            $total_permit = 0;
            $total_sick = 0;
            $total_absent = 0;

            foreach ($status as $id => $st) {
                if ($st == AbsenceStatus::Hadir->value)
                    $total_present++;
                else if ($st == AbsenceStatus::Izin->value)
                    $total_permit++;
                else if ($st == AbsenceStatus::Sakit->value)
                    $total_sick++;
                else
                    $total_absent++;
            }

            $absence = Absence::create([
                'id_type' => $request->id_type,
                'dates' => date('Y-m-d', strtotime($request->dates)),
                'total_present' => $total_present,
                'total_permit' => $total_permit,
                'total_sick' => $total_sick,
                'total_absent' => $total_absent,
            ]);

            $activity_title = trim(str_replace('Absensi', '', $absence->type->name));

            foreach ($status as $id => $st) {
                $student = Student::select('name')->whereId($id)->first();

                AbsenceDetail::create([
                    'id_absence' => $absence->id,
                    'id_student' => $id,
                    'status' => $st
                ]);

                if ($st == AbsenceStatus::Absen->value)
                    $activity = 'tidak mengikuti kegiatan';
                else if ($st == AbsenceStatus::Hadir->value)
                    $activity = 'mengikuti kegiatan dengan baik';
                elseif ($st == AbsenceStatus::Izin->value)
                    $activity = 'tidak mengikuti kegiatan karena telah izin untuk tidak hadir';
                else
                    $activity = 'tidak mengikuti kegiatan karena sedang sakit';

                Activity::create([
                    'id_student' => $id,
                    'icon' => $absence->type->icon,
                    'title' => $activity_title,
                    'message' => 'Ananda ' . $student->name . ' ' . $activity
                ]);
            }
        });

        return Redirect::route('academic.absence.create')->with('success', __('message.create_success', ['label' => __($this->title['absence'])]));
    }

    public function storeType(AbsenceTypeRequest $request)
    {
        $request->merge(['flag' => AbsenceTypeFlag::Umum->value]);
        AbsenceType::create($request->all());

        return Redirect::route('academic.absence.type.index')->with('success', __('message.create_success', ['label' => __($this->title['type'])]));
    }

    public function editType(AbsenceType $type)
    {
        return view($this->path['type'] . 'edit', [
            'title' => __($this->title['type']),
            'icon' => $this->icon,
            'type' => $type
        ]);
    }

    public function updateType(AbsenceTypeRequest $request, AbsenceType $type)
    {
        $type->update($request->all());

        return Redirect::route('academic.absence.type.index')->with('success', __('message.update_success', ['label' => __($this->title['type'])]));
    }

    public function destroyType(AbsenceType $type)
    {
        $type->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['type'])])
        ];

        return response()->json($response);
    }

    public function downloadPdfReport(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $status_color = ['#e6533c', '#26aa84', '#49B6F5', '#dea741'];

        $absence = AbsenceDetail::select('id', 'id_absence', 'id_student', 'status')
            ->with([
                'absence' => function ($query) {
                    $query->select('id', 'id_type', 'dates', 'created_at')
                        ->with(['type' => fn($qt) => $qt->select('id', 'name')]);
                },
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')
                        ->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                }
            ])
            ->whereHas('absence', function ($query) use ($month, $year) {
                $query->whereMonth('dates', $month)
                    ->whereYear('dates', $year);
            })
            ->orderBy('id', 'desc')
            ->get();

        $pdf = Pdf::loadView($this->path['absence'] . 'pdf-report', [
            'absence' => $absence,
            'month' => $month,
            'year' => $year,
            'status_color' => $status_color,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.absence_report'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadExcelReport(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $status_color = ['f1998c', '3bd6aa', '5ABCF5', 'f9c563'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $style_col = [
            'font' => ['bold' => true], // Set font nya jadi bold
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Set text jadi ditengah secara horizontal (center)
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ],
            'borders' => [
                'top' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border top dengan garis tipis
                'right' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],  // Set border right dengan garis tipis
                'bottom' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border bottom dengan garis tipis
                'left' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN] // Set border left dengan garis tipis
            ]
        ];

        $style_row = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ],
            'borders' => [
                'top' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border top dengan garis tipis
                'right' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],  // Set border right dengan garis tipis
                'bottom' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border bottom dengan garis tipis
                'left' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN] // Set border left dengan garis tipis
            ]
        ];

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.absence_report'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.month') . ' : ' . Common::monthFormat($month));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.year') . ' : ' . $year);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row += 2;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.nis'));
        $sheet->setCellValue('C' . $row, __('label.name'));
        $sheet->setCellValue('D' . $row, __('label.class'));
        $sheet->setCellValue('E' . $row, __('label.absence_type'));
        $sheet->setCellValue('F' . $row, __('label.date'));
        $sheet->setCellValue('G' . $row, __('label.time'));
        $sheet->setCellValue('H' . $row, __('label.status'));

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;
        $absence = AbsenceDetail::select('id', 'id_absence', 'id_student', 'status')
            ->with([
                'absence' => function ($query) {
                    $query->select('id', 'id_type', 'dates', 'created_at')
                        ->with(['type' => fn($qt) => $qt->select('id', 'name')]);
                },
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')
                        ->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                }
            ])
            ->whereHas('absence', function ($query) use ($month, $year) {
                $query->whereMonth('dates', $month)
                    ->whereYear('dates', $year);
            })
            ->orderBy('id', 'desc')
            ->get();

        foreach ($absence as $a) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $a->student->nis);
            $sheet->setCellValue('C' . $row, $a->student->name);
            $sheet->setCellValue('D' . $row, $a->student->class->name);
            $sheet->setCellValue('E' . $row, $a->absence->type->name);
            $sheet->setCellValue('F' . $row, Common::dateFormat($a->absence->dates));
            $sheet->setCellValue('G' . $row, date('H:i', strtotime($a->absence->created_at)));
            $sheet->setCellValue('H' . $row, $a->status_name);

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($status_color[$a->status->value]);

            $row++;
            $no++;
        }

        // Setting
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.absence_report'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.absence_report'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function getStudent(Request $request)
    {
        $type = AbsenceType::select('id', 'id_excul', 'flag')->whereId($request->type)->first();

        if ($type->is_umum) {
            $student = Student::select('id', 'id_class', 'nis', 'name')
                ->with(['class' => fn($query) => $query->select('id', 'name')])
                ->orderBy('id_class')
                ->orderBy('name')
                ->get();
        } else {
            $student = Student::select('id', 'id_class', 'nis', 'name')
                ->with(['class' => fn($query) => $query->select('id', 'name')])
                ->where('exculs', 'like', '%"' . $type->id_excul . '"%')
                ->orderBy('id_class')
                ->orderBy('name')
                ->get();
        }

        $list = view($this->path['absence'] . 'create-student-list', ['student' => $student])->render();
        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'list' => $list,
            ]
        ];

        return response()->json($response);
    }
}
