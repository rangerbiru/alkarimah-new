# Bagian 3 — Penggabungan akun pegawai - orang tua (AccountLinker)

Deploy SETELAH bagian 1 dan 2. Ada 1 migration baru (tabel `account_link_log`). Tidak ada perubahan data saat deploy; data baru berubah hanya ketika admin menekan Hubungkan / Putuskan.

## Fitur
1. **Form pegawai (create/edit)** — mengikuti PPIA: radio "Apakah pegawai ini juga sebagai Orang Tua Santri?" (`is_parent`). Bila Ya, daftar anak ditampilkan sebagai label (bisa dihapus dengan ✕, kecuali anak yang sudah tertaut) dan tombol "Pilih Anak" membuka modal datatable semua santri (NIS, nama, kelas, orang tua) dengan kolom **Aksi di kanan**: tombol **Pilih** (menjadi badge **Terpilih**), atau badge kecil **Ortu punya akun** bila orang tua santri itu sudah punya akun (tidak bisa dipilih). Santri tanpa data orang tua (`id_parent` kosong) tetap tampil dan bisa dipilih.
   - Saat disimpan (satu transaksi): dibuat satu baris orang tua untuk akun pegawai (nama, nomor HP akun, jenis kelamin; cabang dari akun) dan anak terpilih dipindahkan ke baris itu (`student.id_parent`). Anak yang orang tuanya punya akun ditolak dengan pesan (tidak ada yang tersimpan).
   - Sebelum membuat baris orang tua baru, dicari data orang tua yang sudah ada dengan nomor HP (10 digit terakhir) atau nama yang sama (gelar setelah koma diabaikan). Bila ada, tidak ada yang tersimpan dan admin diarahkan ke Orang Tua > Edit > Gabungkan dengan Akun Pegawai. Akun yang sudah punya baris orang tua dibiarkan.
   - Memilih "Tidak" tidak pernah menghapus data orang tua yang sudah ada. Edit pegawai menyamakan `parent.phone` dengan nomor akun.
   - Hak akses: sama dengan form pegawai (admin, atau pegawai bermodul Kelola Kepegawaian).
2. **Form edit orang tua, admin saja**: bagian "Gabungkan dengan akun pegawai". Cari pegawai (nama/NIP/HP). Pegawai dengan nomor HP yang sama (10 digit terakhir) disarankan, tetapi admin tetap harus memilih dan mengonfirmasi. Tombol Hubungkan menampilkan preview dan peringatan; server mengecek ulang pemblokir. Tombol "Putuskan" memanggil `unlink()`.
3. **Registrasi orang tua**: nomor HP dicek ke semua user tanpa melihat role. Bila nomor dipakai akun staf, setelah OTP terverifikasi data orang tua dihubungkan ke akun pegawai (bukan membuat user baru) dengan pesan "silakan login dengan akun pegawai". Bila tidak bisa dihubungkan (pegawai nonaktif, ada pemblokir, akun staf tanpa data pegawai, atau lebih dari satu akun staf berbagi nomor), registrasi dihentikan dengan pesan "hubungi Admin" dan TIDAK ada user baru.
4. **Form edit orang tua**: bila nomor HP yang diketik milik akun pegawai, pesan mengarahkan admin ke fitur Gabungkan; ditolak kembali ke halaman EDIT dengan input tetap terisi. Orang tua yang sudah digabung: nama akun, nomor HP, dan password TIDAK diubah dari form orang tua (mengikuti akun pegawai); edit form pegawai ikut menyamakan `parent.phone`.

## Aturan penggabungan (`App\Services\AccountLinker`)
- `preview(employeeId, parentId)`: hanya baca. Data pegawai, orang tua, akun lama, daftar anak, pemblokir, peringatan, jumlah baris yang dipindah.
- `link()`: satu transaksi, `lockForUpdate` pada parent, employee, dan kedua user. Menjalankan semua pemblokir, `parent.id_user` = user pegawai, memindahkan kolom allowlist dari akun lama lalu soft-delete akun lama (+ hapus sesi aktifnya), `parent.phone` = nomor akun pegawai, simpan entri rollback di `account_link_log`, catat ke `activity_log` (`log_name = account-link`).
- `unlink()`: mengembalikan `parent.id_user`, `parent.phone`, memulihkan akun lama (restore), dan mengembalikan baris yang masih menunjuk ke user pegawai. **Batasan**: baris yang dibuat setelah penggabungan tetap milik user pegawai. Tanpa entri rollback (mis. dihubungkan manual lewat SQL), `unlink` hanya melepas `parent.id_user` (parent jadi tanpa akun login).
- Password tidak pernah diubah; orang tua yang digabung login dengan email/HP + password akun pegawai.

### Pemblokir
Pegawai nonaktif/dihapus; akun pegawai hilang; orang tua dihapus; orang tua sudah terhubung ke akun pegawai itu; akun orang tua lama bukan ber-role `orang-tua` (akun staf tidak dinonaktifkan); akun pegawai sudah terhubung ke orang tua lain; nomor akun pegawai sudah dipakai data orang tua lain; cabang pegawai berbeda dari cabang orang tua/santri (BranchScope memfilter `Parents`/`Student`/`Transaction` dengan `users.branch_id`, sehingga data anak tidak terlihat setelah digabung — terbukti dari kode, diuji dengan data buatan karena DB lokal hanya satu cabang).

