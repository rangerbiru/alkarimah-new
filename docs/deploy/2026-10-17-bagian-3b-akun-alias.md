# Bagian 3b: akun alias (login dengan akun pegawai maupun akun orang tua lama)

Koreksi atas bagian 3. Commit terpisah di atas `8823a80`.

## Perilaku
- Pegawai yang juga orang tua bisa login dengan **akun pegawai** maupun **akun orang tuanya yang lama**; keduanya masuk sebagai akun pegawai dengan menu dua peran yang sama.
- Penggabungan **tidak lagi men-soft-delete** akun orang tua lama. Akun itu menjadi *alias*: `users.linked_user_id` = id akun pegawai. Password kedua akun tidak berubah.
- `parent.id_user` diarahkan ke akun pegawai; kolom referensi allowlist (`config/account-link.php`) dan `account_link_log` seperti sebelumnya.
- `unlink` mengosongkan `linked_user_id`, mengembalikan `parent.id_user`/`parent.phone`/baris yang dipindah dari `account_link_log`. Akun lama login sebagai dirinya sendiri lagi.

## Login (`AuthController@authenticate`)
- Mencari **semua** user dengan email/nomor HP itu (nomor kosong dan `0` tidak dipakai mencari), mengecek password ke tiap kandidat (maks 10; bila lebih, 10 pertama dicek dan dicatat ke log `login-too-many-candidates` berisi nomor dan jumlah kandidat).
- Kandidat yang passwordnya cocok diselesaikan ke akun tujuan (alias -> akun pegawai). Tepat satu tujuan -> login. Lebih dari satu tujuan berbeda -> ditolak (pesan "hubungi Admin") dan dicatat di log `login-ambiguous-accounts`; tidak ada yang dipilih otomatis. Alias yang tujuannya hilang/terhapus -> ditolak, log `login-alias-target-invalid`.
- Route `POST authenticate` sebelumnya **tanpa throttle**; kini `throttle:20,1` (per IP, per menit). Naikkan/turunkan bila jaringan pondok (satu IP) terkena.
- Middleware `resolve.alias` di grup `auth` (`ResolveAlias`): sesi atau cookie "ingat saya" lama milik alias dialihkan ke akun pegawai; bila tujuan tidak valid, sesi diakhiri. Tidak ada logika sesi di model.

## Form pegawai (`is_parent`)
- Anak yang orang tuanya sudah punya akun **tidak ditolak**. Server menampilkan ringkasan (orang tua, anak, peringatan) dan kotak centang konfirmasi; setelah dicentang dan Simpan, akun orang tua tersebut digabung (`AccountLinker::link`) dalam transaksi yang sama dengan penyimpanan pegawai. Password tidak dikirim ulang ke form, jadi diketik ulang saat konfirmasi (create).
- Anak dari **dua** orang tua berakun berbeda ditolak dengan pesan yang menyebut kedua nama; admin membereskan data ganda dulu (mis. Gabungkan di salah satu orang tua).
- Datatable anak: kunci JSON `locked` diganti `has_account` (hanya badge informasi, tombol Pilih tetap ada).

## Pegawai/akun dihapus
- `EmployeeController@destroy` dan `UserController@destroy` memanggil `AccountLinker::unlinkAliasesOf()`: semua alias yang menunjuk ke akun itu dilepas (unlink otomatis, termasuk alias tanpa entri log), dicatat ke `activity_log` (`log_name = account-link`, `reason = employee-deleted | user-deleted`). Penghapusan lewat SQL langsung/`->delete()` massal tidak melewati ini; alias dengan tujuan terhapus tidak bisa login (fail closed) sampai di-unlink dari halaman edit orang tua.

## Lupa password / registrasi
- Lupa password lewat nomor akun alias mereset password alias itu saja (OTP dan pilihan akun tetap). Akun pegawai tidak berubah.
- Registrasi orang tua dengan nomor milik akun alias ditolak: "Akun Anda sudah terdaftar... login atau Lupa Password".

## Halaman edit orang tua
Menampilkan "Terhubung dengan akun pegawai: <nama>" dan bahwa akun orang tua lama (email/HP) tetap bisa dipakai login.

## Deploy
1. `php artisan migrate` (migration `2026_10_17_000200_add_linked_user_id_to_users_table`; kolom nullable + index + FK `nullOnDelete`, aman untuk data yang ada). Termasuk migration bagian 3 (`account_link_log`, `student.id_parent` nullable) bila belum dijalankan.
2. Tidak ada perubahan `.env`.
3. Setelah deploy, pantau `storage/logs` untuk `login-ambiguous-accounts` dan `login-too-many-candidates` (data nomor ganda yang perlu dibereskan).

## Catatan fallback data lama
`unlink()` masih memulihkan akun orang tua yang di-soft-delete (entri `account_link_log` dengan `old_user_deleted = true`, buatan versi bagian 3 sebelumnya). Versi itu **belum pernah di-deploy** (commit `ff282fc` dan `8823a80` belum di-push), jadi fallback ini hanya relevan untuk data lokal/pengujian dan bisa dihapus bila diinginkan.

## Rollback
`php artisan migrate:rollback --path=database/migrations/2026_10_17_000200_add_linked_user_id_to_users_table.php` menghapus kolom. Lepaskan semua alias lebih dulu (unlink dari halaman edit orang tua), sebab tanpa kolom itu akun orang tua lama tidak lagi terhubung ke akun pegawai.
