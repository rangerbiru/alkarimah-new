-- Akun dengan nomor HP kembar: nomor yang dipakai lebih dari satu AKUN TUJUAN (SELECT saja).
--
-- Nomor dibandingkan dengan normalisasi yang sama seperti login (App\Helpers\Phone::normalize):
-- buang "-", spasi, dan titik; awalan +62 / 62 menjadi 0. Nomor kosong dan "0" diabaikan.
-- Akun tujuan = akun itu sendiri, atau akun pegawai bila akun itu alias (users.linked_user_id).
--
-- Login dengan nomor seperti ini:
--   * password hanya cocok dengan satu akun tujuan -> langsung masuk;
--   * password cocok dengan beberapa akun tujuan -> halaman "Pilih Akun".
-- Daftar ini adalah daftar yang perlu dibereskan admin; kesamaan password tidak bisa dicek dari DB.
--
-- Jenis akun per baris:  E = punya data pegawai aktif;  P = role orang-tua tanpa data pegawai;
--                        S = role staf lain tanpa data pegawai (mis. kasir, wali kelas);  ALIAS = sudah digabung.
-- Kategori (nama dianggap sama bila kata pertama nama berbunyi sama, 4 karakter SOUNDEX; periksa kolom akun):
--   a  = pegawai (E) dan akun orang tua (P) orang yang sama, belum digabung
--        -> admin: Orang Tua -> Edit -> "Gabungkan dengan akun pegawai".
--   b  = akun staf tanpa data pegawai (S) dan akun orang tua (P) orang yang sama
--        -> tidak bisa digabung dengan desain sekarang; pakai halaman Pilih Akun, atau buatkan data pegawainya lalu gabungkan.
--   c1 = dua atau lebih akun orang tua (P) orang yang sama (data orang tua ganda)
--        -> admin: pindahkan anak ke satu data orang tua, lalu hapus data orang tua yang ganda (akunnya ikut dinonaktifkan).
--   c2 = orang berbeda atau tidak jelas (nama berbeda, atau kombinasi lain seperti beberapa akun admin)
--        -> admin: betulkan nomor HP salah satu akun.

-- 1. Daftar lengkap
SELECT g.nomor, g.akun_tujuan,
       CASE WHEN g.nama_berbeda = 1 AND g.n_e > 0 AND g.n_p > 0 THEN 'a'
            WHEN g.nama_berbeda = 1 AND g.n_e = 0 AND g.n_s > 0 AND g.n_p > 0 THEN 'b'
            WHEN g.nama_berbeda = 1 AND g.n_e = 0 AND g.n_s = 0 AND g.n_p >= 2 THEN 'c1'
            ELSE 'c2' END AS kategori,
       g.akun
