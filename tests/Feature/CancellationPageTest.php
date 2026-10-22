<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Enums\TransactionStatus;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Feature\Concerns\CancellationFixtures;

/**
 * Bagian C fitur Pembatalan Transaksi: controller, route, menu, halaman admin dan riwayat.
 */
class CancellationPageTest extends ModuleAccessTestCase
{
    use CancellationFixtures;

    private const REASON = 'Salah transfer oleh orang tua';

    private function pegawai(array $modules): User
    {
        $user = $this->createUser('pegawai', $modules);
        $this->createEmployee($user);

        return $user;
    }

    private function enc(int $id): string
    {
        return Crypt::encrypt($id);
    }

    private function table(User $user, array $search = [], array $extra = []): array
    {
        return $this->actingAs($user)->postJson(route('finance.cancellation.datatable'), $extra + [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'start_date' => date('Y-m').'-01', 'end_date' => date('Y-m-t'),
            'search' => ['value' => $search[0] ?? ''],
        ])->assertOk()->json();
    }

    private function statusOf(int $id): string
    {
        return (string) DB::table('transaction')->where('id', $id)->value('status');
    }

    /** @return array<string, array{0: string, 1: string}> */
    private function routes(int $id): array
    {
        return [
            'index' => ['get', route('finance.cancellation.index')],
            'history' => ['get', route('finance.cancellation.history')],
            'datatable' => ['post', route('finance.cancellation.datatable')],
            'datatable.history' => ['post', route('finance.cancellation.datatable.history')],
            'preview' => ['post', route('finance.cancellation.preview', $this->enc($id))],
            'store' => ['post', route('finance.cancellation.store', $this->enc($id))],
        ];
    }

    #[RunInSeparateProcess]
    public function test_access_is_limited_to_admin_and_non_cashier_module_holders(): void
    {
        $id = $this->payBillsByTransfer();
        $allowed = [$this->admin, $this->pegawai([Modules::CancelTransaction])];
        $denied = [
            'pegawai tanpa modul' => $this->pegawai([]),
            'pegawai modul 11 + kasir' => $this->pegawai([Modules::CancelTransaction, Modules::Kasir]),
            'pegawai modul 11 + kasir unit' => $this->pegawai([Modules::CancelTransaction, Modules::KasirUnit]),
            'kasir (modul 11 diberikan)' => $this->createUser('kasir', [Modules::CancelTransaction]),
            'ortu' => $this->ortu,
        ];

        foreach ($allowed as $user) {
            foreach (['index', 'history'] as $name) {
                $this->actingAs($user)->get($this->routes($id)[$name][1])->assertOk();
            }
            $this->actingAs($user)->postJson($this->routes($id)['preview'][1])->assertOk()->assertJsonPath('data.can_cancel', true);
        }

        foreach ($denied as $label => $user) {
            foreach ($this->routes($id) as $name => [$method, $url]) {
                $this->actingAs($user)->{$method}($url, $method === 'post' ? ['reason' => self::REASON] : [])
                    ->assertRedirect(route('base'), "{$label} → {$name}");
            }
        }

        $this->assertSame(TransactionStatus::Paid->value, $this->statusOf($id));
        $this->assertSame(0, DB::table('transaction_cancellation')->where('id_transaction', $id)->count());
    }

    public function test_every_route_keeps_all_three_access_layers(): void
    {
        $names = ['index', 'history', 'datatable', 'datatable.history', 'preview', 'store'];

        foreach ($names as $name) {
            $middleware = app('router')->getRoutes()->getByName("finance.cancellation.{$name}")->gatherMiddleware();

            $this->assertContains('role:admin,pegawai', $middleware, $name);
            $this->assertContains('accessRights:11', $middleware, $name);
            $this->assertContains(\App\Http\Middleware\CancellationAccess::class, $middleware, $name);
        }
    }

