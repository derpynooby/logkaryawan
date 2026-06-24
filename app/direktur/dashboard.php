<?php
define('BASE','..');
require '../shared/auth.php'; require '../shared/config.php'; require '../shared/layout.php';
require '../shared/pagination.php';
$user = require_role('direktur');
$page = paginate_page();

$s=$db->query("SELECT role,COUNT(*) c FROM users GROUP BY role");
$uc=array_column($s->fetchAll(),'c','role');

$s=$db->query('SELECT status,COUNT(*) c FROM logbooks WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE()) GROUP BY status');
$mc=array_column($s->fetchAll(),'c','status');

$s=$db->query('SELECT COUNT(*) FROM (SELECT user_id FROM logbooks WHERE tanggal=CURDATE() GROUP BY user_id HAVING SUM(jam)>=8) t');
$fulfilled=$s->fetchColumn();

$totalKaryawan = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='karyawan'")->fetchColumn();
$pg = paginate_meta($totalKaryawan, $page);
$page = $pg['page'];

// Ambil data performa karyawan termasuk jumlah jam per status
$s = $db->query("SELECT 
    u.name,
    COALESCE(SUM(l.jam),0) jam,
    SUM(l.status='approved') approved,
    SUM(l.status='pending') pending,
    SUM(l.status='declined') declined,
    COALESCE(SUM(CASE WHEN l.status='approved' THEN l.jam END),0) jam_approved,
    COALESCE(SUM(CASE WHEN l.status='pending' THEN l.jam END),0) jam_pending,
    COALESCE(SUM(CASE WHEN l.status='declined' THEN l.jam END),0) jam_declined
  FROM users u 
  LEFT JOIN logbooks l ON l.user_id=u.id
    AND MONTH(l.tanggal)=MONTH(CURDATE()) AND YEAR(l.tanggal)=YEAR(CURDATE())
  WHERE u.role='karyawan' 
  GROUP BY u.id 
  ORDER BY jam DESC
  LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$kstat=$s->fetchAll();

$qs = fn(array $extra = []) => page_qs(array_merge($page > 1 ? ['page' => $page] : []), $extra);

html_head('Dashboard Eksekutif'); nav($user,'Dashboard Eksekutif');
?>
<div class="grid">
  <div class="stat"><div class="val"><?=$uc['karyawan']??0?></div><div class="lbl">Total Karyawan</div></div>
  <div class="stat"><div class="val"><?=$uc['pic']??0?></div><div class="lbl">Total PIC</div></div>
  <div class="stat"><div class="val"><?=$fulfilled?></div><div class="lbl">Penuhi 8 Jam Hari Ini</div></div>
  <div class="stat"><div class="val"><?=$mc['pending']??0?></div><div class="lbl">Pending Bulan Ini</div></div>
  <div class="stat"><div class="val"><?=$mc['approved']??0?></div><div class="lbl">Disetujui Bulan Ini</div></div>
  <div class="stat"><div class="val"><?=$mc['declined']??0?></div><div class="lbl">Ditolak Bulan Ini</div></div>
</div>
<div class="card">
  <h2>Performa Karyawan — <?=date('F Y')?></h2>
  <?php if(!$kstat): ?>
  <p style="color:var(--muted);font-size:.9rem">Belum ada data karyawan.</p>
  <?php else: ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'karyawan'); ?>
  <div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Nama</th>
        <th>Total Jam</th>
        <th>Disetujui</th>
        <th>Menunggu</th>
        <th>Ditolak</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($kstat as $r): ?>
      <tr>
        <td><?=htmlspecialchars($r['name'])?></td>
        <td><strong><?=number_format($r['jam'],1)?></strong></td>
        <td style="color:var(--success)">
          <?=$r['approved']?> 
          <br>
          <small><?=number_format($r['jam_approved'],1)?> jam</small>
        </td>
        <td style="color:var(--warn)">
          <?=$r['pending']?> 
          <br>
          <small><?=number_format($r['jam_pending'],1)?> jam</small>
        </td>
        <td style="color:var(--danger)">
          <?=$r['declined']?> 
          <br>
          <small><?=number_format($r['jam_declined'],1)?> jam</small>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  </div>
  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi performa'); ?>
  <?php endif ?>
</div>
<?php html_foot() ?>
