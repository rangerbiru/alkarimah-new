-- Pemeriksaan data untuk kasus login "Data Siswa Anda belum ada" pada pegawai yang juga orang tua (SELECT saja).

-- A. Pegawai berstatus NONAKTIF eksplisit (employee.status = 0). Hanya mereka yang diperlakukan sebagai
--    "orang tua saja" saat login. Pastikan tidak ada yang nonaktif karena salah input.
--    Catatan: kolom employee.status NOT NULL dan tidak ada input status di form Edit Pegawai, jadi form tidak bisa mengosongkannya.
SELECT e.id, e.nip, e.name, e.status, e.status_employment, e.id_user, u.role, e.updated_at, e.updated_by, w.name AS diubah_oleh,
       EXISTS (SELECT 1 FROM parent p WHERE p.id_user = e.id_user AND p.deleted_at IS NULL) AS juga_orang_tua
FROM employee e
LEFT JOIN users u ON u.id = e.id_user
LEFT JOIN users w ON w.id = e.updated_by
WHERE e.deleted_at IS NULL AND e.status = 0
ORDER BY e.updated_at DESC;

-- B. Status pegawai di luar nilai yang dikenal (status_employment harus 1/2/3; status harus 0/1).
SELECT e.id, e.nip, e.name, e.status, e.status_employment, e.status_teacher, e.updated_at, e.updated_by
FROM employee e
WHERE e.deleted_at IS NULL
  AND (e.status_employment NOT IN (1, 2, 3) OR e.status NOT IN (0, 1))
ORDER BY e.updated_at DESC;

-- C. Akun orang tua "yatim": role orang-tua, belum menjadi alias, tetapi tidak punya baris orang tua aktif
--    (data orang tuanya dihapus lewat Orang Tua -> Hapus, yang tidak menghapus akunnya).
--    Mereka ditolak saat login dengan "Data Siswa Anda belum ada". Kolom akun_lain_nomor_sama menunjukkan
--    akun lain (mis. pegawai) dengan nomor yang sama: kandidat orang yang sama, perlu dicek admin.
SELECT u.id, u.name, u.phone, u.created_at, u.lastlogin_at,
       (SELECT GROUP_CONCAT(CONCAT('#', s.id, ' ', s.role, ' ', s.name) SEPARATOR ' ;; ')
          FROM users s
         WHERE s.id <> u.id AND s.deleted_at IS NULL
           AND REPLACE(u.phone, '-', '') NOT IN ('', '0')
           AND REPLACE(s.phone, '-', '') = REPLACE(u.phone, '-', '')) AS akun_lain_nomor_sama,
       (SELECT MAX(p.deleted_at) FROM parent p WHERE p.id_user = u.id) AS data_ortu_dihapus_pada
FROM users u
WHERE u.role = 'orang-tua' AND u.deleted_at IS NULL AND u.linked_user_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM parent p WHERE p.id_user = u.id AND p.deleted_at IS NULL)
ORDER BY u.id;
