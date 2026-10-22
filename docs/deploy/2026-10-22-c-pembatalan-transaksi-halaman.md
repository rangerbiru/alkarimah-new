# 2026-10-22 (Bagian C): Pembatalan Transaksi, halaman admin dan riwayat

**Tanpa migration baru.** Tergantung bagian A dan B. Deploy A-D sekaligus. Setelah C terpasang, menu **Keuangan > Pembatalan
Transaksi** muncul untuk admin dan pegawai pemegang modul 11 (bukan pemegang modul Kasir / Kasir Unit), sehingga pembatalan
sudah bisa dilakukan; tampilan "Dibatalkan" di sisi orang tua baru ada di bagian D.

## Yang ditambahkan
- Halaman `finance/cancellation` (daftar transaksi lunas atau dibatalkan, filter tanggal bayar dan jenis, pencarian nomor / NIS /
  nama siswa / nama ortu) dengan dialog konfirmasi (pratinjau, alasan wajib), dan `finance/cancellation/history`.
- Route di `routes/finance.php` dengan tiga lapis: `role:admin,pegawai`, `accessRights:11`, dan `CancellationAccess`
  (memakai `TransactionCancellation::allowed()`, supaya pegawai pemegang modul 11 **dan** Kasir ditolak).
- Menu: `sidebar-menu-cancellation` di `sidebar-admin` dan `sidebar-pegawai-modules` (dipakai sidebar desktop dan mobile).
- Kunci lang `message.server_error_reference` (id/en). **Kunci ini ternyata belum ada** di repo walau prompt menyebutnya sudah ada.

## Catatan
- Kesalahan tak terduga mengembalikan 500 dengan kode `CNL-ymdHis-XXXX` dan ditulis ke log.
- Label status di tabel memakai `label.paid` ("Dibayar"), bukan "Sudah Dibayar".

## File
| File | Perubahan |
|---|---|
| `app/Http/Controllers/Finance/CancellationController.php` | baru |
| `app/Http/Middleware/CancellationAccess.php` | baru |
| `routes/finance.php`, `routes/breadcrumbs.php` | route dan breadcrumb |
| `resources/views/backend/finance/cancellation/{index,history}.blade.php` | baru |
| `resources/views/layouts/backend/sidebar-menu-cancellation.blade.php` | baru |
| `resources/views/layouts/backend/sidebar-admin.blade.php`, `sidebar-pegawai-modules.blade.php` | include menu |
| `lang/{id,en}/{label,string,message}.php` | kunci baru |

## Verifikasi
`php artisan test --env=testing` untuk `CancellationPageTest` (6 test). Cek browser (Playwright) dilakukan di DB test saja.

## Rollback
Revert commit ini; tidak ada perubahan data.
