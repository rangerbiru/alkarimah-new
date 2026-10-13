<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Laporan tagihan per jenis: filter berdasarkan jenis tagihan (bill_type),
 * semua tagihan dalam satu jenis digabung jadi satu baris per siswa.
 *
 * Memakai DB app_alkarimah_testing dengan DatabaseTransactions.
 */
class BillPerTypeReportTest extends TestCase
{
    use DatabaseTransactions;

    private const BRANCH_ID = 990002;

    private int $yearId;

    private int $otherYearId;

    private int $classId;

    private int $sppTypeId;

    private int $bookTypeId;

    private int $deletedTypeId;

    /** @var array<string, int> */
    private array $students = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('app_alkarimah_testing', DB::connection()->getDatabaseName());

        $this->yearId = $this->createYear(2026);
        $this->otherYearId = $this->createYear(2025);

        $this->sppTypeId = $this->createType('SPP Agregasi', '2026-01-01 00:00:00');
        $this->bookTypeId = $this->createType('Buku Agregasi', '2026-02-01 00:00:00');
        $this->deletedTypeId = $this->createType('Jenis Terhapus', '2026-03-01 00:00:00', true);

        $spp300 = $this->createBill($this->yearId, $this->sppTypeId, 'SPP 300');
        $spp700 = $this->createBill($this->yearId, $this->sppTypeId, 'SPP 700');
        $sppOldYear = $this->createBill($this->otherYearId, $this->sppTypeId, 'SPP 300 Lama');
        $book = $this->createBill($this->yearId, $this->bookTypeId, 'Buku Kelas 9');

        $this->classId = DB::table('class')->insertGetId([
            'id_wali_kelas' => 0, 'name' => '9A Test', 'level_education' => 'smp', 'level_class' => 9,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        foreach (['ahmad', 'budi'] as $name) {
            $this->students[$name] = DB::table('student')->insertGetId([
                'id_parent' => 0, 'id_class' => $this->classId, 'nis' => 'AGG-'.$name, 'name' => 'Siswa Agregasi '.$name,
                'gender' => 'male', 'religion' => 'Islam', 'exculs' => '[]', 'status' => 1,
                'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        // Ahmad: dua tagihan SPP di tahun ajaran yang sama (satu lunas), plus Buku dan SPP tahun lalu
        $this->createTransactionBill('ahmad', $spp300, 300000, 0);
        $this->createTransactionBill('ahmad', $spp700, 700000, 1);
        $this->createTransactionBill('ahmad', $book, 50000, 0);
        $this->createTransactionBill('ahmad', $sppOldYear, 999, 0);

        // Budi: satu tagihan SPP lunas
        $this->createTransactionBill('budi', $spp300, 300000, 1);

        $this->actingAs(User::findOrFail(DB::table('users')->insertGetId([
            'name' => 'Test admin', 'password' => bcrypt('secret'), 'role' => 'admin',
            'phone' => '08'.random_int(100000000, 999999999), 'gender' => 'male',
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ])));
    }

    private function createYear(int $startYear): int
    {
        return DB::table('year')->insertGetId([
            'start_year' => $startYear, 'start_month' => '07', 'end_year' => $startYear + 1, 'end_month' => '06',
            'status' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
    }

    private function createType(string $name, string $createdAt, bool $deleted = false): int
    {
        return DB::table('bill_type')->insertGetId([
            'name' => $name, 'period' => 2, 'spp' => 1, 'branch_id' => self::BRANCH_ID,
            'created_at' => $createdAt, 'created_by' => 0, 'deleted_at' => $deleted ? $createdAt : null,
        ]);
    }

    private function createBill(int $yearId, int $typeId, string $name): int
    {
        return DB::table('bill')->insertGetId([
            'id_year' => $yearId, 'id_type' => $typeId, 'name' => $name, 'nominal' => 100000,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
    }

    private function createTransactionBill(string $student, int $billId, int $total, int $status): void
    {
        DB::table('transaction_bill')->insert([
            'id_student' => $this->students[$student], 'id_bill' => $billId, 'months' => 7, 'years' => 2026,
            'total' => $total, 'status' => $status, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
    }

    private function params(array $params = []): array
    {
        return array_merge(['year' => $this->yearId, 'class' => $this->classId, 'bill_type' => $this->sppTypeId], $params);
    }

    /** @return array<string, array{0: float, 1: float, 2: float}> total, dibayar, sisa per NIS */
    private function datatableRows(array $params = []): array
    {
        $response = $this->post(route('finance.report.datatable.bill-per-type'), array_merge([
            'draw' => 1, 'start' => 0, 'length' => 50, 'search' => ['value' => ''],
        ], $this->params($params)))->assertOk();

        $rows = collect($response->json('data'));
        $this->assertSame($rows->count(), $rows->pluck('nis')->unique()->count(), 'siswa muncul dobel');

        return $rows->mapWithKeys(fn ($r) => [
            $r['nis'] => [(float) $r['total_amount'], (float) $r['total_paid'], (float) $r['total_remaining']],
        ])->sortKeys()->all();
    }

    private function total(array $params = []): array
    {
        $data = $this->post(route('finance.report.get.total-bill-per-type'), $this->params($params))->assertOk()->json('data');

        return [(float) $data['total'], (float) $data['paid'], (float) $data['remaining']];
    }

    public function test_dropdown_lists_bill_types_not_individual_bills(): void
    {
        $billTypes = $this->get(route('finance.report.bill-per-type'))->assertOk()->viewData('bill_types')->all();

        $this->assertSame(['Buku Agregasi', 'SPP Agregasi'], array_values($billTypes));
        $this->assertSame([$this->bookTypeId, $this->sppTypeId], array_keys($billTypes));
    }

    public function test_bills_of_the_same_type_are_summed_into_one_row_per_student(): void
    {
        $this->assertSame([
            'AGG-ahmad' => [1000000.0, 700000.0, 300000.0],
            'AGG-budi' => [300000.0, 300000.0, 0.0],
        ], $this->datatableRows());

        $this->assertSame([1300000.0, 1000000.0, 300000.0], $this->total());
    }

    public function test_year_and_type_filters_still_apply(): void
    {
        $this->assertSame(['AGG-ahmad' => [50000.0, 0.0, 50000.0]], $this->datatableRows(['bill_type' => $this->bookTypeId]));
        $this->assertSame([50000.0, 0.0, 50000.0], $this->total(['bill_type' => $this->bookTypeId]));

        $this->assertSame(['AGG-ahmad' => [999.0, 0.0, 999.0]], $this->datatableRows(['year' => $this->otherYearId]));
        $this->assertSame([], $this->datatableRows(['bill_type' => $this->deletedTypeId]));
    }

    public function test_excel_and_pdf_use_bill_type_name(): void
    {
        ob_start();
        $this->get(route('finance.report.download.excel.bill-per-type', $this->params()))->assertOk();
        $content = ob_get_clean();

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $content);
        $sheet = IOFactory::load($file)->getActiveSheet();
        unlink($file);

        $this->assertSame('Jenis Tagihan : SPP Agregasi', $sheet->getCell('A3')->getValue());

        $rows = collect($sheet->rangeToArray('B1:G'.$sheet->getHighestRow()))
            ->filter(fn ($r) => is_string($r[0]) && str_starts_with($r[0], 'AGG-'))
            ->mapWithKeys(fn ($r) => [$r[0] => [(float) $r[3], (float) $r[4], (float) $r[5]]])
            ->sortKeys()->all();
        $this->assertSame($this->datatableRows(), $rows);

        $this->get(route('finance.report.download.pdf.bill-per-type', $this->params()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
