<?php
define('BASE','..');
require '../shared/auth.php'; require '../shared/config.php'; require '../shared/layout.php';
require '../shared/pagination.php';
$user = require_role('karyawan'); $uid = $user['id'];
$page = paginate_page();

$today = date('Y-m-d');
$ftgl  = preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['tanggal']??'') ? $_GET['tanggal'] : $today;
$fsts  = in_array($_GET['status']??'',['pending','approved','declined']) ? $_GET['status'] : '';

$where='l.user_id=? AND l.tanggal=?'; $p=[$uid,$ftgl];
if($fsts){ $where.=' AND l.status=?'; $p[]=$fsts; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM logbooks l WHERE $where");
$countStmt->execute($p);
$pg = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page = $pg['page'];

$sumStmt = $db->prepare("SELECT COALESCE(SUM(l.jam),0) FROM logbooks l WHERE $where");
$sumStmt->execute($p);
$totalJam = (float)$sumStmt->fetchColumn();

$s=$db->prepare("SELECT l.*,u.name pic_name FROM logbooks l JOIN users u ON u.id=l.pic_id WHERE $where ORDER BY l.id DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$s->execute($p); $rows=$s->fetchAll();

$baseQs = array_filter(['tanggal' => $ftgl, 'status' => $fsts ?: null]);
$qs = fn(array $extra = []) => page_qs($baseQs, array_merge($page > 1 ? ['page' => $page] : [], $extra));

html_head('Laporan'); nav($user,'Laporan Aktivitas'); flash();
?>
<div class="card">
  <h2>Riwayat Logbook</h2>
  <form method="get" class="filter-bar">
    <label style="font-size:.8rem;color:var(--muted);font-weight:600;margin:0">Tanggal:</label>
    <input type="date" name="tanggal" value="<?=htmlspecialchars($ftgl)?>" max="<?=$today?>">
    <select name="status">
      <option value="">Semua Status</option>
      <?php foreach(['pending'=>'Menunggu','approved'=>'Disetujui','declined'=>'Ditolak'] as $v=>$l): ?>
      <option value="<?=$v?>" <?=$fsts===$v?'selected':''?>><?=$l?></option>
      <?php endforeach ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <?php if($ftgl!==$today): ?>
    <a href="laporan.php" class="btn btn-ghost btn-sm">Hari Ini</a>
    <?php endif ?>
  </form>
  <p style="font-size:.8rem;color:var(--muted);margin-bottom:.75rem">
    Menampilkan: <strong><?=date('d/m/Y',strtotime($ftgl))?></strong>
    <?=$ftgl===$today?' (Hari Ini)':''?>
  </p>
  <?php if(!$rows): ?><p style="color:var(--muted);font-size:.9rem">Tidak ada data untuk tanggal ini.</p>
  <?php else: ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'entri'); ?>
  <div class="table-wrap">
  <table><thead><tr><th>Jam</th><th>Aktivitas</th><th>PIC</th><th>Status</th><th>Catatan PIC</th></tr></thead><tbody>
  <?php foreach($rows as $r): ?>
  <tr><td><?=htmlspecialchars($r['jam'])?></td>
      <td><?=htmlspecialchars($r['aktivitas'])?></td>
      <td><?=htmlspecialchars($r['pic_name'])?></td>
      <td><?=badge($r['status'])?></td>
      <td style="font-size:.8rem;color:var(--muted)"><?=htmlspecialchars($r['catatan_pic']??'—')?></td></tr>
  <?php endforeach ?>
  </tbody></table>
  </div>
  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi laporan'); ?>
  <div style="margin-top:.75rem;font-weight:700;color:var(--primary)">Total: <?=number_format($totalJam,1)?> jam</div>
  <?php endif ?>
</div>
<?php html_foot() ?>
