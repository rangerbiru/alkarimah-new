<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\TransactionController;
use App\Models\SavingsMutation;
use App\Models\Transaction;
use App\Services\TransactionCancellation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionMethod;
use Tests\Feature\Concerns\CancellationFixtures;

/**
 * Bagian D fitur Pembatalan Transaksi: tampilan "Dibatalkan" di sisi orang tua, riwayat, kuitansi, dashboard.
 */
class CancellationDisplayTest extends ModuleAccessTestCase
{
    use CancellationFixtures;

    private const REASON = "Salah transfer oleh orang tua\n<b>baris kedua</b>";

    private function cancel(int $id): void
    {
        $this->actingAs($this->admin);
        (new TransactionCancellation)->cancel($id, self::REASON, $this->admin);
    }

    private function enc(int $id): string
    {
        return Crypt::encrypt($id);
    }

    /** ID hasil dekripsi segmen terakhir path (ID terenkripsi berbeda setiap kali dibuat). */
    private function idIn(string $url): int
    {
        return (int) Crypt::decrypt(urldecode(basename(parse_url($url, PHP_URL_PATH))));
    }

    private function paidBills(Transaction $t): array
    {
        $m = new ReflectionMethod(TransactionController::class, 'paidBills');

        return $m->invoke($this->app->make(TransactionController::class), $t)
            ->map(fn ($b) => [$b->id, (float) $b->subtotal, (float) $b->discount, (float) $b->total])->all();
    }

    private function billsDetail(Transaction $t): array
    {
        return collect($t->fresh()->bills_detail)->map(fn ($b) => [$b->id, $b->name, (float) $b->discount, (float) $b->total])->all();
    }

    public function test_badge_and_labels(): void
    {
        $id = $this->payBillsByTransfer();
        $this->assertStringContainsString(__('label.paid_off'), Transaction::find($id)->status_badge);

        $st = $this->paySavingsDeposit();
        $this->cancel($id);
        $this->cancel($st);

        $badge = Transaction::find($id)->status_badge;
        $this->assertStringContainsString(__('label.cancelled'), $badge);
        $this->assertStringNotContainsString(__('label.not_paid'), $badge);
        $this->assertTrue(Transaction::find($id)->is_cancelled);

        $m = SavingsMutation::withoutGlobalScopes()->where('id_transaction', $st)->where('flag', '3')->first();
        $this->assertNotNull($m);
        $this->assertSame(__('label.savings_deposit_cancellation'), $m->flag_name);
        $this->assertTrue($m->is_cancellation);
        $this->assertFalse($m->is_deposit);
        $this->assertNotSame(__('label.savings_withdrawal'), $m->flag_name);
    }

    public function test_discounted_bill_transaction_is_identical_after_cancel(): void
    {
        $this->addDiscount();
        $id = $this->payBillsByTransfer(true);
        $paid = $this->paidBills(Transaction::find($id));
        $detail = $this->billsDetail(Transaction::find($id));

        $this->cancel($id);

        $this->assertSame($paid, $this->paidBills(Transaction::find($id)));
        $this->assertSame($detail, $this->billsDetail(Transaction::find($id)));
        $this->assertContains(50000.0, array_column($detail, 2));
        $this->assertContains(50000.0, array_column($this->paidBills(Transaction::find($id)), 2));
    }

