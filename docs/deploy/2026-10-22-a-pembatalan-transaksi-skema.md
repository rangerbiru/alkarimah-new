# 2026-10-22 (Bagian A): Pembatalan Transaksi, skema, enum, config, modul 11

**Ada 3 migration. Belum ada menu atau halaman; belum ada perilaku yang berubah di aplikasi.** Bagian B sampai D menyusul.
Deploy dilakukan sekaligus setelah D; catatan gabungan A-D dibuat di akhir.

## Penyimpangan dari rancangan (disetujui)
- **Modul diberi ID 11, bukan 5.** Di Al Karimah ID 5 sudah dipakai modul "Kepala Sekolah" (ID 5-10 terisi).
  Semua rujukan "modul 5" di rancangan dibaca `Modules::CancelTransaction = 11`; route memakai `accessRights:11`.
- **Siapa yang boleh:** admin, dan pegawai bermodul 11 *kecuali* pemegang modul Kasir (7) atau Kasir Unit (8) serta role
  `kasir` lama (ditolak walau diberi modul 11). Aturannya dipasang di service (bagian B) dan di route (bagian C).
- Modul 11 ditambahkan ke kelompok "Tata Usaha" di halaman Hak Akses pegawai (`Modules::Groups['tu']`).
- Test `EmployeeRightsPageTest::test_rights_page_lists_all_modules_in_two_groups` (sebelumnya "ten") disesuaikan:
  jumlah checkbox modul 10 menjadi 11. Hanya itu perubahan pada test lama.

## Migration (urut)
1. `2026_10_22_000001_create_transaction_cancellation_table`: tabel `transaction_cancellation`
   (`UNIQUE(id_transaction)`, indeks `cancelled_at` dan `branch_id`).
2. `2026_10_22_000002_add_reversal_columns_and_indexes_for_cancellation`:
   - `topup_history.reversal_of` + indeks `topup_history_reversal_of_index`, `topup_history_id_transaction_index`;
   - `savings_mutation.reversal_of` + indeks `savings_mutation_reversal_of_index`,
     `savings_mutation_id_transaction_flag_index (id_transaction, flag)`;
   - indeks `transaction_bill_id_transaction_index`.
   `savings_mutation.id_transaction` polimorfik (flag 1 dan 3: `transaction.id`; flag 2: `savings_withdrawal.id`),
   jadi lookup harus ikut memfilter `flag`.
3. `2026_10_22_000003_add_cancel_transaction_module`: sisipkan modul 11 "Pembatalan Transaksi" bila kosong; lewati bila
   sudah bernama itu; ID dipakai modul lain berarti `RuntimeException` (tidak ada `INSERT IGNORE`). Tidak menambah
   `module_rights`, jadi hanya admin yang bisa memakai fitur sampai hak diberikan.

## File yang berubah
| File | Perubahan |
|---|---|
| `app/Enums/TransactionStatus.php` | `Cancelled = '3'` |
| `app/Enums/SavingsMutationFlag.php` | `Cancellation = '3'` |
| `app/Constants/Modules.php` | `CancelTransaction = 11`, masuk `Groups['tu']` |
| `config/transaction-cancellation.php` | baru: `scope`, `rules`, `reason` (`reversers` ditambah di bagian B) |
| `database/migrations/2026_10_22_00000{1,2,3}_*.php` | baru |

## Urutan di server (nanti, saat deploy A-D)
1. Backup database.
2. Pemeriksaan (SELECT saja): `SELECT id, name FROM module WHERE id = 11;` harus kosong (atau sudah bernama
   "Pembatalan Transaksi"). `SHOW TABLES LIKE 'transaction_cancellation';` harus kosong.
3. `php artisan migrate --pretend`, periksa SQL-nya, lalu `php artisan migrate --force`.
   Jalankan saat sepi: indeks memindai `transaction_bill`, `topup_history`, `savings_mutation`.
4. `php artisan config:clear` (ada file config baru).

## Verifikasi (tidak mengubah data)
- `SHOW COLUMNS FROM topup_history LIKE 'reversal_of';` dan hal yang sama untuk `savings_mutation`.
- `SELECT id, name FROM module WHERE id = 11;` dan `SELECT COUNT(*) FROM module_rights WHERE id_module = 11;` (hasil 0).
- Admin membuka Hak Akses pegawai: muncul "Pembatalan Transaksi" di kelompok Tata Usaha, tidak tercentang.

## Rollback
Aman **hanya bila belum ada transaksi yang dibatalkan** (`SELECT COUNT(*) FROM transaction_cancellation;` hasil 0):
`php artisan migrate:rollback --step=3` (modul 11 dan `module_rights`-nya hanya dihapus bila namanya "Pembatalan Transaksi").
Bila sudah ada pembatalan, jangan rollback skema.

## Hasil test (DB `app_alkarimah_testing`)
`CancellationSchemaTest` (6) + `CancellationModuleTest` (7) + `EmployeeRightsPageTest::test_rights_page_lists_all_modules...` (1):
14 test, 75 assertion, semua hijau. Migration dijalankan ke DB test dengan `--env=testing --path=...`
(DB aplikasi tidak disentuh; diperiksa lewat SELECT sebelum dan sesudahnya).
Sabotase: `up()` tidak lagi melempar saat ID dipakai modul lain, dan `down()` menghapus modul apa pun
-> `test_up_throws...` dan `test_down_leaves...` keduanya gagal; kode dikembalikan (file identik dengan aslinya).
