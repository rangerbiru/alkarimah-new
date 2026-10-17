# Bagian 1 — Perbaikan keamanan darurat (2026-10-17)

Berdiri sendiri: tidak bergantung pada fitur dua peran. Tidak ada migration dan tidak ada perubahan data.

## Isi perbaikan

| Kode | Masalah | Perbaikan |
|---|---|---|
| A | `PUT /profile` memakai `$request->all()`, user bisa mengubah role sendiri | Hanya `name, email, phone, gender` yang disimpan (`safe()->only`) |
| B | `POST/PUT/DELETE /user*` tanpa middleware, `User::create($request->all())` | Route dikunci `role:admin,super-admin`; hanya field whitelist; role wajib salah satu dari bendahara, kasir, kasir-unit, wali-kelas, penanggung-jawab-tabungan; edit/update/hapus hanya akun role tsb di cabang admin (selain itu 404) |
| C | `UserRequest` memakai `unique:branch` | Unik terhadap tabel `users` (email: semua baris; telepon: yang belum dihapus, dan hanya diperiksa bila nomor diganti) |
| D | Registrasi ortu: set password tanpa OTP terverifikasi | Setelah OTP benar, session menyimpan tanda terikat ke parent + nomor HP, berlaku 15 menit, dibuang setelah dipakai. OTP juga dikosongkan setelah terverifikasi |
| E | `POST reset-password/{user}` tanpa cek OTP | Alur reset password dipindah ke session (lihat di bawah) |
| G | Nomor dipakai >1 user, reset memilih baris pertama | Setelah OTP benar, bila nomor dipakai >1 akun, user memilih akun (nama + role) yang direset. Hanya satu akun yang direset |
| F | Route sensitif tanpa pembatas | Lihat tabel route di bawah |
| WA | Pengiriman WhatsApp tanpa guard | `WHATSAPP_ENABLED` + hanya jalan di `APP_ENV=production` |

Tambahan: rate limit (`throttle`) pada pengiriman OTP (5/menit) dan verifikasi kode (10/menit); batas 5 percobaan kode per sesi lupa-password.

### Alur lupa password yang baru
1. `POST /forgot-password` (nomor + captcha) → OTP dikirim, state OTP disimpan di **session** (bukan di kolom `users.token`).
2. `GET/POST /forgot-password/verification` (tanpa id user di URL).
3. Nomor milik >1 akun → `GET/POST /forgot-password/accounts` untuk memilih akun.
4. `GET/POST /reset-password` hanya bisa dibuka bila OTP + akun terpilih ada di session.

URL lama `forgot-password/verification/{user}` dan `reset-password/{token|user}` **dihapus** (menjadi 404). Pengguna yang sedang di tengah alur lupa password saat deploy harus mengulang dari awal.

### Route yang dikunci (sebelum → sesudah)
Sebelum: hanya `auth` + `initialize.backend` (semua user yang login, termasuk orang tua).

| Route | Sesudah |
|---|---|
| `branch.*` (index/create/store/edit/update/destroy/datatable) | `role:admin,super-admin` |
| `setting.index`, `setting.update`, `setting.year.*` | `role:admin,super-admin` |
| `user.index/create/store/edit/update/destroy/datatable` | `role:admin,super-admin` (+ `ensureManageable` di controller) |
| `user.*.kepala-sekolah` | tetap `role:admin` (ditambah group `admin,super-admin`; efektif admin saja, seperti sebelumnya) |
| `whatsapp.send.bill-due-date` | `access:kasir` (sama dengan halaman Tunggakan yang memakainya) |
| `student.import` | `role:admin,pegawai` + `accessRights:2` (sama dengan route siswa lain) |
| `employee.import` | `role:admin,pegawai` + `accessRights:3` (sama dengan route pegawai lain) |

Catatan: `super-admin` tetap bisa mengakses branch, setting, dan user. Untuk `whatsapp.send.bill-due-date`, `student.import`, `employee.import`, super-admin ikut aturan route saudaranya (tidak punya akses), karena halaman pemakainya juga tidak terbuka untuk super-admin.

Route lain tanpa middleware (`academic/*`, `employee/*`, dst.) sengaja **tidak diubah** di bagian ini.

