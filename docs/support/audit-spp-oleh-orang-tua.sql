-- Audit: perubahan kolom spp pada santri yang dilakukan oleh akun orang tua (SELECT saja).
--
-- Sebelum hotfix form edit santri untuk orang tua, input "SPP" ikut tersimpan lewat $request->all().
-- Setelah hotfix, input itu dihapus dan nilainya dibuang oleh validated().
-- Student memakai activity log (kolom spp ikut dicatat), jadi perubahan lama masih bisa dilacak di sini.
--
-- Dihitung sebagai "oleh orang tua": pelaku ber-role orang-tua, ATAU akun lain (pegawai dua peran)
-- yang punya baris orang tua aktif dan bukan admin/super-admin.
-- Kolom spp_lama = nilai sebelum diubah; gunakan untuk memulihkan bila perubahannya tidak sah.

SELECT al.id            AS log_id,
       al.created_at,
       al.subject_id    AS student_id,
       s.nis,
       s.name           AS santri,
       al.causer_id,
       u.name           AS pelaku,
       u.role           AS role_pelaku,
       JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.spp'))        AS spp_lama,
       JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.attributes.spp')) AS spp_baru,
       s.spp            AS spp_sekarang
FROM activity_log al
JOIN users u        ON u.id = al.causer_id
LEFT JOIN student s ON s.id = al.subject_id
WHERE al.subject_type = 'App\\Models\\Student'
  AND al.causer_type  = 'App\\Models\\User'
  AND al.event        = 'updated'
  AND JSON_EXTRACT(al.properties, '$.attributes.spp') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.spp')) IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.spp')) <> JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.attributes.spp'))
  AND ( u.role = 'orang-tua'
        OR ( u.role NOT IN ('admin', 'super-admin')
             AND EXISTS (SELECT 1 FROM parent p WHERE p.id_user = u.id AND p.deleted_at IS NULL) ) )
ORDER BY al.id DESC;

-- Catatan: perubahan yang terjadi saat spp awalnya NULL tidak tercatat sebagai "old" di log
-- (old.spp = null), jadi tidak muncul di atas. Cek tambahan untuk kasus itu:
SELECT al.id AS log_id, al.created_at, al.subject_id AS student_id, al.causer_id, u.name AS pelaku, u.role AS role_pelaku,
       JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.attributes.spp')) AS spp_baru
FROM activity_log al
JOIN users u ON u.id = al.causer_id
WHERE al.subject_type = 'App\\Models\\Student'
  AND al.causer_type  = 'App\\Models\\User'
  AND al.event        = 'updated'
  AND JSON_EXTRACT(al.properties, '$.attributes.spp') IS NOT NULL
  AND (JSON_EXTRACT(al.properties, '$.old.spp') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(al.properties, '$.old.spp')) IN ('null', ''))
  AND ( u.role = 'orang-tua'
        OR ( u.role NOT IN ('admin', 'super-admin')
             AND EXISTS (SELECT 1 FROM parent p WHERE p.id_user = u.id AND p.deleted_at IS NULL) ) )
ORDER BY al.id DESC;
