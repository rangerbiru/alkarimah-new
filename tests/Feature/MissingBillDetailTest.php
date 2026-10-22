<?php

namespace Tests\Feature;

use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Feature\Concerns\CancellationFixtures;

/**
 * Transaksi tagihan yang baris transaction_bill-nya sudah tidak ada (dihapus langsung di database) tidak boleh
 * menjatuhkan halaman verifikasi setoran kas ("Attempt to read property "bill" on null").
 */
class MissingBillDetailTest extends ModuleAccessTestCase
{
    use CancellationFixtures;

    private const MISSING = 99999990;

    private function cashTransaction(array $bills, float $subtotal): Transaction
    {
        $this->actingAs($this->kasir);

        return Transaction::create([
            'id_student' => $this->studentId, 'dates' => date('Y-m-d'), 'flag' => TransactionFlag::Tagihan->value,
            'payment_method' => TransactionMethod::Cash->value, 'status' => TransactionStatus::Paid->value,
            'paid_at' => now(), 'paid_by' => $this->kasir->id, 'subtotal' => $subtotal, 'total' => $subtotal,
            'bills' => $bills,
        ]);
    }

    public function test_missing_bill_row_becomes_a_placeholder_with_the_transaction_amount(): void
    {
        $single = $this->cashTransaction([self::MISSING], 200000);
        $detail = collect($single->fresh()->bills_detail)->values();

        $this->assertCount(1, $detail);
        $this->assertSame(__('string.bill_missing', ['id' => self::MISSING]), $detail[0]->name);
        $this->assertEquals(200000, $detail[0]->total);
        $this->assertSame(0, $detail[0]->type->id);
        $this->assertSame(0, $detail[0]->year->id);
    }

    public function test_existing_bill_is_unaffected_and_missing_one_among_several_has_unknown_amount(): void
    {
        $t = $this->cashTransaction([$this->tbIds[0], self::MISSING], 950000);
        $detail = collect($t->fresh()->bills_detail)->values();

        $this->assertCount(2, $detail);
        $this->assertStringContainsString('SPP', $detail[0]->name);
        $this->assertEquals(750000, $detail[0]->total);
        $this->assertEquals(0, $detail[1]->total);
    }

    #[RunInSeparateProcess]
    public function test_bendahara_can_open_verification_page_and_cashier_can_print_the_deposit(): void
    {
        $bendahara = $this->createUser('bendahara');
        $ok = $this->cashTransaction([$this->tbIds[0]], 750000);
        $orphan = $this->cashTransaction([self::MISSING], 200000);

        $deposit_id = DB::table('cash_deposit')->insertGetId([
            'number' => 'SK-UJI-0001', 'dates' => date('Y-m-d'), 'transactions' => json_encode([(string) $ok->id, (string) $orphan->id]),
            'total' => 950000, 'status' => 0, 'branch_id' => self::BRANCH_ID, 'created_by' => $this->kasir->id,
        ]);
        $enc = Crypt::encrypt($deposit_id);

        $html = $this->actingAs($bendahara)->get(route('finance.transaction.verify.cash', $enc))->assertOk()->getContent();
        $this->assertStringContainsString($orphan->number, $html);

        $pdf = $this->actingAs($this->kasir)->get(route('finance.transaction.print.cash', $enc))->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('Content-Type'));
    }
}
