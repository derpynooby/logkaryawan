<?php
define('BASE','..');
require '../shared/auth.php'; require '../shared/config.php'; require '../shared/layout.php';
require '../shared/pagination.php';
require '../shared/modal.php';
$user = require_role('karyawan'); $uid = $user['id'];
$page = paginate_page();

$today = today_hours($db,$uid);
$countStmt = $db->prepare('SELECT COUNT(*) FROM logbooks WHERE user_id=? AND tanggal=CURDATE()');
$countStmt->execute([$uid]);
$pg = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page = $pg['page'];

$s=$db->prepare('SELECT l.*,u.name pic_name FROM logbooks l JOIN users u ON u.id=l.pic_id WHERE l.user_id=? AND l.tanggal=CURDATE() ORDER BY l.id DESC LIMIT '.$pg['perPage'].' OFFSET '.$pg['offset']);
$s->execute([$uid]); $entries=$s->fetchAll();
$pics=$db->query("SELECT id,name FROM users WHERE role='pic' ORDER BY name")->fetchAll();

$qs = fn(array $extra = []) => page_qs(array_merge($page > 1 ? ['page' => $page] : []), $extra);

// Hitung total approve dan total pending (jumlah entri & akumulasi jam)
$stmt_approved = $db->prepare("SELECT COUNT(*), COALESCE(SUM(jam),0) FROM logbooks WHERE user_id=? AND tanggal=CURDATE() AND status='approved'");
$stmt_approved->execute([$uid]);
list($total_approve, $jam_approve) = $stmt_approved->fetch(PDO::FETCH_NUM);

$stmt_pending = $db->prepare("SELECT COUNT(*), COALESCE(SUM(jam),0) FROM logbooks WHERE user_id=? AND tanggal=CURDATE() AND status='pending'");
$stmt_pending->execute([$uid]);
list($total_pending, $jam_pending) = $stmt_pending->fetch(PDO::FETCH_NUM);

html_head('Input Logbook'); nav($user,'Input Logbook'); flash();
?>
<?php if($today<8): ?>
<div class="alert alert-warn">⚠️ Akumulasi jam hari ini: <strong><?=number_format($today,1)?> jam</strong>. Minimal 8 jam.</div>
<?php endif ?>
<div class="card">
  <h2>Tambah Aktivitas — <?=date('d M Y')?></h2>
  <form method="post" action="action.php">
    <input type="hidden" name="action" value="add">
    <?= csrf_field() ?>
    <div class="grid grid-2">
      <div class="field"><label>Tanggal</label><input type="text" value="<?=date('d M Y')?>" readonly></div>
      <div class="field"><label>Jumlah Jam</label><input type="number" name="jam" min="0.25" max="24" step="0.25" required placeholder="cth: 2.5"></div>
    </div>
    <div class="field"><label>PIC / Supervisor</label>
      <select name="pic_id" required><option value="">— Pilih PIC —</option>
      <?php foreach($pics as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option><?php endforeach ?>
      </select></div>
    <div class="field"><label>Deskripsi Aktivitas</label>
      <textarea name="aktivitas" required placeholder="Jelaskan aktivitas yang dikerjakan..."></textarea></div>
    <button class="btn btn-primary" type="submit">Simpan Aktivitas</button>
  </form>
</div>
<div class="card">
  <h2>Entri Hari Ini</h2>
  <?php if(!$entries): ?><p style="color:var(--muted);font-size:.9rem">Belum ada entri hari ini.</p>
  <?php else: ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'entri'); ?>
  <div class="table-wrap">
  <table><thead><tr><th>Jam</th><th>Aktivitas</th><th>PIC</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach($entries as $e): ?>
  <tr><td><?=htmlspecialchars($e['jam'])?> jam</td>
      <td><?=htmlspecialchars($e['aktivitas'])?></td>
      <td><?=htmlspecialchars($e['pic_name'])?></td>
      <td><?=badge($e['status'])?></td>
      <td>
        <?php if($e['status']==='pending'): ?>
          <div class="btn-group">
            <a href="edit.php?id=<?=$e['id']?>" class="btn btn-primary btn-sm">Edit</a>
            <button type="button" class="btn btn-danger btn-sm"
              data-confirm-delete
              data-id="<?=$e['id']?>"
              data-name="entri jam <?=htmlspecialchars($e['jam'])?> — <?=htmlspecialchars(mb_substr($e['aktivitas'],0,30))?>..."
              data-label="entri"
              data-form="formDelEntry<?=$e['id']?>">Hapus</button>
            <form id="formDelEntry<?=$e['id']?>" method="post" action="action.php" hidden>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?=$e['id']?>">
              <?= csrf_field() ?>
            </form>
          </div>
        <?php else: ?>
          <span style="color:var(--muted);font-size:.8rem">Terkunci</span>
        <?php endif ?>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody></table>
  </div>
  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi entri'); ?>
  <div style="margin-top:.75rem;font-weight:700;">
    <span style="color:#222">Total: <?=number_format($today,1)?> jam</span>
    &nbsp;|&nbsp;
    <span style="color:green">Approve: <?= (int)$total_approve ?> entri, <?= number_format($jam_approve, 1) ?> jam</span>
    &nbsp;|&nbsp;
    <span style="color:orange">Menunggu: <?= (int)$total_pending ?> entri, <?= number_format($jam_pending, 1) ?> jam</span>
  </div>
  <?php endif ?>
</div>
<?php
modal_html();
modal_script();
html_foot();
?>
