# Bagian 2 — Aplikasi dua peran (pegawai yang juga orang tua)

Deploy SETELAH bagian 1 (`2026-10-17-bagian-1-keamanan.md`). Tidak ada migration dan tidak ada perubahan data.
Bagian ini belum menyediakan cara menghubungkan akun (itu bagian 3); status dua peran murni dari `parent.id_user = users.id` milik pegawai.

## Perilaku
- **Menu digabung**: pegawai/staf yang punya baris `parent` aktif dan santri melihat menu stafnya ditambah "Menu Orang Tua" (Anak Saya, Pembayaran, Tabungan Saldo, Uang Saku), di sidebar desktop dan mobile, plus pintasan di dashboard pegawai.
- **Konteks per request**: request ke route khusus orang tua (`role:orang-tua`, atau route bersama yang tidak dipenuhi user sebagai staf) dijalankan sebagai orang tua: `users.role` di memori diganti `orang-tua` untuk request itu saja. Event `saving` di model `User` selalu mengembalikan role asli sehingga role override TIDAK PERNAH tersimpan ke DB. Request ke route staf tetap sebagai staf.
- **Modul Hak Akses**: berlaku seperti biasa pada route staf. Pada request konteks orang tua, `hasAccess()` dan `moduleIds()` kosong dan `accessRights:` menolak.
- **Pegawai nonaktif yang juga orang tua**: diperlakukan sebagai orang tua saja (seluruh request konteks orang tua, tanpa menu staf; route staf ditolak). Login ditolak bila tidak punya santri (pesan yang sama dengan orang tua biasa). Pegawai nonaktif yang bukan orang tua tidak berubah.
- **Orang tua tanpa santri** yang juga pegawai aktif: tetap masuk sebagai pegawai, menu orang tua tidak tampil.
- **Akun satu peran**: tidak berubah.
- Route baru `academic.student.children` ("Anak Saya", `role:orang-tua`): daftar anak untuk orang tua (route `academic.student.index` untuk admin menampilkan seluruh santri).
- Perbaikan tambahan: orang tua hanya boleh membuka/mengubah santri miliknya (`academic.student.show/edit/update.parent` memberi 404 bila bukan anaknya; sebelumnya tidak dicek).

## Allowlist route orang tua (akun orang tua biasa dan konteks orang tua)
`config/parent_access.php`, saklar `.env`: `PARENT_ROUTE_MODE=log|enforce` (default `log`).
- `log`: route di luar allowlist tetap boleh, dicatat ke log: `parent-route-outside-allowlist` dengan route, method, user_id, waktu.
- `enforce`: route di luar allowlist diarahkan ke dashboard (JSON 403 untuk AJAX).

Memantau log:
```bash
grep 'parent-route-outside-allowlist' storage/logs/laravel-*.log | sed -E 's/.*"route":"([^"]*)".*/\1/' | sort | uniq -c | sort -rn
```
Tambahkan route yang sah ke `config/parent_access.php` sebelum mengganti ke `enforce`. Test `test_every_parent_route_is_in_the_allowlist_config` gagal bila ada route ber-`orang-tua` yang belum terdaftar.

## File yang berubah
- `app/Models/User.php` (isParent/isEmployee/hasMultipleRoles/actAs/clearActAs, event saving/saved, syncOriginal, moduleIds)
- `app/Http/Middleware/ActiveRole.php` (baru), `AccessRights.php`, `bootstrap/app.php` (alias `active.role`), `routes/web.php` (grup auth + `active.role`)
- `app/Http/Controllers/AuthController.php` (aturan login pegawai nonaktif-orang tua), `Academic/StudentController.php` (children, kepemilikan santri)
- `routes/academic.php` (route children)
- `config/parent_access.php` (baru)
- `resources/views/layouts/backend/sidebar.blade.php`, `layouts/backend/sidebar-menu-parent.blade.php` (baru), `layouts/mobile/sidebar.blade.php`, `backend/dashboard/pegawai.blade.php`, `backend/dashboard/partials/parent-shortcut.blade.php` (baru)
- `lang/{id,en}/label.php`, `lang/{id,en}/string.php`
- Test saja: `tests/Feature/DualRoleTest.php`

## Migration / perintah artisan
Tidak ada migration. Bila server memakai cache: `php artisan config:clear && php artisan route:clear` (lalu cache ulang bila dipakai). Opsional di `.env`: `PARENT_ROUTE_MODE=log`.

## Verifikasi setelah deploy
1. Orang tua biasa: login, dashboard, pembayaran, saldo, uang saku, "santri" berfungsi seperti sebelumnya. Log hanya mencatat route yang memang di luar allowlist.
2. Pegawai biasa (bukan orang tua): tidak ada "Menu Orang Tua"; membuka `/academic/student/children` diarahkan ke dashboard.
3. Buat data uji: hubungkan satu parent ke `users.id` seorang pegawai (SQL manual: `UPDATE parent SET id_user=<id user pegawai> WHERE id=<id parent>`; hanya untuk uji, cara resmi ada di bagian 3). Login sebagai pegawai tsb: dashboard menampilkan pintasan dan sidebar menampilkan Menu Orang Tua; "Anak Saya" menampilkan hanya anaknya; menu staf (modul) tetap berfungsi; pada halaman orang tua sidebar menampilkan tautan Dashboard.
4. Pastikan `users.role` pegawai tsb tetap `pegawai` di DB setelah membuka halaman orang tua dan mengubah profil.
5. Kasir yang juga orang tua: halaman kasir tetap kasir, "Pembayaran" membuka tampilan orang tua.

## Rollback
Kembalikan file dari backup dan jalankan `config:clear` / `route:clear`. Tidak ada perubahan data.

## Batasan
- Login dengan nomor HP kembar belum diubah (dibahas bersama bagian 3).
- Konflik kepentingan kasir (kasir tidak boleh memproses tagihan anaknya sendiri) dikerjakan di bagian 4; sebelum itu, kasir yang juga orang tua masih bisa memproses tagihan anaknya.
