<?php
define('BASE', '..');
require '../shared/auth.php';
require '../shared/config.php';
require '../shared/layout.php';
require '../shared/pagination.php';

$user = require_role('admin');
$page = paginate_page();

$sql = "SELECT
    SUM(CASE WHEN a.last_log >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS aktif,
    SUM(CASE WHEN a.last_log IS NULL OR a.last_log < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS nonaktif,
    COUNT(*) AS total
FROM (
    SELECT u.id, MAX(l.tanggal) AS last_log
    FROM users u
    LEFT JOIN logbooks l ON l.user_id = u.id
    WHERE u.role = 'karyawan'
    GROUP BY u.id
) a";
$sum = $db->query($sql)->fetch() ?: ['aktif' => 0, 'nonaktif' => 0, 'total' => 0];

$inactiveCount = (int)$db->query("SELECT COUNT(*) FROM (
    SELECT u.id, MAX(l.tanggal) AS last_log
    FROM users u
    LEFT JOIN logbooks l ON l.user_id = u.id
    WHERE u.role = 'karyawan'
    GROUP BY u.id
    HAVING last_log IS NULL OR last_log < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
) t")->fetchColumn();

$pg = paginate_meta($inactiveCount, $page);
$page = $pg['page'];

$recentInactiveStmt = $db->query("SELECT u.name, u.email, MAX(l.tanggal) AS last_log
    FROM users u
    LEFT JOIN logbooks l ON l.user_id = u.id
    WHERE u.role = 'karyawan'
    GROUP BY u.id
    HAVING last_log IS NULL OR last_log < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY last_log IS NULL DESC, last_log ASC, u.name ASC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$recentInactive = $recentInactiveStmt->fetchAll();

$qs = fn(array $extra = []) => page_qs(array_merge($page > 1 ? ['page' => $page] : []), $extra);

html_head('Dashboard Admin');
nav($user, 'Dashboard Admin');
flash();
?>
<div class="grid">
  <div class="stat">
    <div class="val"><?= (int)$sum['total'] ?></div>
    <div class="lbl">Total Karyawan</div>
  </div>
  <div class="stat">
    <div class="val"><?= (int)$sum['aktif'] ?></div>
    <div class="lbl">Pengguna Aktif (7 hari)</div>
  </div>
  <div class="stat">
    <div class="val"><?= (int)$sum['nonaktif'] ?></div>
    <div class="lbl">Pengguna Non Aktif</div>
  </div>
</div>

<div class="card">
  <h2>Pengguna Non Aktif</h2>
  <?php if (!$recentInactive): ?>
    <p style="color:var(--muted);font-size:.9rem">Semua karyawan aktif dalam 7 hari terakhir.</p>
  <?php else: ?>
    <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'pengguna non aktif'); ?>
    <div class="table-wrap">
      <table>
        <thead>
        <tr><th>Nama</th><th>Email</th><th>Logbook Terakhir</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recentInactive as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= $r['last_log'] ? htmlspecialchars($r['last_log']) : 'Belum pernah input' ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi non aktif'); ?>
  <?php endif ?>
  <div style="margin-top:1rem">
    <a class="btn btn-primary btn-sm" href="karyawan.php">Kelola Data Karyawan</a>
  </div>
</div>
<?php html_foot() ?>
