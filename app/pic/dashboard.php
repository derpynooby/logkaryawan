<?php
define('BASE','..');
require '../shared/auth.php'; require '../shared/config.php'; require '../shared/layout.php';
require '../shared/pagination.php';
$user = require_role('pic'); $uid = $user['id'];
$page = paginate_page();

$s=$db->prepare('SELECT status,COUNT(*) c FROM logbooks WHERE pic_id=? GROUP BY status');
$s->execute([$uid]); $cnt=array_column($s->fetchAll(),'c','status');

$s=$db->prepare('SELECT COUNT(DISTINCT user_id) FROM logbooks WHERE pic_id=?');
$s->execute([$uid]); $team=$s->fetchColumn();

$countStmt = $db->prepare('SELECT COUNT(*) FROM logbooks l WHERE l.pic_id=? AND l.status="pending"');
$countStmt->execute([$uid]);
$pg = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page = $pg['page'];

$s=$db->prepare('SELECT l.*,u.name nm FROM logbooks l JOIN users u ON u.id=l.user_id WHERE l.pic_id=? AND l.status="pending" ORDER BY l.tanggal DESC,l.id DESC LIMIT '.$pg['perPage'].' OFFSET '.$pg['offset']);
$s->execute([$uid]); $pending=$s->fetchAll();

$qs = fn(array $extra = []) => page_qs(array_merge($page > 1 ? ['page' => $page] : []), $extra);

html_head('Dashboard PIC'); nav($user,'Dashboard PIC'); flash();
?>
<div class="grid">
  <div class="stat"><div class="val"><?=$cnt['pending']??0?></div><div class="lbl">Menunggu Persetujuan</div></div>
  <div class="stat"><div class="val"><?=$cnt['approved']??0?></div><div class="lbl">Disetujui</div></div>
  <div class="stat"><div class="val"><?=$cnt['declined']??0?></div><div class="lbl">Ditolak</div></div>
  <div class="stat"><div class="val"><?=$team?></div><div class="lbl">Anggota Tim</div></div>
</div>
<div class="card">
  <h2>Antrian Persetujuan</h2>
  <?php if(!$pending): ?><p style="color:var(--muted);font-size:.9rem">Tidak ada logbook yang menunggu.</p>
  <?php else: ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'antrian'); ?>
  <div class="table-wrap">
  <table><thead><tr><th>Karyawan</th><th>Tanggal</th><th>Jumlah Jam</th><th>Aktivitas</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach($pending as $r): ?>
  <tr><td><?=htmlspecialchars($r['nm'])?></td><td><?=htmlspecialchars($r['tanggal'])?></td>
      <td><?=htmlspecialchars($r['jam'])?></td>
      <td><?=htmlspecialchars(mb_strimwidth($r['aktivitas'],0,55,'…'))?></td>
      <td>
        <div class="btn-group">
        <form method="post" action="action.php" style="display:inline">
          <input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?=$r['id']?>">
          <?= csrf_field() ?>
          <button class="btn btn-success btn-sm">✓ Setuju</button>
        </form>
        <a href="logbook.php?review=<?=$r['id']?>" class="btn btn-danger btn-sm">✗ Tolak</a>
        </div>
      </td></tr>
  <?php endforeach ?>
  </tbody></table>
  </div>
  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi antrian'); ?>
  <?php endif ?>
  <div style="margin-top:1rem"><a href="logbook.php" class="btn btn-primary btn-sm">Lihat Semua →</a></div>
</div>
<?php html_foot() ?>