## File yang berubah
- `app/Http/Controllers/AuthController.php`, `UserController.php`, `Employee/TahfidzController.php` (guard `sendToWa`)
- `app/Http/Requests/UserRequest.php`, `UserProfileRequest.php`
- `app/Helpers/Whatsapp.php`, `config/services.php`
- `app/Support/OtpFlag.php` (baru)
- `routes/web.php`, `routes/academic.php`, `routes/hr.php`
- `resources/views/frontend/auth/forgot-password-verification.blade.php`, `reset-password.blade.php`, `forgot-password-accounts.blade.php` (baru)
- `lang/{id,en}/string.php`, `lang/{id,en}/label.php`
- Test/dev saja (tidak perlu di-deploy): `tests/TestCase.php`, `tests/Feature/SecurityHardeningTest.php`, `tests/Feature/ModuleRoutesPpiaAccessTest.php`, `phpunit.xml`, `.gitignore`

## Migration baru
Tidak ada.

## SEBELUM deploy — WAJIB periksa `.env` server
```bash
grep -E '^(APP_ENV|WHATSAPP_ENABLED)=' .env
```
- `APP_ENV` **harus** `production`. Bila bukan, WhatsApp **tidak terkirim sama sekali** (OTP registrasi dan lupa password ikut mati; hanya ditulis ke `storage/logs`).
- `WHATSAPP_ENABLED` boleh tidak ada (default aktif) atau `true`. Isi `false` hanya untuk mematikan pengiriman.
- Bila server memakai `config:cache` / `route:cache`, jalankan setelah file di-upload:
```bash
php artisan config:clear && php artisan route:clear
# lalu (bila memang dipakai di server) php artisan config:cache && php artisan route:cache
```

## Langkah deploy
1. Backup folder aplikasi (atau minimal file pada daftar di atas).
2. Upload file yang berubah.
3. Jalankan perintah cache di atas bila perlu.

Tidak ada perintah artisan lain, tidak ada migration, tidak perlu restart queue worker.

## Verifikasi setelah deploy
1. Login sebagai admin: menu User (Kasir, Bendahara, dst.) dan Pengaturan masih terbuka; tambah dan edit user kasir uji berhasil; mencoba role lain dari form tidak mengubah role.
2. Login sebagai orang tua: membuka `/user/kasir`, `/branch`, `/setting` mengarah ke dashboard (tidak ada akses).
3. Profil: ubah nama/telepon sendiri berhasil; role tetap.
4. Lupa password dengan nomor yang dipakai satu akun: OTP WhatsApp **sungguhan terkirim**, verifikasi, langsung ke halaman password baru.
5. Lupa password dengan nomor yang dipakai >1 akun: muncul halaman pilih akun (nama + role).
6. Buka langsung `/reset-password` tanpa melalui alur: diarahkan ke halaman lupa password.
7. Registrasi ortu baru: OTP terkirim, verifikasi, set password berhasil. Membuka `/register/password/{id}` tanpa verifikasi diarahkan ke halaman registrasi.
8. Kasir: halaman Tunggakan, tombol kirim pengingat WA masih berfungsi.
9. Admin/pegawai bermodul: import siswa dan import pegawai masih berfungsi.

## Rollback
Kembalikan file dari backup langkah 1 (atau `git revert` commit bagian ini lalu upload ulang), jalankan `config:clear` dan `route:clear`. Tidak ada perubahan data yang perlu dibalik. Catatan: setelah rollback, celah A–G terbuka kembali.

## Batasan yang diketahui (tidak diubah di bagian ini)
- **Login dengan nomor HP kembar:** `Auth::attempt(['phone' => ...])` hanya mencoba satu baris (yang pertama ditemukan). Pada DB lokal: 23 user (20 orang tua, 3 staf) tidak bisa login memakai nomor HP karena ada user lain yang berbagi nomor dengan id lebih kecil; mereka hanya bisa login lewat email. 10 nomor dipakai lintas role. Dibahas bersama bagian 2.
- Kunci API WhatsApp masih hardcode di `Whatsapp.php` dan `TahfidzController.php` (tidak dipindah ke `.env` agar deploy tidak bergantung perubahan `.env`).
- Aturan `phone` di `UserProfileRequest` (`max:15`, unik terhadap `users`) tidak diubah.
