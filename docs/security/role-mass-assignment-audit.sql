-- Audit: was the users.role mass-assignment hole (profile update / unprotected user.* routes) abused?
-- READ-ONLY. Every statement is a SELECT. Run against production and review the rows manually.
-- MySQL 5.7+ / MariaDB 10.2+ (JSON_EXTRACT).

-- 1. Staff-role users that look like parents: have a parent row, no employee row.
--    A parent who escalated through the profile form would land here.
SELECT u.id, u.name, u.phone, u.email, u.role, u.created_at, u.updated_at, u.updated_by,
       p.id AS parent_id, p.name AS parent_name
FROM users u
JOIN parent p ON p.id_user = u.id
LEFT JOIN employee e ON e.id_user = u.id
WHERE u.role <> 'orang-tua'
  AND e.id IS NULL
ORDER BY u.updated_at DESC;

-- 2. Every role change recorded by activity_log (User logs 'role' via LogsActivity).
--    Suspicious when the causer is the user themself, or the causer is not admin/super-admin.
SELECT a.id, a.created_at, a.subject_id AS user_id, su.name AS user_name,
       JSON_UNQUOTE(JSON_EXTRACT(a.properties, '$.old.role'))        AS role_before,
       JSON_UNQUOTE(JSON_EXTRACT(a.properties, '$.attributes.role')) AS role_after,
       a.causer_id, cu.name AS causer_name, cu.role AS causer_role_now,
       (a.causer_id = a.subject_id) AS self_change
FROM activity_log a
LEFT JOIN users su ON su.id = a.subject_id
LEFT JOIN users cu ON cu.id = a.causer_id
WHERE a.subject_type = 'App\\Models\\User'
  AND a.event = 'updated'
  AND JSON_EXTRACT(a.properties, '$.old.role') IS NOT NULL
  -- old.role is JSON null on the update right after a user is created (email rewrite): not a role change
  AND JSON_TYPE(JSON_EXTRACT(a.properties, '$.old.role')) <> 'NULL'
  AND JSON_UNQUOTE(JSON_EXTRACT(a.properties, '$.old.role'))
      <> JSON_UNQUOTE(JSON_EXTRACT(a.properties, '$.attributes.role'))
ORDER BY a.created_at DESC;

-- 3. Users created by someone who is not admin/super-admin (unprotected POST /user),
--    or created with a privileged role at all. Review the whole list against known staff.
SELECT u.id, u.name, u.phone, u.email, u.role, u.branch_id, u.created_at,
       u.created_by, cu.name AS creator_name, cu.role AS creator_role
FROM users u
LEFT JOIN users cu ON cu.id = u.created_by
WHERE u.role IN ('super-admin', 'admin', 'kasir', 'bendahara', 'penanggung-jawab-tabungan', 'wali-kelas')
   OR (u.created_by > 0 AND cu.role NOT IN ('admin', 'super-admin'))
ORDER BY u.role, u.created_at DESC;

-- 4. Password / branch / role changed on someone else's account by a non-admin
--    (unprotected PUT /user/{user}).
SELECT a.id, a.created_at, a.subject_id AS user_id, su.name AS user_name, su.role AS user_role,
       a.causer_id, cu.name AS causer_name, cu.role AS causer_role_now,
       JSON_EXTRACT(a.properties, '$.old.password') <> JSON_EXTRACT(a.properties, '$.attributes.password') AS password_changed,
       JSON_EXTRACT(a.properties, '$.old.branch_id') <> JSON_EXTRACT(a.properties, '$.attributes.branch_id') AS branch_changed,
       JSON_EXTRACT(a.properties, '$.old.role') <> JSON_EXTRACT(a.properties, '$.attributes.role') AS role_changed
FROM activity_log a
LEFT JOIN users su ON su.id = a.subject_id
LEFT JOIN users cu ON cu.id = a.causer_id
WHERE a.subject_type = 'App\\Models\\User'
  AND a.event = 'updated'
  AND a.causer_id IS NOT NULL
  AND a.causer_id <> a.subject_id
  AND (cu.role IS NULL OR cu.role NOT IN ('admin', 'super-admin'))
ORDER BY a.created_at DESC;

-- 5. Users soft-deleted by a non-admin (unprotected DELETE /user/{user}).
SELECT u.id, u.name, u.role, u.deleted_at, u.deleted_by, du.name AS deleter_name, du.role AS deleter_role
FROM users u
LEFT JOIN users du ON du.id = u.deleted_by
WHERE u.deleted_at IS NOT NULL
  AND (du.role IS NULL OR du.role NOT IN ('admin', 'super-admin'))
ORDER BY u.deleted_at DESC;

-- 6. Role / privileged-account counts per branch, to compare against the expected staff list.
SELECT branch_id, role, COUNT(*) AS total
FROM users
WHERE deleted_at IS NULL
GROUP BY branch_id, role
ORDER BY branch_id, role;
