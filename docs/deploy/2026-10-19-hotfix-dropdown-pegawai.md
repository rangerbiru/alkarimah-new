# Hotfix 2026-10-19: dropdown / radio tidak menampilkan nilai tersimpan

**Tanpa migration. Tanpa perubahan `.env`. Tanpa `config:cache` ulang selain biasa.**

## Masalah
Di form edit (terutama Edit Pegawai), Jabatan menampilkan "Pilih" dan Status Pegawai tidak punya radio
yang terpilih, padahal nilainya ada di database. Data **tersimpan dengan benar**; yang gagal adalah
tampilannya. Akibatnya menyimpan form tanpa memilih ulang mengirim nilai kosong, dan Jabatan menjadi `NULL`.

Penyebab (ada di komponen, bukan di controller):
- `x-form.select` hanya memilih opsi dari prop `selected`; prop `old` yang dipakai 135 pemanggilan (57 file) diabaikan.
- `x-form.radio` membandingkan `'2' == <objek enum>` (selalu false) dan menganggap `false == ''` sebagai "kosong".

## File yang berubah
Komponen:
- `resources/views/components/form/select.blade.php`: `selected` tidak dikirim -> pakai `old`; enum memakai `->value`;
  boolean menjadi `1`/`0`; array (select multiple) dinormalkan per elemen. Halaman yang sudah mengirim `selected` tidak berubah.
- `resources/views/components/form/radio.blade.php`: perbandingan sebagai string (enum `->value`, boolean `1`/`0`);
  radio pertama menjadi default hanya bila tidak ada nilai yang cocok.

Pegawai:
- `app/Http/Requests/EmployeeRequest.php`: daftar field eksplisit `FORM_FIELDS`, aturan baru untuk `id_position`
  (`nullable|exists` di tabel `position`, belum dihapus, cabang admin), `status_employment` (enum `EmployeeStatus`: 1/2/3),
  `gender`, `marital_status`, `status_teacher`, `education`, `task_additional`; `nik` create dibatasi 16 (sama dengan update).
- `app/Http/Controllers/Hr/EmployeeController.php`: `store`/`update` hanya menyimpan `FORM_FIELDS`
  (`id_user`, `branch_id`, `status`, `salary`, `role`, dan kolom lain tidak bisa diisi dari request).

Santri / orang tua:
- `resources/views/backend/academic/student/edit-parent.blade.php`: input SPP diganti teks (orang tua tidak bisa mengubah SPP);
  `validated()` sudah membuang `spp`.

Master pelanggaran (HR):
- `resources/views/backend/hr/master/violation/edit.blade.php`, `create.blade.php`: opsi disamakan dengan enum DB
  (`aktif` / `non aktif`, `sedang`); sebelumnya opsi edit (`Aktif`, `Nonaktif`, `menengah`) tidak cocok dengan DB.

Login (pegawai yang juga orang tua):
- `app/Models/User.php`: `is_inactive_employee` hanya benar bila `employee.status` bernilai 0 secara eksplisit. Nilai kosong (null) atau
  tidak dikenal tidak lagi dianggap nonaktif (sebelumnya `! status`, sehingga null dianggap nonaktif).
- `app/Http/Controllers/AuthController.php`: hanya komentar; pengecekan "wajib punya santri" tetap hanya untuk akun orang tua dan
  pegawai nonaktif eksplisit yang juga orang tua. Staf aktif tidak pernah dicek.

Tes: `tests/Feature/LoginEmployeeStatusTest.php`, `tests/Feature/FormComponentsTest.php`, `EditPagesPreselectTest.php`, `EmployeeFormTest.php`,
`tests/Feature/Concerns/RendersFormValues.php`.

Skrip SQL (tidak dijalankan otomatis; lihat langkah di bawah):
- `docs/support/pulihkan-jabatan-pegawai.sql`
- `docs/support/deteksi-dropdown-kosong.sql`
- `docs/support/audit-spp-oleh-orang-tua.sql`
- `docs/support/cek-status-pegawai-dan-akun-yatim.sql`