FROM (
    SELECT x.nomor,
           COUNT(DISTINCT x.target_id) AS akun_tujuan,
           SUM(x.jenis = 'E') AS n_e,
           SUM(x.jenis = 'P') AS n_p,
           SUM(x.jenis = 'S') AS n_s,
           COUNT(DISTINCT CASE WHEN x.jenis <> 'ALIAS' THEN LEFT(SOUNDEX(x.nama_depan), 4) END) AS nama_berbeda,
           GROUP_CONCAT(CONCAT('#', x.id, ' ', x.role,
                               IF(x.jenis = 'ALIAS', CONCAT(' ALIAS->#', x.target_id), ''),
                               ' ', x.name,
                               ' | pegawai=', IFNULL(x.emp_name, '-'),
                               ' | ortu=', IFNULL(x.parent_name, '-'), ' (anak ', x.anak, ')')
                        ORDER BY x.id SEPARATOR ' ;; ') AS akun
    FROM (
        SELECT u.id, u.name, u.role,
               (CASE WHEN REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', '') LIKE '+62%'
                       THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', ''), 4))
                  WHEN REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', '') LIKE '62%'
                       THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', ''), 3))
                  ELSE REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', '') END) AS nomor,
               IFNULL(u.linked_user_id, u.id) AS target_id,
               SUBSTRING_INDEX(TRIM(REPLACE(REPLACE(u.name, ',', ' '), '.', ' ')), ' ', 1) AS nama_depan,
               e.name AS emp_name,
               p.name AS parent_name,
               (SELECT COUNT(*) FROM student s WHERE s.id_parent = p.id AND s.deleted_at IS NULL) AS anak,
               CASE WHEN u.linked_user_id IS NOT NULL THEN 'ALIAS'
                    WHEN e.id IS NOT NULL THEN 'E'
                    WHEN u.role = 'orang-tua' THEN 'P'
                    ELSE 'S' END AS jenis
        FROM users u
        LEFT JOIN employee e ON e.id_user = u.id AND e.deleted_at IS NULL
        LEFT JOIN parent p   ON p.id_user = u.id AND p.deleted_at IS NULL
        WHERE u.deleted_at IS NULL
    ) x
    WHERE x.nomor NOT IN ('', '0')
    GROUP BY x.nomor
    HAVING COUNT(DISTINCT x.target_id) > 1
) g
ORDER BY kategori, g.nomor;

-- 2. Ringkasan per kategori
SELECT t.kategori, COUNT(*) AS jumlah_nomor
FROM (
SELECT g.nomor, g.akun_tujuan,
       CASE WHEN g.nama_berbeda = 1 AND g.n_e > 0 AND g.n_p > 0 THEN 'a'
            WHEN g.nama_berbeda = 1 AND g.n_e = 0 AND g.n_s > 0 AND g.n_p > 0 THEN 'b'
            WHEN g.nama_berbeda = 1 AND g.n_e = 0 AND g.n_s = 0 AND g.n_p >= 2 THEN 'c1'
            ELSE 'c2' END AS kategori,
       g.akun
FROM (
    SELECT x.nomor,
           COUNT(DISTINCT x.target_id) AS akun_tujuan,
           SUM(x.jenis = 'E') AS n_e,
           SUM(x.jenis = 'P') AS n_p,
           SUM(x.jenis = 'S') AS n_s,
           COUNT(DISTINCT CASE WHEN x.jenis <> 'ALIAS' THEN LEFT(SOUNDEX(x.nama_depan), 4) END) AS nama_berbeda,
           GROUP_CONCAT(CONCAT('#', x.id, ' ', x.role,
                               IF(x.jenis = 'ALIAS', CONCAT(' ALIAS->#', x.target_id), ''),
                               ' ', x.name,
                               ' | pegawai=', IFNULL(x.emp_name, '-'),
                               ' | ortu=', IFNULL(x.parent_name, '-'), ' (anak ', x.anak, ')')
                        ORDER BY x.id SEPARATOR ' ;; ') AS akun
    FROM (
        SELECT u.id, u.name, u.role,
               (CASE WHEN REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', '') LIKE '+62%'
                       THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', ''), 4))
                  WHEN REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', '') LIKE '62%'
                       THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', ''), 3))
                  ELSE REPLACE(REPLACE(REPLACE(u.phone, '-', ''), ' ', ''), '.', '') END) AS nomor,
               IFNULL(u.linked_user_id, u.id) AS target_id,
               SUBSTRING_INDEX(TRIM(REPLACE(REPLACE(u.name, ',', ' '), '.', ' ')), ' ', 1) AS nama_depan,
               e.name AS emp_name,
               p.name AS parent_name,
               (SELECT COUNT(*) FROM student s WHERE s.id_parent = p.id AND s.deleted_at IS NULL) AS anak,
               CASE WHEN u.linked_user_id IS NOT NULL THEN 'ALIAS'
                    WHEN e.id IS NOT NULL THEN 'E'
                    WHEN u.role = 'orang-tua' THEN 'P'
                    ELSE 'S' END AS jenis
        FROM users u
        LEFT JOIN employee e ON e.id_user = u.id AND e.deleted_at IS NULL
        LEFT JOIN parent p   ON p.id_user = u.id AND p.deleted_at IS NULL
        WHERE u.deleted_at IS NULL
    ) x
    WHERE x.nomor NOT IN ('', '0')
    GROUP BY x.nomor
    HAVING COUNT(DISTINCT x.target_id) > 1
) g
) t
GROUP BY t.kategori
ORDER BY t.kategori;
