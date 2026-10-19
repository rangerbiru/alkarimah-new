# 2026-10-19: Halaman Pilih Akun dan normalisasi nomor HP saat login

**Tanpa migration. Tanpa perubahan `.env`. Data di DB tidak diubah.** Deploy setelah `b1b7336` (hapus orang tua).

## 1. Halaman Pilih Akun (menggantikan penolakan "lebih dari satu akun")
Bila password cocok dengan lebih dari satu akun tujuan (setelah akun alias diselesaikan ke akun pegawainya), pengguna
diarahkan ke `GET /choose-account` (`auth.choose-account`) alih-alih ditolak.
- Hanya akun yang password-nya cocok yang ditampilkan: nama, role, dan nama anak (untuk akun yang punya data orang tua).
- Daftar id akun disimpan di session `login_choice` (bukan di URL / form), berlaku 5 menit, dihapus begitu dipakai
  (berhasil atau tidak). Server menolak id di luar daftar. Kedaluwarsa -> kembali ke halaman login dengan pesan.
- Setelah dipilih (`POST /choose-account`): pengecekan yang sama seperti login biasa (akun orang tua yatim, wajib punya santri,
  dua peran, dll.), lalu `Auth::login` dengan "Ingat Saya" sesuai input awal dan regenerasi session.
- Pencatatan di `activity_log` (`log_name = login-ambiguous-accounts`): `event = presented` (nomor, akun kandidat, IP) dan
  `event = chosen` (nomor, akun kandidat, akun yang dipilih, IP). Menggantikan `Log::warning('login-ambiguous-accounts')`.
- Tidak ada tombol pindah akun setelah masuk: logout lalu login lagi.
- Satu akun tujuan dan akun alias tidak berubah: langsung masuk.

### Penyaringan kandidat (perbaikan 2026-10-20)
Sebelum halaman Pilih Akun ditampilkan, setiap akun tujuan menjalani pemeriksaan pasca-login yang sama dengan login biasa
(`AuthController::loginRejection()`, satu sumber untuk login langsung, penyaringan, dan akun yang dipilih):
akun orang tua yatim (tanpa data orang tua aktif) dan akun orang tua tanpa santri dibuang. Akun alias yang tujuannya tidak
valid sudah dibuang lebih awal.
- Tersisa 1 akun: langsung masuk tanpa halaman pilihan (kasus 087728816070: akun kepala sekolah dua peran + akun orang tua yatim).
- Tersisa 2 atau lebih: halaman Pilih Akun hanya berisi akun yang bisa dipakai.
- Tersisa 0: pesan penolakan akun pertama (urut id), sama seperti login biasa.
- Kandidat yang dibuang dicatat ke `activity_log` (`log_name = login-ambiguous-accounts`, `event = filtered`) dengan
  `dropped` = id akun dan alasan (`parent-account-orphan`, `parent-without-students`, `alias-target-invalid`).
- Akun dua peran tampil dengan label gabungan, mis. "Kepala Sekolah + Orang Tua".
- Pemeriksaan dijalankan lagi saat akun dipilih (data bisa berubah selama halaman terbuka).

## 2. Normalisasi nomor HP
Nomor input dan `users.phone` / `parent.phone` dibandingkan dalam bentuk baku (`App\Helpers\Phone::normalize` dan `Phone::sql`):
buang `-`, spasi, titik; awalan `+62` / `62` menjadi `0`. Dihitung di query, data tidak diubah.
- Berlaku di login, lupa password (cari akun, daftar akun, reset), dan registrasi (cari data orang tua, cek akun terdaftar,
  akun staf bernomor sama, nomor akun alias).
- Nomor kosong dan "0" (termasuk "+62" / "62" yang menjadi "0") tetap tidak pernah dipakai mencari akun. Batas 10 kandidat tetap.
- Dampak: nomor yang tersimpan dengan tanda `-` (mis. `0813-2942-9364`) kini bisa dipakai login; sebelumnya tidak pernah cocok.
  Akibatnya beberapa nomor bisa mendapat akun kandidat baru; lihat skrip di bawah.
- Kunci pembatas percobaan login memakai nomor ternormalisasi (format berbeda dari nomor yang sama dihitung bersama).

## 3. Daftar akun bernomor kembar untuk admin
`docs/support/akun-nomor-kembar.sql` (SELECT saja): semua nomor dengan lebih dari satu akun tujuan, kategori a / b / c1 / c2
dan saran tindakannya, plus ringkasan per kategori. Normalisasi sama dengan login.

## File yang berubah
- `app/Http/Controllers/AuthController.php`: `authenticate()`, `chooseAccount()`, `storeChooseAccount()`, `completeLogin()`,
  `resolveLoginAccount()`, `usersWithPhone()`; normalisasi di lupa password dan registrasi.
- `app/Http/Requests/AuthRegisterRequest.php`, `AuthForgotPasswordRequest.php`: nomor dinormalkan sebelum validasi.
- `routes/web.php`: `auth.choose-account`, `auth.store.choose-account`.
- `resources/views/frontend/auth/choose-account.blade.php` (baru, gaya halaman login).
- `lang/{id,en}/string.php`: kunci baru; `login_ambiguous_accounts` dihapus.
- Tes: `tests/Feature/LoginChooseAccountTest.php`, `tests/Feature/LoginPhoneNormalizationTest.php` (baru),
  `tests/Feature/AccountAliasTest.php` (penolakan diganti halaman pilih akun).
- `docs/support/akun-nomor-kembar.sql` (baru).

## Verifikasi di browser (HP)
1. Dua akun dengan nomor dan password sama: login -> halaman Pilih Akun menampilkan keduanya (nama, role, nama anak);
   pilih satu -> masuk ke akun itu. Logout, login lagi -> halaman Pilih Akun muncul lagi.
2. Biarkan halaman Pilih Akun lebih dari 5 menit, lalu pilih -> kembali ke login dengan pesan waktu habis.
3. Akun yang nomornya tersimpan `0812-xxxx-xxxx` -> login dengan `0812xxxxxxxx` dan `+62812xxxxxxxx` berhasil.
4. Lupa password dengan format nomor berbeda dari yang tersimpan -> kode OTP terkirim.
5. Akun alias (akun orang tua yang sudah digabung) -> langsung masuk ke akun pegawai tanpa Pilih Akun.
6. 087728816070 dengan password yang sama untuk akun kepala sekolah dan akun orang tua lama -> langsung masuk sebagai
   kepala sekolah (menu pegawai + orang tua), tanpa halaman Pilih Akun.
7. Dua akun valid, salah satunya dua peran -> label "<Role> + Orang Tua" di halaman Pilih Akun.

## Rollback
Kembalikan file di atas. Session `login_choice` yang tersisa tidak berbahaya (kedaluwarsa sendiri).
