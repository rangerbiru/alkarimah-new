# 2026-10-22: Pembatalan Transaksi (A-D), catatan deploy gabungan

Fitur: admin (atau pegawai bermodul 11 yang bukan kasir) membatalkan **transaksi masuk lunas buatan orang tua** (tagihan TR via
BNI/BSI/saldo, setoran tabungan ST, top-up saldo TS via BNI/BSI) lewat entri pembalik. Baris lama tidak diubah.

## Commit
| Commit | Bagian |
|---|---|
| `4ce4b0e` | A: tabel `transaction_cancellation`, kolom `reversal_of` + indeks, modul 11, enum, config |
| `d6dde02` | B: service `TransactionCancellation` + reverser TR/ST/TS, pesan |
| `0f5c26e` | C: controller, route, menu, halaman admin dan riwayat |
| `b58c95f` | perbaikan B: log update siswa dengan `old.id_class` null tidak lagi dianggap pindah kelas (blokir palsu pada santri yang tabungannya berubah setelah bayar) |
| (commit D) | D: tampilan Dibatalkan di sisi ortu, riwayat, kuitansi, dashboard |

`origin/main..main` hanya berisi commit fitur ini (tidak ada commit lain yang belum di-push yang menyentuh file yang sama).
Patch hanya-fitur: `git diff 4ce4b0e~1..HEAD -- . ':!tests' ':!docs' ':!.phpunit.result.cache'`.

## File per bagian (tanpa tests/)
| Bagian | File |
|---|---|
| A | 3 migration `2026_10_22_00000{1,2,3}_*`, `app/Enums/{TransactionStatus,SavingsMutationFlag}.php`, `app/Constants/Modules.php`, `config/transaction-cancellation.php` |
| B | `app/Services/TransactionCancellation.php`, `CancellationException.php`, `app/Services/Cancellation/*`, `lang/{id,en}/{message,string}.php` |
| C | `CancellationController`, `CancellationAccess`, `routes/{finance,breadcrumbs}.php`, `views/backend/finance/cancellation/*`, `sidebar-menu-cancellation`, `sidebar-admin`, `sidebar-pegawai-modules`, `lang/*` |
| D | lihat `2026-10-22-d-pembatalan-transaksi-tampilan-ortu.md` |

## Urutan deploy
1. **Backup** database.
2. **Pemeriksaan (SELECT saja):** `SELECT id, name FROM module WHERE id = 11;` harus kosong atau bernama "Pembatalan Transaksi"
   (ID 5 sudah dipakai Kepala Sekolah; migration melempar error bila ID 11 dipakai modul lain).
3. Pasang file (A-D sekaligus).
4. `php artisan migrate --pretend`, periksa, lalu `php artisan migrate --force` (3 migration).
5. `php artisan config:clear && php artisan route:clear && php artisan view:clear`; muat ulang PHP-FPM bila perlu.
6. Beri modul "Pembatalan Transaksi" ke pegawai yang berwenang lewat Hak Akses.

## Peringatan
- **Jangan menjalankan `php artisan execute:start` lagi**: rutinitas backfill-nya tidak mengenal status 3 / flag 3.
- Pemegang modul Kasir (7) / Kasir Unit (8) tidak bisa memakai fitur ini walau diberi modul 11 (pemisahan tugas).
- Pembayaran cicilan (split bill): pembatalan mengembalikan baris yang dibayar ke belum lunas **tanpa menggabungkannya** kembali
  dengan baris sisa.

## Verifikasi tanpa mengubah data
Buka menu Keuangan > Pembatalan Transaksi (admin): tabel dan riwayat tampil. Buka satu transaksi dengan "Batalkan" lalu **tutup dialog
tanpa mengisi alasan**. Jangan membatalkan transaksi sungguhan hanya untuk mencoba.

## Hasil test (DB `app_alkarimah_testing`)
Lihat laporan bagian D (jumlah test dan assertion).

## Rollback
- Belum ada pembatalan: revert kode, lalu `php artisan migrate:rollback --step=3`.
- Sudah ada pembatalan: **jangan rollback skema**. Tutup menu dengan revert C, biarkan D terpasang (agar ortu tetap melihat
  "Dibatalkan"), atau pulihkan dari backup.
