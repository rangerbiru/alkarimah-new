# 2026-10-22 (Bagian B): Pembatalan Transaksi, service dan reverser

**Tanpa migration baru. Belum ada route, menu, atau halaman yang memanggil service ini**, jadi memasang bagian B saja tidak
mengubah perilaku aplikasi. Tergantung bagian A (tabel, kolom `reversal_of`, enum `Cancelled`). Deploy A-D sekaligus.

## Cara kerja
`App\Services\TransactionCancellation` (`preview()` hanya membaca; `cancel()` menulis) membalik transaksi **lunas buatan orang
tua**: TR (BNI / BSI / Saldo), ST (BNI / BSI), TS (BNI / BSI). Tunai dan pengambilan tabungan tidak termasuk.
Satu `DB::transaction`, kunci berurutan: parent, transaction, student, transaction_bill, baris laporan. Entri lama tidak
diubah; saldo dikembalikan lewat entri pembalik (`topup_history` / `savings_mutation` flag 3 dengan `reversal_of`).
Tahap bernama (`checked`, `bills`, `discounts`, `reports`, `ledger`, `balances`, `status`) memanggil `step()`; test menimpanya
untuk menggagalkan proses di tengah jalan dan memastikan tidak ada yang tersisa.

| Jenis | Yang dibalik |
|---|---|
| TR | `transaction_bill` (status 0, `id_transaction` NULL, diskon 0, `total = subtotal`), diskon periodik (periode kembali 0, `status = 1`), `report_bill`, `report_bill_type`, `report_bill_method`, `report_bill_class`, `report_student`, kode unik di saldo ortu atau pemotongan saldo |
| ST | mutasi setoran tabungan santri, kode unik di saldo ortu |
| TS | penambahan saldo ortu |

Ditolak (bukan ditebak) bila: riwayat saldo atau tabungan tidak lengkap, tagihan tidak tertaut benar, baris laporan tidak
ditemukan atau akan minus, saldo ortu atau tabungan akan minus, sudah masuk setoran kas atau setoran kode unik, santri pindah
kelas setelah bayar (khusus TR), admin membatalkan transaksi anaknya sendiri, alasan kurang dari 3 kata atau 10 karakter.
Transaksi bulan lalu hanya mendapat peringatan (`month_open` mati).

## Penyimpangan dari rancangan (disetujui atau mengikuti kode Al Karimah)
- **Hak akses:** admin, atau pegawai pemegang modul 11 yang **tidak** memegang modul Kasir (7) / Kasir Unit (8). Role `kasir`
  dan role lain ditolak. (`TransactionCancellation::allowed()`)
- **Admin tidak boleh membatalkan transaksi anaknya sendiri:** memakai `CashierConflict::ownParentIds()` yang sudah ada
  (akun, lalu 10 digit terakhir nomor HP), bukan pemeriksaan baru.
- **Cakupan cabang:** transaksi di cabang lain dari pelaku dianggap tidak ditemukan.
- **Pembayaran cicilan (split bill) milik Al Karimah:** tagihan yang dibayar sebagian dipecah menjadi satu baris lunas (nominal
  cicilan) dan satu baris sisa. Membatalkannya mengembalikan baris yang dibayar ke belum lunas **tanpa menyatukannya kembali**
  dengan baris sisa; angka laporan tetap benar (yang dibalik persis yang dulu ditambahkan).
- **Level jenjang laporan** memakai pemetaan `App\Constants\EducationLevel::Classes` (smp.1 = 9).

## File yang berubah
| File | Perubahan |
|---|---|
| `app/Services/TransactionCancellation.php` | baru: `allowed`, `preview`, `cancel`, `step`, pemeriksaan, entri pembalik, snapshot, activity log |
| `app/Services/CancellationException.php` | baru: `blockers`, `codes()` |
| `app/Services/Cancellation/{Context,TransactionReverser,BaseReverser,BillPaymentReverser,SavingsDepositReverser,BalanceTopupReverser}.php` | baru |
| `config/transaction-cancellation.php` | tambah `reversers` |
| `lang/{id,en}/message.php` | 19 kunci `cancel_*` (`cancel_success` lama tidak disentuh; pesan sukses fitur ini `cancel_transaction_success`) |
| `lang/{id,en}/string.php` | `cancellation_ledger` |

## Urutan di server (nanti, saat deploy A-D)
Sama dengan catatan bagian A; tambahan: `php artisan config:clear` (kunci `reversers` baru) dan `php artisan optimize:clear` bila
cache lang ikut dipakai.

## Verifikasi (tidak mengubah data)
Belum ada UI. Satu-satunya cek yang aman di server adalah memastikan file terpasang dan `php artisan config:show transaction-cancellation`
memuat `reversers`. Jangan membatalkan transaksi sungguhan hanya untuk mencoba.
SELECT untuk melihat apa yang kelak bisa dibatalkan (hanya yang non-tunai dan lunas):
`SELECT id, number, flag, payment_method, status_deposit, status_deposit_code FROM transaction WHERE status = 1 AND payment_method <> 1;`

## Rollback
Revert commit bagian B. Tidak ada perubahan skema atau data.

## Hasil test (DB `app_alkarimah_testing`)
`TransactionCancellationTest`: 32 test, 247 assertion, hijau; bersama bagian A: 46 test, 322 assertion.
Pembayaran dalam fixture dijalankan lewat `TransactionController::updateStatus` (listener `BillPaid`, `StudentReport`,
`TopupUniqueCode`, `SavingsDepositMutation` yang asli) dan `PaymentController::store` untuk bayar pakai saldo.
Setelah dibatalkan, ringkasan data (tagihan, semua tabel laporan, saldo ortu, tabungan, diskon) sama persis dengan sebelum bayar.

Sabotase (lima, masing-masing membuat test gagal, lalu kode dikembalikan identik dengan aslinya):
| Sabotase | Test yang gagal |
|---|---|
| laporan tidak dibalik | 4 |
| saldo tidak disimpan | 6 |
| tanpa `DB::transaction` | 6 |
| rule `class_unchanged` dimatikan | 1 |
| tagihan tidak direset | 4 |
