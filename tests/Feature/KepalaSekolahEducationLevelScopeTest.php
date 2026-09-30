<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Pembatasan data per jenjang untuk role kepala sekolah.
 *
 * Memakai DB app_alkarimah_testing (skema hasil salin dari DB dev, tanpa data)
 * dengan DatabaseTransactions, karena migrasi dari nol belum bisa jalan.
 */
class KepalaSekolahEducationLevelScopeTest extends TestCase
{
    use DatabaseTransactions;

    private const BRANCH_ID = 990001;

    /** @var array<string, int> id kelas per jenjang: sd, smp, sma */
    private array $classes = [];

    /** @var array<string, string> nama siswa per jenjang */
    private array $studentNames = [];

    /** @var array<string, int> */
    private array $students = [];

    private int $billId;

    private int $waliKelasSdId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('app_alkarimah_testing', DB::connection()->getDatabaseName());

        $this->waliKelasSdId = $this->createUser('wali-kelas');

        $yearId = DB::table('year')->insertGetId([
            'start_year' => 2026, 'start_month' => '07', 'end_year' => 2027, 'end_month' => '06',
            'status' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        $this->billId = DB::table('bill')->insertGetId([
            'id_year' => $yearId, 'id_type' => 1, 'name' => 'SPP Test', 'nominal' => 100000,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        $totals = ['sd' => 100000, 'smp' => 200000, 'sma' => 400000];

        foreach ($totals as $level => $total) {
            $this->classes[$level] = DB::table('class')->insertGetId([
                'id_wali_kelas' => $level == 'sd' ? $this->waliKelasSdId : 0,
                'name' => 'Kelas Test '.strtoupper($level),
                'level_education' => $level,
                'level_class' => 1,
                'branch_id' => self::BRANCH_ID,
                'created_by' => 0,
            ]);

            $this->studentNames[$level] = 'Siswa Test '.strtoupper($level);

            $this->students[$level] = DB::table('student')->insertGetId([
                'id_parent' => 0,
                'id_class' => $this->classes[$level],
                'nis' => 'T-'.$level,
                'name' => $this->studentNames[$level],
                'gender' => 'male',
                'religion' => 'Islam',
                'exculs' => '[]',
                'status' => 1,
                'branch_id' => self::BRANCH_ID,
                'created_by' => 0,
            ]);

            DB::table('transaction_bill')->insert([
                'id_student' => $this->students[$level],
                'id_bill' => $this->billId,
                'months' => 7,
                'years' => 2026,
                'total' => $total,
                'status' => 0,
                'branch_id' => self::BRANCH_ID,
                'created_by' => 0,
            ]);
        }
    }

    private function createUser(string $role, array $levels = []): int
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Test '.$role,
            'password' => bcrypt('secret'),
            'role' => $role,
            'phone' => '08'.random_int(100000000, 999999999),
            'gender' => 'male',
            'branch_id' => self::BRANCH_ID,
            'created_by' => 0,
        ]);

        foreach ($levels as $level) {
            DB::table('user_education_levels')->insert(['user_id' => $id, 'level_education' => $level]);
        }

        return $id;
    }

    private function actingAsRole(string $role, array $levels = []): User
    {
        $user = User::findOrFail($this->createUser($role, $levels));
        $this->actingAs($user);

        return $user;
    }

    private function datatable(array $params = [])
    {
        return $this->post(route('finance.report.datatable.bill-per-type'), array_merge([
            'draw' => 1, 'start' => 0, 'length' => 50, 'search' => ['value' => ''],
            'year' => '', 'class' => '', 'bill_type' => $this->billId,
        ], $params));
    }

    private function datatableStudentNames(array $params = []): array
    {
        $response = $this->datatable($params)->assertOk();

        return collect($response->json('data'))->pluck('student_name')->sort()->values()->all();
    }

    private function totalBill(array $params = [])
    {
        return $this->post(route('finance.report.get.total-bill-per-type'), array_merge([
            'year' => '', 'class' => '', 'bill_type' => $this->billId,
        ], $params));
    }

