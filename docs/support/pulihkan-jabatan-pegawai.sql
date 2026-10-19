-- Pemulihan jabatan pegawai yang terhapus lewat form Edit Pegawai (hotfix dropdown 2026-10-19).
--
-- Penyebab: form edit tidak menandai jabatan yang tersimpan, sehingga menyimpan form tanpa memilih ulang
-- mengirim id_position kosong -> NULL. Tabel employee memakai activity log yang menyimpan nilai lama, jadi
-- jabatan lama bisa dipulihkan dari log tanpa backup.
--
-- URUTAN: (1) deploy hotfix dulu, (2) jalankan LANGKAH 1 dan periksa hasilnya,
--         (3) jalankan LANGKAH 2 untuk menghasilkan UPDATE, tinjau, lalu jalankan UPDATE yang dipilih.
-- SKRIP INI TIDAK MENGUBAH DATA SAMPAI ANDA MENJALANKAN PERNYATAAN UPDATE HASIL LANGKAH 2 SECARA MANUAL.
--
-- Aturan pemilihan:
--   * hanya pegawai aktif (belum dihapus) yang jabatannya KOSONG SEKARANG (yang sudah diisi ulang admin tidak ditimpa),
--   * jabatan yang dipulihkan = nilai lama dari log pengosongan TERAKHIR pegawai itu,
--   * jabatan lama itu harus masih ada (belum dihapus) di tabel position.
-- Pengosongan yang disengaja admin tidak bisa dibedakan dari yang tidak sengaja: periksa kolom dikosongkan_pada
-- dan dikosongkan_oleh di LANGKAH 1, dan buang pegawai yang memang sengaja dikosongkan dari UPDATE.

-- LANGKAH 1: pratinjau (SELECT saja)
SELECT x.employee_id, e.nip, e.name AS pegawai, e.id_position AS jabatan_sekarang,
       x.jabatan_lama, p.name AS nama_jabatan_lama,
       x.log_id, x.dikosongkan_pada, x.causer_id, u.name AS dikosongkan_oleh
FROM (
    SELECT l.subject_id AS employee_id, l.id AS log_id, l.created_at AS dikosongkan_pada, l.causer_id,
           CAST(JSON_UNQUOTE(JSON_EXTRACT(l.properties, '$.old.id_position')) AS UNSIGNED) AS jabatan_lama
    FROM activity_log l
    JOIN (
        SELECT al.subject_id, MAX(al.id) AS log_id
        FROM activity_log al
        WHERE al.subject_type = 'App\\Models\\Employee'
          AND al.event = 'updated'
          AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.id_position')) NOT IN ('null', '')
          AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.id_position')) IS NOT NULL
          AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.attributes.id_position')) IN ('null', '')
        GROUP BY al.subject_id
    ) last ON last.log_id = l.id
) x
JOIN employee e      ON e.id = x.employee_id AND e.deleted_at IS NULL AND e.id_position IS NULL
JOIN position p      ON p.id = x.jabatan_lama AND p.deleted_at IS NULL
LEFT JOIN users u    ON u.id = x.causer_id
ORDER BY x.dikosongkan_pada DESC;

-- LANGKAH 2: menghasilkan pernyataan UPDATE (masih SELECT; tidak mengubah data).
-- Salin baris hasilnya yang disetujui, lalu jalankan di dalam transaksi: START TRANSACTION; ...; (periksa) COMMIT;
-- Setiap UPDATE punya syarat id_position IS NULL, jadi tidak menimpa pegawai yang sudah diisi ulang.
SELECT CONCAT('UPDATE employee SET id_position = ', x.jabatan_lama,
              ' WHERE id = ', x.employee_id, ' AND id_position IS NULL AND deleted_at IS NULL;',
              '  -- ', e.name, ' (', p.name, ')') AS perintah_update
FROM (
    SELECT l.subject_id AS employee_id,
           CAST(JSON_UNQUOTE(JSON_EXTRACT(l.properties, '$.old.id_position')) AS UNSIGNED) AS jabatan_lama
    FROM activity_log l
    JOIN (
        SELECT al.subject_id, MAX(al.id) AS log_id
        FROM activity_log al
        WHERE al.subject_type = 'App\\Models\\Employee'
          AND al.event = 'updated'
          AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.id_position')) NOT IN ('null', '')
          AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.id_position')) IS NOT NULL
          AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.attributes.id_position')) IN ('null', '')
        GROUP BY al.subject_id
    ) last ON last.log_id = l.id
) x
JOIN employee e ON e.id = x.employee_id AND e.deleted_at IS NULL AND e.id_position IS NULL
JOIN position p ON p.id = x.jabatan_lama AND p.deleted_at IS NULL
ORDER BY x.employee_id;