## Urutan
1. **Deploy kode** (file di atas). Tidak ada migration; `php artisan view:clear` bila view di-cache.
2. **Verifikasi di browser** (bagian berikut).
3. **Baru setelah itu** pulihkan data: jalankan LANGKAH 1 `pulihkan-jabatan-pegawai.sql`, periksa, lalu LANGKAH 2 dan UPDATE yang disetujui
   (di dalam transaksi). Jangan memulihkan sebelum deploy: admin yang membuka Edit Pegawai di kode lama akan mengosongkannya lagi.
4. Jalankan `deteksi-dropdown-kosong.sql` untuk melihat kolom lain (santri, orang tua, kelas, tagihan, dll.) yang ikut terkosongkan,
   dan `audit-spp-oleh-orang-tua.sql` untuk perubahan SPP oleh orang tua.

## Verifikasi di browser
**Edit Pegawai** (HR -> Pegawai -> Edit pada pegawai yang punya jabatan):
- Jabatan menampilkan jabatan tersimpan (bukan "Pilih"); Status Pegawai, Status Pengajar, Status Pernikahan, Jenis Kelamin tercentang sesuai data.
- Simpan tanpa mengubah apa pun, buka Edit lagi: Jabatan dan Status tetap sama.
- Ubah Jabatan dan Status Pegawai, simpan, buka Edit lagi: nilai baru tampil.

**Edit Santri (admin)** (Akademik -> Santri -> Edit):
- Agama, Asrama, Halaqah, Jenjang, Jenis Kelamin, Beasiswa, Status tampil sesuai data. Kelas terisi oleh JS setelah Jenjang dipilih (perilaku lama).
- Simpan tanpa mengubah apa pun: Asrama, Halaqah, Kelas tidak berubah.

**Edit Santri (akun orang tua)**:
- Agama dan Jenis Kelamin tampil sesuai data. Tab "Lainnya": SPP tampil sebagai teks (bukan input).

**Edit Orang Tua** (Akademik -> Orang Tua -> Edit): Jenis Kelamin tampil sesuai data; Provinsi/Kota/Kecamatan/Desa diisi JS seperti sebelumnya.

**Halaman lain yang belum bisa diuji otomatis** (uji manual cepat, buka lalu simpan tanpa mengubah):
- Edit Kelas: "Jenjang Kelas" diisi lewat AJAX oleh JS (nilai ada di `class_level`).
- Edit Diskon, Edit Tagihan (setup) bagian dropdown dependen (Tagihan, Berlaku untuk) yang diisi JS.
- Edit Lokasi Absensi, Edit Pelanggaran Santri, Edit Kegiatan Kepanitiaan, Edit Setup Payroll (select multiple tunjangan).
- Halaman filter/laporan yang memberi nilai awal tahun/bulan (`:old="$year->id"`, `date('n')`, dll.): kini nilai awal itu benar-benar terpilih.
  Bandingkan data yang tampil pertama kali dengan sebelumnya pada: Laporan Tagihan (belum lunas, progres, per santri, total, penagihan SPP berjalan, tunggakan, metode pembayaran),
  Setup Tagihan (index/list/setting), Slip Gaji, Monitoring Akademik, Tabungan, Pembayaran, Dashboard Kasir.

## Kasus login 087728816070 (RIYADI SUROYO)
Bukan akibat bug Edit Pegawai. Akun yang cocok dengan password adalah akun orang tua lama (user 762): data orang tuanya (parent 725)
sudah dihapus lewat Orang Tua -> Hapus pada 2026-10-19 08:21, sedangkan akunnya tetap ada sebagai role `orang-tua` tanpa baris orang tua,
jadi ditolak dengan "Data Siswa Anda belum ada". Akun pegawai (user 1213, status = 1) tidak pernah terkena pengecekan itu.
Login dengan password akun pegawai berhasil. Perbaikan datanya (admin): jadikan akun 762 alias dari 1213
(`UPDATE users SET linked_user_id = 1213 WHERE id = 762`) atau minta pengguna memakai password akun pegawai; lihat
`cek-status-pegawai-dan-akun-yatim.sql` bagian C untuk akun lain yang berkondisi sama.

## Rollback
Kembalikan file di atas ke versi sebelumnya. Tidak ada perubahan data oleh kode hotfix; satu-satunya perubahan data adalah UPDATE pemulihan yang Anda jalankan sendiri.
