<?php
define('BASE', '..');
require '../shared/auth.php';
require '../shared/config.php';
require '../shared/layout.php';
require '../shared/pagination.php';
$user = require_role('karyawan');
$uid = $user['id'];
$page = paginate_page();

$today = today_hours($db, $uid);
$s = $db->prepare('SELECT COALESCE(SUM(jam),0) FROM logbooks WHERE user_id=? AND MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())');
$s->execute([$uid]);
$bulan = (float)$s->fetchColumn();
 
$s = $db->prepare('SELECT status,COUNT(*) c FROM logbooks WHERE user_id=? GROUP BY status');
$s->execute([$uid]);
$cnt = array_column($s->fetchAll(), 'c', 'status');

$s = $db->prepare('SELECT COUNT(*) FROM logbooks WHERE user_id=?');
$s->execute([$uid]);
$pg = paginate_meta((int)$s->fetchColumn(), $page);
$page = $pg['page'];

$s = $db->prepare('SELECT l.*,u.name pic_name FROM logbooks l JOIN users u ON u.id=l.pic_id WHERE l.user_id=? ORDER BY l.tanggal DESC,l.id DESC LIMIT ' . $pg['perPage'] . ' OFFSET ' . $pg['offset']);
$s->execute([$uid]);
$recent = $s->fetchAll();

$qs = fn(array $extra = []) => page_qs(array_merge($page > 1 ? ['page' => $page] : []), $extra);

html_head('Dashboard');
nav($user, 'Dashboard');
?>
<?php if ($today < 8): ?>
<div class="alert alert-warn">
    ⚠️ Akumulasi jam kerja hari ini baru <strong><?= number_format($today, 1) ?> jam</strong>. Minimal <strong>8 jam</strong>.
</div>
<?php endif ?>

<div class="grid" style="margin-bottom:1rem;">
  <div class="stat">
    <div class="val"><?= number_format($today, 1) ?></div>
    <div class="lbl">Jam Hari Ini</div>
  </div>
  <div class="stat">
    <div class="val"><?= number_format($bulan, 1) ?></div>
    <div class="lbl">Jam Bulan Ini</div>
  </div>
  <div class="stat">
    <div class="val"><?= $cnt['approved'] ?? 0 ?></div>
    <div class="lbl">Disetujui</div>
  </div>
  <div class="stat">
    <div class="val"><?= $cnt['pending'] ?? 0 ?></div>
    <div class="lbl">Menunggu</div>
  </div>
  <div class="stat">
    <div class="val"><?= $cnt['declined'] ?? 0 ?></div>
    <div class="lbl">Ditolak</div>
  </div>
</div>
<div class="card">
  <h2>Aktivitas Terbaru</h2>
  <?php if (!$recent): ?>
    <p style="color:var(--muted);font-size:.9rem">Belum ada logbook.</p>
  <?php else: ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'aktivitas'); ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Jam</th>
          <th>Aktivitas</th>
          <th>PIC</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['tanggal']) ?></td>
          <td><?= htmlspecialchars($r['jam']) ?></td>
          <td><?= htmlspecialchars(mb_strimwidth($r['aktivitas'], 0, 60, '…')) ?></td>
          <td><?= htmlspecialchars($r['pic_name']) ?></td>
          <td><?= badge($r['status']) ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi aktivitas'); ?>
  <?php endif ?>
  <div style="margin-top:1rem">
    <a href="laporan.php" class="btn btn-primary btn-sm" style="min-width:140px;display:inline-block;">Lihat Semua →</a>
  </div>
</div>
<?php html_foot() ?>