### Allowlist kolom referensi (`config/account-link.php`)
Hasil verifikasi per kolom (DB lokal, kode, dan role pemilik nilai):

| Kolom | Merujuk `users.id`? | Diisi akun orang tua? | Keputusan |
|---|---|---|---|
| `transaction.created_by / updated_by / deleted_by` | ya (`Auth::id()` di model) | ya (20 / 16 / 16 baris lokal) | **dipindah** |
| `transaction_bill.created_by / updated_by` | ya | ya (updated_by 3) | **dipindah** |
| `transaction_payment_code.created_by` | ya | ya (37 dari 37) | **dipindah** |
| `activity_log.causer_id` dengan `causer_type = App\Models\User` | ya (morph) | ya (134) | **dipindah** (baris `causer_type` lain tidak disentuh) |
| `transaction.paid_by` | ya | tidak (0 dari 545; diisi kasir atau 0) | tidak |
| `*.verified_by`, `processed_by`, `approved_by`, `class.id_wali_kelas`, `module_rights/user_rights/user_education_levels.*` | ya | tidak (milik staf) | tidak |
| `users.updated_by`, kolom audit tabel lain | ya | sebagian | tidak (di luar tabel transaksi, sesuai keputusan) |
| `student_*.student_id` | **tidak** (`student.id`) | — | tidak |
| `dzikir_pp.user`, `hisnul.user`, `employee_awards.awarded_by`, `student_details.financing_by` | **tidak** (teks) | — | tidak |

## File yang berubah
- Baru: `app/Services/AccountLinker.php`, `AccountLinkException.php`, `app/Http/Controllers/Hr/AccountLinkController.php`, `app/Helpers/Phone.php`, `config/account-link.php`, `database/migrations/2026_10_17_000000_create_account_link_log_table.php`, `resources/views/backend/hr/employee/form-children.blade.php`, `resources/views/backend/academic/parent/partials/link-employee.blade.php`
- Diubah: `app/Http/Controllers/Hr/EmployeeController.php`, `Academic/ParentController.php`, `AuthController.php`, `routes/hr.php`, view `hr/employee/create|edit`, `academic/parent/edit`, `lang/{id,en}/{label,string,message}.php`
- Test saja: `tests/Feature/AccountLinkTest.php`

## Migration dan perintah
```bash
php artisan migrate        # membuat tabel account_link_log; menyamakan student.id_parent menjadi nullable (no-op di produksi)
php artisan config:clear && php artisan route:clear   # bila server memakai cache
```
Migration `make_student_id_parent_nullable` hanya menyamakan skema: di database produksi/lokal kolom `student.id_parent` sudah nullable (ada santri tanpa orang tua), sedangkan migration awal membuatnya NOT NULL. Tidak mengubah data.

`account_link_log` tidak punya pembersihan otomatis. Jangan dibersihkan: isinya adalah data rollback.

## Verifikasi setelah deploy (pakai data uji, bukan data asli)
1. Admin: Pegawai → Tambah: radio "Juga Orang Tua" tampil; pilih Ya, buka modal, cari santri uji, pilih 2 anak dari orang tua yang sama → preview muncul → Simpan: pegawai dibuat dan orang tua tertaut. Pilih dua anak dari orang tua berbeda → ditolak.
2. Login sebagai pegawai tsb: menu orang tua tampil (bagian 2), "Anak Saya" memuat anak yang dipilih; orang tua yang punya akun lama tidak bisa login dengan akun lama, tetapi bisa dengan email/HP + password akun pegawai.
3. Orang tua → Edit: bagian "Gabungkan dengan akun pegawai" menampilkan pegawai terhubung; tombol Putuskan mengembalikan akun lama (bisa login lagi dengan password lamanya).
4. Orang tua → Edit: ketik nomor HP milik pegawai lain → kembali ke halaman edit dengan pesan arahan Gabungkan dan input tetap.
5. Registrasi orang tua memakai nomor pegawai uji: tidak membuat user baru, pesan "silakan login dengan akun pegawai".
6. Cek `account_link_log` dan `activity_log` (`log_name = 'account-link'`) terisi.

## Rollback
- Data: putuskan semua penggabungan lewat tombol Putuskan (atau `AccountLinker::unlink`) SEBELUM menurunkan kode.
- Kode: kembalikan file dari backup. Tabel `account_link_log` boleh dibiarkan; bila perlu `php artisan migrate:rollback --step=1` (menghapus jejak rollback, jadi lakukan hanya setelah semua penggabungan diputus).

## Batasan / catatan
- Login dengan nomor HP kembar belum diubah (`Auth::attempt` hanya mencoba satu baris per nomor). Penggabungan mengurangi kasusnya karena akun orang tua lama dinonaktifkan.
- Registrasi dengan nomor pegawai menggabungkan otomatis setelah OTP (sesuai spesifikasi). Bila satu nomor HP dipakai bersama oleh pegawai dan orang tua yang berbeda orang, OTP tetap sampai ke pemilik nomor; admin sebaiknya menggunakan fitur Putuskan bila itu terjadi.
- Gabungkan akun di form edit orang tua hanya untuk admin (`role:admin`).
- Konflik kepentingan kasir (bagian 4) belum aktif: kasir yang digabung sebagai orang tua masih bisa memproses tagihan anaknya sendiri sampai bagian 4 dideploy.