    #[RunInSeparateProcess]
    public function test_parent_pages_for_bill_savings_and_topup(): void
    {
        $tr = $this->payBillsByTransfer();
        $st = $this->paySavingsDeposit();
        $ts = $this->payTopup();
        DB::table('parent')->where('id', $this->parentId)->update(['balance' => 5000000]);
        foreach ([$tr, $st, $ts] as $id) {
            $this->cancel($id);
        }

        $origin_ts = (int) DB::table('topup_history')->where('id_transaction', $ts)->whereNull('reversal_of')->orderBy('id')->value('id');

        // waiting dialihkan ke detail
        $r = $this->actingAs($this->ortu)->get(route('finance.payment.waiting', $this->enc($tr)))->assertRedirect();
        $this->assertSame(route('finance.payment.show', 0), preg_replace('#/[^/]+$#', '/0', $r->headers->get('Location')));
        $this->assertSame($tr, $this->idIn($r->headers->get('Location')));

        $r = $this->actingAs($this->ortu)->get(route('finance.savings.waiting', $this->enc($st)))->assertRedirect();
        $this->assertSame($st, $this->idIn($r->headers->get('Location')));
        $this->assertStringContainsString('/savings/', $r->headers->get('Location'));

        $r = $this->actingAs($this->ortu)->get(route('finance.balance.waiting', $this->enc($ts)))->assertRedirect();
        $this->assertSame($origin_ts, $this->idIn($r->headers->get('Location')));

        // detail: badge, alasan (di-escape), catatan, tanpa nama admin
        $pages = [
            route('finance.payment.show', $this->enc($tr)),
            route('finance.savings.show', $this->enc($st)),
            route('finance.balance.show', $this->enc($origin_ts)),
        ];
        foreach ($pages as $url) {
            $html = $this->actingAs($this->ortu)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString(__('label.cancelled'), $html);
            $this->assertStringContainsString(__('label.cancellation_reason'), $html);
            $this->assertStringContainsString('Salah transfer oleh orang tua', $html);
            $this->assertStringContainsString('&lt;b&gt;baris kedua&lt;/b&gt;', $html);
            $this->assertStringNotContainsString('<b>baris kedua</b>', $html);
            $this->assertStringContainsString(__('string.cancelled_parent_note'), $html);
            $this->assertStringNotContainsString($this->admin->name, $html);
        }

        // riwayat tidak menautkan ke waiting
        $payment = $this->actingAs($this->ortu)->postJson(route('finance.payment.get.history'), ['page' => 1])->assertOk()->json('data.list');
        $this->assertStringNotContainsString('/waiting/', $payment);
        $this->assertStringContainsString(__('label.cancelled'), $payment);

        $savings = $this->actingAs($this->ortu)->postJson(route('finance.savings.get.history'), ['page' => 1])->assertOk()->json('data.list');
        $this->assertSame(2, substr_count($savings, 'data-flag="1"'));
        $this->assertSame(0, substr_count($savings, 'data-flag="2"'));
        $this->assertStringNotContainsString('/waiting/', $savings);
        $this->assertStringContainsString(__('string.cancellation_entry'), $savings);

        $balance = $this->actingAs($this->ortu)->postJson(route('finance.balance.get.history'), ['page' => 1])->assertOk()->json('data.list');
        $this->assertStringNotContainsString('/waiting/', $balance);
        preg_match_all('/data-idh="([^"]+)"/', $balance, $m);
        $originals = DB::table('topup_history')->whereNull('reversal_of')->pluck('id')->all();
        $reversals = DB::table('topup_history')->whereNotNull('reversal_of')->pluck('id')->all();
        $this->assertNotEmpty($m[1]);
        $this->assertNotEmpty(array_intersect(array_map(fn ($i) => (int) Crypt::decrypt(html_entity_decode($i)), $m[1]), $originals));
        foreach ($m[1] as $idh) {
            $id = (int) Crypt::decrypt(html_entity_decode($idh));
            $this->assertContains($id, $originals);
            $this->assertNotContains($id, $reversals);
        }
    }

    #[RunInSeparateProcess]
    public function test_cashier_receipt_and_detail(): void
    {
        $this->addDiscount();
        $id = $this->payBillsByTransfer(true);
        $this->cancel($id);

        $pdf = $this->actingAs($this->kasir)->get(route('finance.transaction.print', $this->enc($id)));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('Content-Type'));

        $html = $this->actingAs($this->kasir)->get(route('finance.transaction.bill.show', $this->enc($id)))->assertOk()->getContent();
        $this->assertStringContainsString(__('label.cancelled'), $html);
        $this->assertStringContainsString('SPP', $html);
    }

    #[RunInSeparateProcess]
    public function test_parent_dashboard(): void
    {
        $tr = $this->payBillsByTransfer();
        $st = $this->paySavingsDeposit();
        $this->payTopup();
        $this->cancel($tr);

        $html = $this->actingAs($this->ortu)->get(route('dashboard.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, '750.000'));
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'badge bg-secondary text-uppercase">'.__('label.cancelled')));
        $this->assertStringContainsString('text-decoration-line-through', $html);

        preg_match_all('#<a href="([^"]+)" class="d-block text-reset text-decoration-none">#', $html, $m);
        $targets = array_map(fn ($u) => [str_contains($u, '/payment/') ? 'payment' : 'savings', $this->idIn($u)], $m[1]);
        $this->assertEqualsCanonicalizing([['payment', $tr], ['payment', $tr], ['savings', $st]], $targets);
    }

    #[RunInSeparateProcess]
    public function test_parent_history_inline_scripts_parse(): void
    {
        foreach (['finance.payment.history', 'finance.savings.history', 'finance.balance.history'] as $route) {
            $html = $this->actingAs($this->ortu)->get(route($route))->assertOk()->getContent();
            $this->assertStringContainsString('status_cancelled', $html);
            $this->assertInlineScriptsParse($html);
        }
    }
}