    #[RunInSeparateProcess]
    public function test_flow_search_preview_store_history(): void
    {
        $id = $this->payBillsByTransfer();
        $this->payTopup();
        $before = $this->state();

        $this->assertSame(1, $this->table($this->admin, ['2489'], ['type' => '1'])['recordsFiltered']);
        $this->assertSame(0, $this->table($this->admin, ['zzz-ngawur'])['recordsFiltered']);

        $row = $this->table($this->admin, ['2489'], ['type' => '1'])['data'][0];
        $this->assertFalse($row['cancelled']);
        $this->assertSame('Santri Uji Batal', $row['student']['name']);
        $this->assertSame('Ortu Uji Batal', $row['parent']['name']);

        $preview = $this->actingAs($this->admin)->postJson($this->routes($id)['preview'][1])->assertOk()->json('data');
        $this->assertTrue($preview['can_cancel']);
        $this->assertSame('transfer', $preview['refund']['mode']);
        $this->assertCount(2, $preview['details']['bills']);
        $this->assertEquals($before, $this->state());

        $store = $this->routes($id)['store'][1];
        foreach (['', 'pendek', 'dua kata'] as $reason) {
            $this->actingAs($this->admin)->postJson($store, ['reason' => $reason])->assertStatus(422)->assertJsonPath('status', false);
        }
        $this->assertEquals($before, $this->state());
        $this->assertSame(TransactionStatus::Paid->value, $this->statusOf($id));

        $this->actingAs($this->admin)->postJson($store, ['reason' => self::REASON])->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', __('message.cancel_transaction_success', ['number' => $row['number']]));
        $this->assertSame(TransactionStatus::Cancelled->value, $this->statusOf($id));

        $this->actingAs($this->admin)->postJson($store, ['reason' => self::REASON])->assertStatus(422);

        $this->assertTrue($this->table($this->admin, ['2489'], ['type' => '1'])['data'][0]['cancelled']);

        $history = $this->actingAs($this->admin)->postJson(route('finance.cancellation.datatable.history'), [
            'draw' => 1, 'start' => 0, 'length' => 10, 'start_date' => date('Y-m').'-01', 'end_date' => date('Y-m-t'),
            'search' => ['value' => 'Salah transfer'],
        ])->assertOk()->json();
        $this->assertSame(1, $history['recordsFiltered']);
        $this->assertSame(self::REASON, $history['data'][0]['reason']);
        $this->assertSame($this->admin->name, $history['data'][0]['cancelled_by']);
        $this->assertSame('Santri Uji Batal', $history['data'][0]['student']['name']);
        $this->assertNotEmpty($history['data'][0]['cancelled_at']);
    }

    #[RunInSeparateProcess]
    public function test_other_branch_transaction_is_not_found(): void
    {
        $id = $this->payBillsByTransfer();
        DB::table('transaction')->where('id', $id)->update(['branch_id' => self::BRANCH_ID + 1]);

        $this->actingAs($this->admin)->postJson($this->routes($id)['preview'][1])->assertNotFound();
        $this->actingAs($this->admin)->postJson($this->routes($id)['store'][1], ['reason' => self::REASON])->assertNotFound();
        $this->assertSame(0, $this->table($this->admin, ['2489'])['recordsFiltered']);
        $this->assertSame(TransactionStatus::Paid->value, $this->statusOf($id));
    }

    #[RunInSeparateProcess]
    public function test_inline_scripts_parse(): void
    {
        $this->payBillsByTransfer();

        foreach (['finance.cancellation.index', 'finance.cancellation.history'] as $name) {
            $html = $this->actingAs($this->admin)->get(route($name))->assertOk()->getContent();
            $this->assertInlineScriptsParse($html);
        }
    }

    #[RunInSeparateProcess]
    public function test_menu_is_shown_only_to_allowed_users(): void
    {
        $link = route('finance.cancellation.index');

        $this->actingAs($this->admin)->get(route('finance.cancellation.index'))->assertSee($link, false);
        $this->actingAs($this->pegawai([Modules::CancelTransaction]))->get(route('finance.cancellation.index'))->assertSee($link, false);

        $kasir_modul = $this->pegawai([Modules::Kasir, Modules::CancelTransaction]);
        $this->actingAs($kasir_modul)->get(route('dashboard.index'))->assertDontSee($link, false);
        $this->actingAs($this->pegawai([]))->get(route('dashboard.index'))->assertDontSee($link, false);
    }
}
