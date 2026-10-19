-- Deteksi nilai dropdown/radio yang berubah dari TERISI menjadi KOSONG lewat form edit (SELECT saja).
--
-- Latar belakang: komponen x-form.select / x-form.radio tidak menandai nilai tersimpan (hotfix 2026-10-19).
-- Menyimpan form edit tanpa memilih ulang dropdown bisa mengosongkan kolom itu. Query ini mencari jejaknya
-- di activity_log: baris update yang nilai lamanya terisi dan nilai barunya kosong/null.
--
-- Hanya MODEL YANG MENCATAT ACTIVITY LOG yang bisa diperiksa dan dipulihkan dari log:
--   Employee, Parents, Student, Classroom (class), BillDiscount, Bill, BillType, Allowance, User.
-- MODEL TANPA ACTIVITY LOG (pemulihan hanya dari backup DB sebelum deploy yang memuat bug ini):
--   AttendanceLocation (lokasi absensi: attendance_group_id, attendance_location),
--   ViolationTypes (impact_level, status), StudentViolations (location), CommitteeActivity (related_field, activity_name),
--   StudentAcademic, dan tabel data siswa tambahan (student_parents / student_profiles / student_addresses).
-- Kolom yang NOT NULL di DB tidak bisa dikosongkan (validasi/SQL menolak), jadi hasilnya hanya relevan untuk kolom nullable.
--
-- Ubah tanggal di bawah ('2000-01-01') ke tanggal sebelum bug mulai terasa bila hasilnya terlalu banyak.
-- nilai_lama = nilai yang harus dikembalikan. Untuk pegawai gunakan skrip khusus: pulihkan-jabatan-pegawai.sql.

SELECT c.model, c.kolom,
       al.id AS log_id, al.created_at, al.subject_id, al.causer_id, u.name AS pelaku,
       JSON_UNQUOTE(JSON_EXTRACT(al.properties, CONCAT('$.old.', c.kolom)))        AS nilai_lama,
       JSON_UNQUOTE(JSON_EXTRACT(al.properties, CONCAT('$.attributes.', c.kolom))) AS nilai_baru
FROM activity_log al
JOIN (
    SELECT 'App\\Models\\Employee' AS subject_type, 'Employee' AS model, 'id_position' AS kolom
    UNION ALL SELECT 'App\\Models\\Employee', 'Employee', 'status_employment'
    UNION ALL SELECT 'App\\Models\\Employee', 'Employee', 'status_teacher'
    UNION ALL SELECT 'App\\Models\\Employee', 'Employee', 'marital_status'
    UNION ALL SELECT 'App\\Models\\Parents', 'Parents', 'id_village'
    UNION ALL SELECT 'App\\Models\\Parents', 'Parents', 'gender'
    UNION ALL SELECT 'App\\Models\\Student', 'Student', 'id_asrama'
    UNION ALL SELECT 'App\\Models\\Student', 'Student', 'id_halaqah'
    UNION ALL SELECT 'App\\Models\\Student', 'Student', 'id_class'
    UNION ALL SELECT 'App\\Models\\Student', 'Student', 'religion'
    UNION ALL SELECT 'App\\Models\\Student', 'Student', 'gender'
    UNION ALL SELECT 'App\\Models\\Student', 'Student', 'beasiswa'
    UNION ALL SELECT 'App\\Models\\Student', 'Student', 'status'
    UNION ALL SELECT 'App\\Models\\Classroom', 'Classroom', 'id_wali_kelas'
    UNION ALL SELECT 'App\\Models\\Classroom', 'Classroom', 'level_education'
    UNION ALL SELECT 'App\\Models\\Classroom', 'Classroom', 'level_class'
    UNION ALL SELECT 'App\\Models\\BillDiscount', 'BillDiscount', 'id_year'
    UNION ALL SELECT 'App\\Models\\BillDiscount', 'BillDiscount', 'id_bill'
    UNION ALL SELECT 'App\\Models\\Bill', 'Bill', 'id_year'
    UNION ALL SELECT 'App\\Models\\Bill', 'Bill', 'start_month'
    UNION ALL SELECT 'App\\Models\\Bill', 'Bill', 'start_year'
    UNION ALL SELECT 'App\\Models\\Bill', 'Bill', 'end_month'
    UNION ALL SELECT 'App\\Models\\Bill', 'Bill', 'end_year'
    UNION ALL SELECT 'App\\Models\\BillType', 'BillType', 'period'
    UNION ALL SELECT 'App\\Models\\BillType', 'BillType', 'spp'
    UNION ALL SELECT 'App\\Models\\Allowance', 'Allowance', 'category'
    UNION ALL SELECT 'App\\Models\\User', 'User', 'gender'
) c ON c.subject_type = al.subject_type
LEFT JOIN users u ON u.id = al.causer_id
WHERE al.created_at >= '2000-01-01'
  AND al.event = 'updated'
  AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, CONCAT('$.old.', c.kolom))) IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, CONCAT('$.old.', c.kolom))) NOT IN ('null', '')
  AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, CONCAT('$.attributes.', c.kolom))) IN ('null', '')
ORDER BY al.id DESC;
