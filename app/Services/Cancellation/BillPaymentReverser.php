<?php

namespace App\Services\Cancellation;

use App\Constants\EducationLevel;
use App\Enums\BillPeriod;
use App\Enums\TransactionMethod;
use App\Helpers\Common;
use Illuminate\Support\Facades\DB;

/**
 * TR: pembayaran tagihan via transfer atau saldo. Membalik transaction_bill, diskon sekali-pakai
 * per periode, dan semua tabel report yang ditulis updateReport / BillPaid / StudentReport.
 */
class BillPaymentReverser extends BaseReverser
{
    /** Urutan tabel = urutan kunci baris (konsisten di semua jalur). */
    private const REPORT_TABLES = ['report_bill', 'report_bill_type', 'report_bill_method', 'report_bill_class', 'report_student'];

    public function dependsOnClass(): bool
    {
        return true;
    }

    public function plan(Context $ctx): void
    {
        $ctx->plan = ['bills' => [], 'reports' => [], 'discounts' => [], 'class' => null];
        $t = $ctx->transaction;

        $this->checkLedger($ctx);

        $bills = $this->loadBills($ctx);

        if ($bills === null) {
            return;
        }

        $class = $this->classOf($ctx);

        if ($class === null) {
            return;
        }

        $masters = $this->loadMasters($ctx, $bills);

        if ($masters === null) {
            return;
        }

        [$billRows, $typeRows] = $masters;

        $aggregate = [];
        $date = substr((string) $t->paid_at, 0, 10);

        foreach ($bills as $b) {
            $bill = $billRows[$b->id_bill];
            $type = $typeRows[$bill->id_type];
            $gross = (float) $b->subtotal;
            $net = (float) $b->total;

            $this->add($aggregate, 'report_bill', "{$bill->id_year}|{$bill->id_type}|{$class['level']}",
                ['id_year' => $bill->id_year, 'id_type' => $bill->id_type, 'level' => $class['level']],
                ['paid' => -$gross, 'remaining' => $gross]);
            $this->add($aggregate, 'report_bill_type', "{$bill->id_year}|{$bill->id_type}|{$date}|{$class['level']}",
                ['id_year' => $bill->id_year, 'id_type' => $bill->id_type, 'dates' => $date, 'level' => $class['level']],
                ['quantity' => -1, 'total' => -$gross]);
            $this->add($aggregate, 'report_bill_method', "{$bill->id_year}|{$date}|{$t->payment_method}",
                ['id_year' => $bill->id_year, 'dates' => $date, 'method' => (int) $t->payment_method],
                ['quantity' => -1, 'total' => -$net]);
            $this->add($aggregate, 'report_bill_class', "{$bill->id_year}|{$date}|{$class['id']}",
                ['id_year' => $bill->id_year, 'dates' => $date, 'id_class' => $class['id']],
                ['quantity' => -1, 'total' => -$net]);

            $ctx->plan['bills'][] = [
                'id' => (int) $b->id,
                'id_bill' => (int) $b->id_bill,
                'name' => $bill->name,
                'period' => $this->periodLabel($type, $b),
                'subtotal' => $gross,
                'discount' => (float) $b->discount,
                'total' => $net,
                'discount_id' => $b->discount_id ? (int) $b->discount_id : null,
            ];
        }

        $paid = (float) $t->subtotal - (float) $t->discount;
        $this->add($aggregate, 'report_student', (string) $t->id_student, ['id_student' => $t->id_student],
            ['bill_paid' => -$paid, 'bill_not_paid' => $paid]);

        $ctx->plan['class'] = ['id' => $class['id'], 'name' => $class['name'], 'level' => $class['level']];

        $this->planReports($ctx, $aggregate);
        $this->planDiscounts($ctx, $bills, $billRows, $typeRows);
    }

    public function apply(Context $ctx, callable $step): void
    {
        $actor = $ctx->actor->id;

        DB::table('transaction_bill')->whereIn('id', array_column($ctx->plan['bills'], 'id'))->update([
            'status' => 0,
            'id_transaction' => null,
            'discount' => 0,
            'discount_id' => null,
            'total' => DB::raw('subtotal'),
            'updated_at' => now(),
            'updated_by' => $actor,
        ]);
        $step('bills');

        foreach (collect($ctx->plan['discounts'])->groupBy('id') as $id => $entries) {
            $row = $ctx->query('bill_discount')->where('id', $id)->first();
            $applies = json_decode((string) $row->applies_to, true);

            foreach ($entries as $entry) {
                $applies[$entry['period']] = $entry['after'];
            }

            DB::table('bill_discount')->where('id', $id)->update([
                'applies_to' => json_encode((object) $applies),
                'status' => 1,
                'updated_at' => now(),
                'updated_by' => $actor,
            ]);
        }
        $step('discounts');

        foreach ($ctx->plan['reports'] as $report) {
            DB::table($report['table'])->where('id', $report['id'])->incrementEach($report['fields'], ['updated_at' => now()]);
        }
        $step('reports');
    }

