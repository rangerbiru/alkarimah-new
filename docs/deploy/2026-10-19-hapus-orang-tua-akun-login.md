# 2026-10-19: Hapus data orang tua ikut menonaktifkan akun login-nya

**Tanpa migration. Tanpa perubahan `.env`.** Deploy setelah hotfix `fdd5666`.

## Latar belakang
`Orang Tua -> Hapus` dulu hanya menghapus baris orang tua dan membiarkan akun login-nya (role `orang-tua`) tetap aktif tanpa
data orang tua ("akun yatim"). Akun seperti itu ditolak saat login dengan pesan "Data Siswa Anda belum ada", yang menyesatkan
(kasus 087728816070).

## Perilaku baru
- Saat admin menghapus data orang tua yang akun login-nya ber-role `orang-tua` dan tidak dipakai baris orang tua lain, konfirmasi
  menampilkan: "Akun login orang tua ini juga akan dinonaktifkan."
- Bila ada pegawai aktif dengan nomor HP yang sama (setelah normalisasi: tanpa `-`, spasi, titik; `+62`/`62` menjadi `0`),
  konfirmasi menawarkan pilihan kedua "Jadikan akun ini alias dari akun pegawai <nama> (<NIP>)". Pilihan bawaan tetap
  "Nonaktifkan akun login ini". **Tidak pernah otomatis**: alias hanya dibuat bila admin memilihnya.
- Bawaan: baris orang tua dan akun login di-soft-delete dalam satu transaksi, dicatat ke `activity_log`
  (`log_name = parent-account`, `event = deactivated-with-parent`).
- Pilihan alias: lewat `AccountLinker::aliasAccount`, mengisi `users.linked_user_id`, dicatat ke `account_link_log`
  (`action = alias`) dan `activity_log` (`log_name = account-link`). Server memeriksa bahwa pegawai yang dipilih memang
  salah satu yang disarankan; selain itu ditolak dan tidak ada yang terhapus.
- Akun staf (role selain `orang-tua`) tidak pernah ikut dinonaktifkan. Akun orang tua yang dipakai baris orang tua lain juga tidak.
- Login akun orang tua yatim yang sudah ada: pesan baru "Akun ini tidak lagi terhubung dengan data orang tua. Silakan login dengan
  akun Anda yang lain atau hubungi Admin." Pesan lama tetap untuk orang tua yang datanya ada tetapi tidak punya santri aktif.
- Data akun yatim yang sudah ada **tidak diubah**: lihat `docs/support/cek-status-pegawai-dan-akun-yatim.sql` bagian C.

## File yang berubah
- `app/Helpers/Phone.php`: `normalize()`, `isBlank()`, `sql()` (normalisasi nomor HP di PHP dan SQL, tanpa mengubah data).
- `app/Services/AccountLinker.php`: `deactivatableAccount()`, `aliasCandidates()`, `aliasAccount()`.
- `app/Http/Controllers/Academic/ParentController.php`: `deletePreview()` (baru), `destroy()`.
- `routes/academic.php`: `GET academic/parent/{parent}/delete-preview` (`academic.parent.delete-preview`).
- `resources/views/backend/academic/parent/index.blade.php`: konfirmasi hapus `deleteParent()`.
- `app/Http/Controllers/AuthController.php`: pesan login akun yatim.
- `lang/{id,en}/message.php`, `lang/{id,en}/string.php`.
- Tes: `tests/Feature/ParentDeleteAccountTest.php` (baru), `tests/Feature/LoginEmployeeStatusTest.php` (pesan akun yatim).

## Verifikasi di browser
1. Orang Tua -> hapus orang tua yang punya akun sendiri: konfirmasi menyebut akun login ikut dinonaktifkan. Setelah dihapus,
   akun itu tidak bisa login (pesan "Email / No. HP atau Password Salah").
2. Orang tua dengan nomor HP sama seperti pegawai: konfirmasi menampilkan dua pilihan radio; pilih alias -> login dengan password
   akun orang tua lama masuk ke akun pegawai.
3. Hapus data orang tua milik pegawai (dua peran): akun pegawai tetap bisa login.
4. Akun orang tua yatim lama (bagian C query di atas): login menampilkan pesan baru.

## Rollback
Kembalikan file di atas. Akun yang terlanjur dinonaktifkan bisa dipulihkan dengan `UPDATE users SET deleted_at = NULL, deleted_by = NULL
WHERE id = <id>` (cari id di `activity_log` `log_name = parent-account`); alias bisa dilepas dengan `linked_user_id = NULL`.