    private function namesOf(array $levels): array
    {
        return collect($levels)->map(fn ($l) => $this->studentNames[$l])->sort()->values()->all();
    }

    /** Nama siswa di file Excel hasil download (kolom C). */
    private function excelStudentNames(array $params = []): array
    {
        ob_start();
        $this->get(route('finance.report.download.excel.bill-per-type', array_merge([
            'year' => '', 'class' => '', 'bill_type' => $this->billId,
        ], $params)))->assertOk();
        $content = ob_get_clean();

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $content);
        $sheet = IOFactory::load($file)->getActiveSheet();
        unlink($file);

        return collect($sheet->rangeToArray('C1:C'.$sheet->getHighestRow()))
            ->flatten()
            ->filter(fn ($v) => is_string($v) && str_starts_with($v, 'Siswa Test'))
            ->sort()->values()->all();
    }

    // ---- Laporan tagihan per jenis ----

    public function test_kepala_sekolah_smp_only_sees_smp_classes_in_dropdown(): void
    {
        $this->actingAsRole('kepala-sekolah', ['smp']);

        $response = $this->get(route('finance.report.bill-per-type'))->assertOk();

        $classIds = array_keys($response->viewData('classes')->all());
        $this->assertSame([$this->classes['smp']], $classIds);
    }

    public function test_kepala_sekolah_smp_is_rejected_for_other_level_class(): void
    {
        $this->actingAsRole('kepala-sekolah', ['smp']);

        foreach (['sd', 'sma'] as $level) {
            $params = ['year' => '', 'class' => $this->classes[$level], 'bill_type' => $this->billId];

            $this->datatable(['class' => $this->classes[$level]])->assertForbidden();
            $this->totalBill(['class' => $this->classes[$level]])->assertForbidden();
            $this->get(route('finance.report.download.excel.bill-per-type', $params))->assertForbidden();
            $this->get(route('finance.report.download.pdf.bill-per-type', $params))->assertForbidden();
        }
    }

    public function test_kepala_sekolah_smp_all_classes_returns_only_smp_data(): void
    {
        $this->actingAsRole('kepala-sekolah', ['smp']);

        $this->assertSame($this->namesOf(['smp']), $this->datatableStudentNames());
        $this->assertEquals(200000, $this->totalBill()->assertOk()->json('data.total'));
        $this->assertSame($this->namesOf(['smp']), $this->excelStudentNames());

        $this->get(route('finance.report.download.pdf.bill-per-type', ['year' => '', 'class' => '', 'bill_type' => $this->billId]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Kelas miliknya sendiri tetap boleh dipilih
        $this->assertSame($this->namesOf(['smp']), $this->datatableStudentNames(['class' => $this->classes['smp']]));
    }

    public function test_kepala_sekolah_with_two_levels_sees_both_and_nothing_else(): void
    {
        $this->actingAsRole('kepala-sekolah', ['smp', 'sma']);

        $response = $this->get(route('finance.report.bill-per-type'))->assertOk();
        $classIds = collect(array_keys($response->viewData('classes')->all()))->sort()->values()->all();
        $this->assertSame([$this->classes['smp'], $this->classes['sma']], $classIds);

        $this->assertSame($this->namesOf(['smp', 'sma']), $this->datatableStudentNames());
        $this->assertEquals(600000, $this->totalBill()->assertOk()->json('data.total'));
        $this->assertSame($this->namesOf(['sma']), $this->datatableStudentNames(['class' => $this->classes['sma']]));

        $this->datatable(['class' => $this->classes['sd']])->assertForbidden();
    }

    public function test_kepala_sekolah_without_level_sees_nothing(): void
    {
        $this->actingAsRole('kepala-sekolah');

        $this->assertSame([], $this->datatableStudentNames());
        $this->assertEquals(0, $this->totalBill()->assertOk()->json('data.total'));
    }

    public function test_admin_and_bendahara_still_see_all_levels(): void
    {
        foreach (['admin', 'bendahara'] as $role) {
            $user = $this->actingAsRole($role);

            $this->assertNull($user->allowedClassIds());

            $response = $this->get(route('finance.report.bill-per-type'))->assertOk();
            $classIds = collect(array_keys($response->viewData('classes')->all()))->sort()->values()->all();
            $expected = collect($this->classes)->values()->sort()->values()->all();
            $this->assertSame($expected, $classIds, $role);

            $this->assertSame($this->namesOf(['sd', 'smp', 'sma']), $this->datatableStudentNames(), $role);
            $this->assertEquals(700000, $this->totalBill()->assertOk()->json('data.total'), $role);
            $this->assertSame($this->namesOf(['sd']), $this->datatableStudentNames(['class' => $this->classes['sd']]), $role);
            $this->assertSame($this->namesOf(['sd', 'smp', 'sma']), $this->excelStudentNames(), $role);
        }
    }

    public function test_wali_kelas_still_limited_to_own_class(): void
    {
        $this->actingAs(User::findOrFail($this->waliKelasSdId));

        $this->assertSame($this->namesOf(['sd']), $this->datatableStudentNames());
        $this->assertEquals(100000, $this->totalBill()->assertOk()->json('data.total'));
        $this->datatable(['class' => $this->classes['smp']])->assertForbidden();
    }

    // ---- Halaman lain ----

    public function test_violation_datatable_and_student_search_are_limited_for_kepala_sekolah(): void
    {
        foreach ($this->students as $studentId) {
            DB::table('student_violations')->insert([
                'student_id' => $studentId, 'violation_id' => 0, 'employee_id' => 0,
                'date' => '2026-09-01', 'time' => '08:00:00', 'location' => 'Test',
            ]);
        }

        $violationStudentIds = function () {
            $response = $this->post(route('academic.violation.datatable'), ['draw' => 1, 'start' => 0, 'length' => 50])->assertOk();

            return collect($response->json('data'))->pluck('student_id')
                ->intersect($this->students)->sort()->values()->all();
        };
        $searchStudentIds = fn () => collect($this->post(route('academic.violation.students'), ['q' => 'Siswa Test'])->assertOk()->json('results'))
            ->pluck('id')->sort()->values()->all();

        $this->actingAsRole('kepala-sekolah', ['smp']);
        $this->assertSame([$this->students['smp']], $violationStudentIds());
        $this->assertSame([$this->students['smp']], $searchStudentIds());

        // Role lain tidak berubah
        $this->actingAsRole('pegawai');
        $all = collect($this->students)->values()->sort()->values()->all();
        $this->assertSame($all, $violationStudentIds());
        $this->assertSame($all, $searchStudentIds());
    }

    public function test_absence_student_list_is_limited_for_kepala_sekolah(): void
    {
        $typeId = DB::table('absence_type')->insertGetId([
            'name' => 'Umum Test', 'flag' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        $this->actingAsRole('kepala-sekolah', ['smp']);
        $list = $this->post(route('academic.absence.get.student'), ['type' => $typeId])->assertOk()->json('data.list');

        $this->assertStringContainsString($this->studentNames['smp'], $list);
        $this->assertStringNotContainsString($this->studentNames['sd'], $list);
        $this->assertStringNotContainsString($this->studentNames['sma'], $list);
    }

    public function test_monitoring_class_filter_is_limited_for_kepala_sekolah(): void
    {
        $this->actingAsRole('kepala-sekolah', ['smp']);

        $response = $this->get(route('academic.monitoring.index'))->assertOk();
        $this->assertSame(['smp|1'], array_keys($response->viewData('classList')));

        $this->get(route('academic.monitoring.index', ['class' => 'sma|1']))->assertForbidden();
        $this->get(route('academic.monitoring.index', ['class' => 'smp|1']))->assertOk();
    }

    public function test_routes_closed_for_kepala_sekolah(): void
    {
        $this->actingAsRole('kepala-sekolah', ['smp']);

        $this->post(route('dashboard.datatable.withdrawal'), ['draw' => 1, 'start' => 0, 'length' => 10])->assertRedirect(route('base'));
        $this->get(route('finance.report.bill-not-paid'))->assertRedirect(route('base'));
        $this->get(route('finance.report.donation'))->assertRedirect(route('base'));
    }
}