    public function describe(Context $ctx): array
    {
        $plan = $ctx->plan + ['bills' => [], 'reports' => [], 'discounts' => [], 'class' => null];

        return [
            'bills' => $plan['bills'],
            'class' => $plan['class'],
            'reports' => collect($plan['reports'])->countBy('table')->all(),
            'discounts' => count($plan['discounts']),
        ];
    }

    private function checkLedger(Context $ctx): void
    {
        $t = $ctx->transaction;

        if ($ctx->savings->isNotEmpty()) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'ada mutasi tabungan pada pembayaran tagihan']);
        }

        if ((string) $t->payment_method !== TransactionMethod::TopupBalance->value) {
            $this->checkUniqueCodeRow($ctx, $ctx->topups);

            return;
        }

        $row = $ctx->topups->first();

        if ((float) $t->unique_code != 0) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'pembayaran saldo tidak boleh punya kode unik']);
        }

        if ($ctx->topups->count() !== 1 || ! $this->same($row->credit, $t->total) || (float) $row->debit != 0) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'pemotongan saldo orang tua tidak ditemukan atau nominalnya tidak sesuai']);
        }
    }

    /** @return \Illuminate\Support\Collection<int, object>|null null bila daftar tagihan tidak valid */
    private function loadBills(Context $ctx)
    {
        $t = $ctx->transaction;
        $ids = json_decode((string) $t->bills, true);

        if (! is_array($ids) || $ids === [] || ! array_is_list($ids) || array_filter($ids, fn ($id) => ! is_numeric($id))) {
            $ctx->block('bills', 'cancel_bills', ['detail' => 'daftar tagihan transaksi kosong atau tidak valid']);

            return null;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $bills = $ctx->query('transaction_bill')->whereIn('id', $ids)->orderBy('id')->get();
        $problems = [];

        if ($bills->count() !== count($ids)) {
            $problems[] = 'ada tagihan yang tidak ditemukan';
        }

        $linked = DB::table('transaction_bill')->where('id_transaction', $t->id)->count();

        if ($linked !== count($ids)) {
            $problems[] = "jumlah tagihan tertaut ({$linked}) berbeda dari daftar transaksi (".count($ids).')';
        }

        foreach ($bills as $b) {
            if ((int) $b->id_transaction !== (int) $t->id) {
                $problems[] = "tagihan #{$b->id} tidak tertaut ke transaksi ini";
            }
            if ((int) $b->status !== 1) {
                $problems[] = "tagihan #{$b->id} belum lunas";
            }
            if ((int) $b->id_student !== (int) $t->id_student) {
                $problems[] = "tagihan #{$b->id} milik santri lain";
            }
            if (! $this->same($b->total, (float) $b->subtotal - (float) $b->discount)) {
                $problems[] = "total tagihan #{$b->id} tidak sama dengan subtotal dikurangi diskon";
            }
        }

        $subtotal = $bills->sum('subtotal');
        $discount = $bills->sum('discount');

        if (! $this->same($subtotal, $t->subtotal)) {
            $problems[] = 'subtotal transaksi berbeda dari jumlah subtotal tagihan';
        }
        if (! $this->same($discount, $t->discount)) {
            $problems[] = 'diskon transaksi berbeda dari jumlah diskon tagihan';
        }
        if (! $this->same($t->total, (float) $t->subtotal - (float) $t->discount + (float) $t->unique_code)) {
            $problems[] = 'total transaksi tidak sama dengan subtotal dikurangi diskon ditambah kode unik';
        }

        if ($problems !== []) {
            $ctx->block('bills', 'cancel_bills', ['detail' => implode('; ', array_unique($problems))]);

            return null;
        }

        return $bills;
    }

    /** @return array{id: int, name: string, level: int}|null */
    private function classOf(Context $ctx): ?array
    {
        $class = $ctx->student ? $ctx->query('class')->where('id', $ctx->student->id_class)->first() : null;
        $level = $class ? (EducationLevel::Classes[$class->level_education.'.'.$class->level_class] ?? null) : null;

        if ($level === null) {
            $this->rowProblem($ctx, 'class', (string) ($ctx->student->id_class ?? '-'), 'tidak ditemukan atau jenjangnya tidak dikenali');

            return null;
        }

        return ['id' => (int) $class->id, 'name' => $class->name, 'level' => $level];
    }

    /** @return array{0: array<int, object>, 1: array<int, object>}|null */
    private function loadMasters(Context $ctx, $bills): ?array
    {
        $billRows = DB::table('bill')->whereIn('id', $bills->pluck('id_bill')->unique())->get()->keyBy('id')->all();
        $typeRows = DB::table('bill_type')->whereIn('id', collect($billRows)->pluck('id_type')->unique())->get()->keyBy('id')->all();

        foreach ($bills as $b) {
            if (! isset($billRows[$b->id_bill]) || ! isset($typeRows[$billRows[$b->id_bill]->id_type])) {
                $ctx->block('bills', 'cancel_bills', ['detail' => "tagihan induk atau jenis tagihan #{$b->id} tidak ditemukan"]);

                return null;
            }
        }

        return [$billRows, $typeRows];
    }

    private function periodLabel(object $type, object $b): string
    {
        return match ((string) $type->period) {
            BillPeriod::Monthly->value => Common::monthFormat($b->months).' '.$b->years,
            BillPeriod::Semiannual->value => 'Semester '.$b->semester,
            default => '',
        };
    }

    /** Menjumlahkan perubahan untuk baris laporan yang sama. */
    private function add(array &$aggregate, string $table, string $key, array $where, array $fields): void
    {
        $entry = &$aggregate[$table][$key];
        $entry['where'] = $where;

        foreach ($fields as $field => $amount) {
            $entry['fields'][$field] = ($entry['fields'][$field] ?? 0) + $amount;
        }
    }

    private function planReports(Context $ctx, array $aggregate): void
    {
        foreach (self::REPORT_TABLES as $table) {
            $entries = $aggregate[$table] ?? [];
            ksort($entries);

            foreach ($entries as $key => $entry) {
                $query = $ctx->query($table);

                foreach ($entry['where'] as $column => $value) {
                    $query->where($column, $value);
                }

                $rows = $query->orderBy('id')->get();

                if ($rows->count() > 1 && $table !== 'report_student') {
                    $rows = $rows->where('branch_id', $ctx->transaction->branch_id)->values();
                }

                if ($rows->count() !== 1) {
                    $this->rowProblem($ctx, $table, $key, $rows->isEmpty() ? 'tidak ditemukan' : 'ditemukan lebih dari satu');

                    continue;
                }

                foreach ($entry['fields'] as $field => $amount) {
                    if ($amount < 0 && (float) $rows[0]->{$field} + $amount < -0.005) {
                        $this->rowProblem($ctx, $table, $key, "akan menjadi minus pada kolom {$field} (sekarang {$rows[0]->{$field}}, dikurangi ".abs($amount).')');
                    }
                }

                $ctx->plan['reports'][] = ['table' => $table, 'id' => (int) $rows[0]->id, 'key' => $key, 'fields' => $entry['fields']];
            }
        }
    }

    /** Diskon periodik dikembalikan ke belum terpakai (0); diskon sekali atau yang sudah dihapus dilewati. */
    private function planDiscounts(Context $ctx, $bills, array $billRows, array $typeRows): void
    {
        foreach ($bills as $b) {
            if ((float) $b->discount <= 0 || ! $b->discount_id) {
                continue;
            }

            $discount = $ctx->query('bill_discount')->where('id', $b->discount_id)->first();
            $applies = $discount && $discount->deleted_at === null && ! empty($discount->applies_to)
                ? json_decode((string) $discount->applies_to, true)
                : null;

            if (! is_array($applies) || $applies === []) {
                continue;
            }

            $monthly = (string) $typeRows[$billRows[$b->id_bill]->id_type]->period === BillPeriod::Monthly->value;
            $period = $monthly ? sprintf('%04d-%02d', $b->years, $b->months) : (string) $b->semester;

            if (! array_key_exists($period, $applies)) {
                $ctx->block('bills', 'cancel_bills', ['detail' => "periode diskon {$period} tidak ada pada diskon #{$discount->id}"]);

                continue;
            }

            $ctx->plan['discounts'][] = ['id' => (int) $discount->id, 'period' => $period, 'before' => $applies[$period], 'after' => 0];
        }
    }

    private function rowProblem(Context $ctx, string $table, string $key, string $problem): void
    {
        $ctx->block('report_rows', 'cancel_report_rows', ['table' => $table, 'key' => $key, 'problem' => $problem]);
    }
}
