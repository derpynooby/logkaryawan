<?php
define('BASE','..');
require '../shared/auth.php'; require '../shared/config.php'; require '../shared/layout.php';
require '../shared/pagination.php';
require '../shared/modal.php';
$user = require_role('pic'); $uid = $user['id'];
$page = paginate_page();

$review = (int)($_GET['review']??0);
$today  = date('Y-m-d');
$ftgl   = preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['tanggal']??'') ? $_GET['tanggal'] : $today;
if ($ftgl > $today) $ftgl = $today; // BUG-08: block future dates
$fuid   = (int)($_GET['user_id']??0);
$fsts   = in_array($_GET['status']??'',['pending','approved','declined']) ? $_GET['status'] : '';

$where='l.pic_id=?'; $p=[$uid];
if($fuid){ $where.=' AND l.user_id=?'; $p[]=$fuid; }
if($fsts){ $where.=' AND l.status=?'; $p[]=$fsts; }
$where.=' AND l.tanggal=?'; $p[]=$ftgl;

$countStmt = $db->prepare("SELECT COUNT(*) FROM logbooks l WHERE $where");
$countStmt->execute($p);
$pg = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page = $pg['page'];

$s=$db->prepare("SELECT l.*,u.name nm FROM logbooks l JOIN users u ON u.id=l.user_id WHERE $where ORDER BY l.id DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$s->execute($p); $rows=$s->fetchAll();

$emps=$db->prepare('SELECT DISTINCT u.id,u.name FROM users u JOIN logbooks l ON l.user_id=u.id WHERE l.pic_id=? ORDER BY u.name');
$emps->execute([$uid]); $emps=$emps->fetchAll();

$baseQs = array_filter(['tanggal' => $ftgl, 'user_id' => $fuid ?: null, 'status' => $fsts ?: null]);
$qs = fn(array $extra = []) => page_qs($baseQs, array_merge($page > 1 ? ['page' => $page] : [], $extra));

// Cek approve all: hanya tampil bila memilih satu karyawan dan ada logbook pending
$showApproveAll = false;
$pendingLogbookIds = [];
if ($fuid && $ftgl) {
    $stmt = $db->prepare("SELECT id FROM logbooks l WHERE l.pic_id=? AND l.user_id=? AND l.tanggal=? AND l.status='pending'");
    $stmt->execute([$uid, $fuid, $ftgl]);
    $pendingLogbookIds = array_column($stmt->fetchAll(), 'id');
    if (count($pendingLogbookIds) > 0) {
        $showApproveAll = true;
    }
}

html_head('Detail Logbook'); modal_styles(); nav($user,'Detail Logbook Tim'); flash();
?>
<?php if($review): ?>
<div class="card" style="max-width:520px;border-color:#fca5a5">
  <h2>Tolak Logbook #<?=$review?></h2>
  <form method="post" action="action.php">
    <input type="hidden" name="action" value="decline"><input type="hidden" name="id" value="<?=$review?>">
    <?= csrf_field() ?>
    <div class="field"><label>Alasan Penolakan</label>
      <textarea name="catatan" required placeholder="Tuliskan alasan penolakan..."></textarea></div>
    <div class="btn-group">
    <button class="btn btn-danger" type="submit">Konfirmasi Tolak</button>
    <a href="logbook.php<?= page_qs($baseQs) ?>" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<?php endif ?>

<div class="card">
  <h2>Logbook Tim</h2>
  <form method="get" class="filter-bar" style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
    <label style="font-size:.8rem;color:var(--muted);font-weight:600;margin:0">Tanggal:</label>
    <input type="date" name="tanggal" value="<?=htmlspecialchars($ftgl)?>" max="<?=$today?>">
    <select name="user_id" style="max-width:180px">
      <option value="">Semua Karyawan</option>
      <?php foreach($emps as $e): ?>
      <option value="<?=$e['id']?>" <?=$fuid==$e['id']?'selected':''?>><?=htmlspecialchars($e['name'])?></option>
      <?php endforeach ?>
    </select>
    <select name="status">
      <option value="">Semua Status</option>
      <?php foreach(['pending'=>'Menunggu','approved'=>'Disetujui','declined'=>'Ditolak'] as $v=>$l): ?>
      <option value="<?=$v?>" <?=$fsts===$v?'selected':''?>><?=$l?></option>
      <?php endforeach ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <?php if($ftgl!==$today || $fuid || $fsts): ?>
    <a href="logbook.php" class="btn btn-ghost btn-sm" style="margin-left:.25rem">Reset</a>
    <?php endif ?>
  </form>
  <?php if($showApproveAll): ?>
    <form method="post" action="action.php" id="formApproveAll" style="display:inline-block;margin-bottom:.5rem">
      <input type="hidden" name="action" value="approve_all">
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" value="<?=$fuid?>">
      <input type="hidden" name="tanggal" value="<?=$ftgl?>">
      <?php foreach($pendingLogbookIds as $lid): ?>
        <input type="hidden" name="logbook_ids[]" value="<?=$lid?>">
      <?php endforeach ?>
      <input type="hidden" name="redirect" value="logbook.php?<?=http_build_query(['tanggal'=>$ftgl,'user_id'=>$fuid])?>">
      <button class="btn btn-success btn-sm" type="submit" data-confirm
      data-type="warn"
      data-title="Setujui Semua?"
      data-msg="Semua logbook <strong>pending</strong> dari karyawan ini pada tanggal tersebut akan disetujui."
      data-confirm-label="Ya, Setujui Semua"
      data-confirm-class="btn-success"
      data-form="formApproveAll"
      id="btnApproveAll">
        ✓ Setujui Semua Logbook Ini
      </button>
    </form>
  <?php endif; ?>
  <p style="font-size:.8rem;color:var(--muted);margin-bottom:.75rem">
    Menampilkan: <strong><?=date('d/m/Y',strtotime($ftgl))?></strong>
    <?=$ftgl===$today?' (Hari Ini)':''?>
  </p>
  <?php if(!$rows): ?><p style="color:var(--muted);font-size:.9rem">Tidak ada data untuk tanggal ini.</p>
  <?php else: ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'entri'); ?>
  <div class="table-wrap">
  <table><thead><tr><th>Karyawan</th><th>Jumlah Jam</th><th>Aktivitas</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach($rows as $r): ?>
  <tr><td><?=htmlspecialchars($r['nm'])?></td>
      <td><?=htmlspecialchars($r['jam'])?></td><td><?=htmlspecialchars($r['aktivitas'])?></td>
      <td><?=badge($r['status'])?></td>
      <td style="font-size:.8rem;color:var(--muted)"><?=htmlspecialchars($r['catatan_pic']??'—')?></td>
      <td><?php if($r['status']==='pending'): ?>
        <div class="btn-group">
        <form method="post" action="action.php" style="display:inline">
          <input type="hidden" name="action" value="approve">
          <input type="hidden" name="id" value="<?=$r['id']?>">
          <?= csrf_field() ?>
          <input type="hidden" name="redirect" value="logbook.php?<?=http_build_query(['tanggal'=>$ftgl,'user_id'=>$fuid,'status'=>$fsts])?>">
          <button class="btn btn-success btn-sm">✓</button>
        </form>
        <a href="<?= $qs(['review' => $r['id']]) ?>" class="btn btn-danger btn-sm">✗</a>
        </div>
      <?php else: ?>—<?php endif ?></td></tr>
  <?php endforeach ?>
  </tbody></table>
  </div>
  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi logbook'); ?>
  <?php endif ?>
</div>
<?php
modal_html();
modal_script();
html_foot();
?>
