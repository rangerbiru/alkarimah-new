# 2026-10-22 (Bagian D): Pembatalan Transaksi, tampilan "Dibatalkan" di sisi orang tua, riwayat, kuitansi

**Tanpa migration baru.** Tergantung A-C. Deploy A-D sekaligus (lihat `2026-10-22-pembatalan-transaksi-gabungan.md`).

## Yang berubah
- `Transaction`: `statusBadge` (match: Lunas / Dibatalkan abu-abu / Belum Lunas), `isCancelled`, `cancellation`, `cancellationReason`,
  `cancelledBills` (snapshot, `keyBy('id')`), dan cabang `is_cancelled` di `billsDetail` (tagihan sudah direset, diskon dan nominal
  diambil dari snapshot).
- `SavingsMutation`: `flagName` (match) dan `isCancellation`.
- Halaman *waiting* (payment / savings / balance) mengalihkan transaksi dibatalkan ke halaman detail (saldo: ke entri `topup_history` asli).
- Detail ortu (`payment/show`, `savings/show`, `balance/show`): kotak alasan pembatalan (di-escape) dan catatan; nama pembatal tidak tampil.
- Riwayat ortu (tagihan, tabungan, saldo): transaksi dibatalkan tertaut ke detail; baris pembalik tabungan dan saldo diberi "Entri pembatalan",
  baris pembalik saldo menaut ke entri asli.
- Kasir: `TransactionController::paidBills()` (dipakai `print` dan `showBill`) memakai snapshot untuk transaksi dibatalkan; kuitansi PDF
  diberi cap DIBATALKAN.
- Dashboard ortu: transaksi dibatalkan ikut tampil (nominal dicoret, badge DIBATALKAN), kartu TR / ST menaut ke detail.

## Perbaikan kecil yang ikut
- `savings/history-parent.blade.php`: `let url` ada di dalam blok `if`, sehingga klik kartu setoran bisa menghasilkan ReferenceError.
  Deklarasinya dipindah ke luar blok (perlu agar kartu setoran dibatalkan bisa diklik).

## File
| File | Perubahan |
|---|---|
| `app/Models/Transaction.php`, `app/Models/SavingsMutation.php` | atribut baru |
| `app/Http/Controllers/Finance/{Payment,Savings,Balance,Transaction}Controller.php` | guard waiting, `reversal_of` di riwayat saldo, `paidBills()` |
| `app/Http/Controllers/DashboardController.php` | dashboard ortu |
| `resources/views/backend/finance/{payment,savings,balance}/*` | detail dan riwayat |
| `resources/views/backend/finance/cancellation/parent-note.blade.php` | baru |
| `resources/views/backend/finance/transaction/pdf-receipt.blade.php` | cap DIBATALKAN |
| `resources/views/backend/dashboard/orang-tua.blade.php` | dashboard ortu |
| `lang/{id,en}/{label,string}.php` | kunci baru |

## Verifikasi
`CancellationDisplayTest` (6 test) dan `CancellationSchemaTest` (tambahan: kunci lang tidak ganda).

## Rollback
Revert commit ini. Transaksi berstatus 3 yang sudah ada akan tampil sebagai "Belum Lunas" di sisi ortu bila D dicabut.
