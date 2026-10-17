# Bagian 2 — Aplikasi dua peran (pegawai yang juga orang tua), model PPIA

Mengikuti PPIA (commit `6ac6c75` dual-role dan `17e20e3` role mass-assignment). Deploy SETELAH bagian 1.
Tidak ada migration pada bagian ini. Status dua peran murni dari data: akun punya baris `employee` (belum dihapus) DAN baris `parent` (belum dihapus) — `employee.id_user = parent.id_user`.

## Perilaku
- **User** (`app/Models/User.php`): accessor `is_employee`, `is_parent`, `has_dual_role`, `menu_role`, `is_inactive_employee`, `acts_as_parent_only`. `users.role` tidak pernah diubah/dioverride.
- **Role middleware**: akun dua peran boleh membuka route `role:orang-tua`; akun dua peran yang terdaftar dengan role `orang-tua` juga boleh membuka route `role:pegawai`. Route di luar kedua kelompok (mis. kasir tanpa modul) tetap ditolak.
- **Menu digabung**: sidebar desktop dan mobile, dashboard pegawai (pintasan), dan bottom nav mengikuti `menu_role`. Menu "Menu Orang Tua" (Data Anak, Pembayaran, Tabungan Saldo, Uang Saku) ditambahkan di bawah menu pegawai.
- **Data Anak** (`academic.student.children`): daftar anak. Akun dua peran menambah anak dengan NIS + tanggal lahir (`academic.student.store.child`, throttle 10/menit). Anak yang orang tuanya punya akun sendiri tidak pernah diambil alih; pesan sama untuk NIS tidak ada dan tanggal lahir salah.
- **Pembayaran / Tabungan Saldo / Uang Saku** untuk orang tua tanpa anak: diarahkan ke Data Anak dengan Swal (`parent.has-children`).
- **Transaksi**: transaksi buatan akun dua peran lewat route orang tua (`finance.payment.store`, `finance.balance.store`, `finance.savings.store`) diberi masa berlaku seperti transaksi orang tua, kecuali akun itu kasir / kasir-unit (setoran kasir tetap setoran kasir).
- **Login**: akun dua peran boleh masuk sebagai pegawai tanpa santri. Orang tua biasa tanpa santri tetap ditolak.
- **Pegawai nonaktif yang juga orang tua** (aturan tambahan Al-Karimah): diperlakukan sebagai orang tua saja (`acts_as_parent_only`): route staf ditolak, `menu_role` = orang-tua, login ditolak bila tidak punya santri. Pegawai nonaktif yang bukan orang tua tidak berubah.
- **Kepemilikan santri**: halaman santri orang tua (show/edit/update) memberi 403 bila bukan anaknya; `updateParent` memakai data tervalidasi.
- **Allowlist route orang tua** (permintaan Al-Karimah, tidak ada di PPIA): `config/parent_access.php`, `PARENT_ROUTE_MODE=log|enforce` (default `log`). Berlaku untuk akun dengan `menu_role = orang-tua` (orang tua biasa dan pegawai nonaktif-orang tua), TIDAK untuk akun dua peran. Mode `log` mencatat `parent-route-outside-allowlist` (route, user, waktu); `enforce` mengarahkan ke dashboard. Test gagal bila ada route ber-`orang-tua` yang belum terdaftar.

### Batasan model ini (berbeda dari rancangan override per request yang sebelumnya)
- Akun dua peran **wajib punya baris pegawai**. Akun `kasir`/`bendahara`/`wali-kelas` murni yang tidak punya baris pegawai tidak dihitung dua peran (di Al-Karimah hanya sedikit akun staf yang punya baris pegawai; kasir biasanya pegawai dengan modul Kasir).
- Endpoint yang dipakai bersama staf dan orang tua memutuskan tampilan lewat hak akses staf (`hasAccess`): kasir/kasir-unit yang juga orang tua mendapat perilaku kasir pada `finance.savings.store` dan `finance.savings.history`. Konflik kepentingan kasir (bagian 4) menutup celah transaksi untuk anak sendiri.
- Modul Hak Akses tetap berlaku untuk akun dua peran (tidak dimatikan saat membuka halaman orang tua).

## File yang berubah
- `app/Models/User.php`, `app/Http/Middleware/Role.php`, `EnsureParentHasChildren.php` (baru), `ParentRouteAllowlist.php` (baru), `bootstrap/app.php`, `routes/web.php` (grup auth + `parent.allowlist`)
- `app/Models/Transaction.php`, `app/Http/Controllers/AuthController.php`, `DashboardController.php`, `Academic/StudentController.php` (children, storeChild, kepemilikan), `Academic/StudentPermitController.php`
- `app/Http/Requests/StudentChildRequest.php` (baru)
- `routes/academic.php`, `routes/finance.php`
- `config/parent_access.php`
- View: `layouts/backend/sidebar.blade.php`, `sidebar-menu-parent.blade.php`, `layouts/mobile/index|sidebar.blade.php`, `backend/dashboard/pegawai.blade.php`, `dashboard/partials/parent-shortcut.blade.php`, `academic/student/index-parent.blade.php`
- `lang/{id,en}/{label,message,string}.php`
- Test saja: `tests/Feature/DualRoleTest.php`

## Migration / perintah
Tidak ada migration. Bila server memakai cache: `php artisan config:clear && php artisan route:clear`. Opsional `.env`: `PARENT_ROUTE_MODE=log`.

## Memantau allowlist
```bash
grep 'parent-route-outside-allowlist' storage/logs/laravel-*.log | sed -E 's/.*"route":"([^"]*)".*/\1/' | sort | uniq -c | sort -rn
```
Tambahkan route yang sah ke `config/parent_access.php` sebelum mengganti ke `enforce`.

## Verifikasi setelah deploy
1. Orang tua biasa: dashboard, pembayaran, saldo, uang saku, Data Anak berfungsi seperti sebelumnya (tanpa tombol Tambah Anak).
2. Pegawai biasa: tidak ada "Menu Orang Tua"; `/academic/student/children` diarahkan ke dashboard.
3. Akun uji dua peran (lihat bagian 3 untuk cara menghubungkan): dashboard menampilkan pintasan, sidebar menampilkan Menu Orang Tua, Data Anak bisa menambah anak dengan NIS + tanggal lahir, menu pegawai (modul) tetap berfungsi.
4. Dua peran tanpa anak: Pembayaran / Saldo / Uang Saku mengarah ke Data Anak dengan notifikasi.
5. `users.role` akun dua peran tetap sama di DB.

## Rollback
Kembalikan file dari backup, `config:clear` / `route:clear`. Tidak ada perubahan data.
