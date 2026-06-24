<?php
define('BASE', '..');
require '../shared/auth.php';
require '../shared/config.php';
require '../shared/layout.php';
require '../shared/pagination.php';
require '../shared/modal.php';

$user = require_role('admin');
$page = paginate_page();
$q = trim($_GET['q'] ?? '');
$editId = (int)($_GET['edit'] ?? 0);

$editData = null;
if ($editId > 0) {
    $s = $db->prepare("SELECT id, name, email FROM users WHERE id=? AND role='karyawan'");
    $s->execute([$editId]);
    $editData = $s->fetch();
}

$where = "u.role='karyawan'";
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
    $params[] = $like;
    $params[] = $like;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$countStmt->execute($params);
$pg = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page = $pg['page'];
$offset = $pg['offset'];

$listSql = "SELECT u.id, u.name, u.email, u.created_at, MAX(l.tanggal) AS last_log
    FROM users u
    LEFT JOIN logbooks l ON l.user_id = u.id
    WHERE $where
    GROUP BY u.id
    ORDER BY u.id DESC
    LIMIT {$pg['perPage']} OFFSET $offset";
$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$baseQs = array_filter(['q' => $q ?: null, 'edit' => $editId > 0 ? $editId : null]);
$qs = fn(array $extra = []) => page_qs($baseQs, array_merge($page > 1 ? ['page' => $page] : [], $extra));

html_head('Data Karyawan');
nav($user, 'Data Karyawan');
flash();
modal_styles();
?>
<div class="grid grid-2">
  <div class="card">
    <h2><?= $editData ? 'Edit Karyawan' : 'Tambah Karyawan' ?></h2>
    <form method="post" action="action.php">
      <input type="hidden" name="action" value="<?= $editData ? 'update_karyawan' : 'create_karyawan' ?>">
      <?php if ($editData): ?>
      <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">
      <?php endif ?>
      <div class="field">
        <label>Nama Lengkap</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($editData['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label><?= $editData ? 'Password Baru (opsional)' : 'Password' ?></label>
        <input type="password" name="password" <?= $editData ? '' : 'required' ?> minlength="6">
      </div>
      <div class="btn-group">
        <button class="btn btn-primary" type="submit"><?= $editData ? 'Simpan Perubahan' : 'Tambah Karyawan' ?></button>
        <?php if ($editData): ?>
        <a class="btn btn-ghost" href="karyawan.php<?= $qs(['edit' => null, 'page' => $page > 1 ? $page : null]) ?>">Batal</a>
        <?php endif ?>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>Catatan Status</h2>
    <p style="color:var(--muted);font-size:.9rem;line-height:1.7">
      Pengguna <strong>aktif</strong> berarti memiliki minimal satu logbook dalam 7 hari terakhir.
      Jika tidak ada logbook selama 7 hari, status dianggap <strong>non aktif</strong>.
    </p>
  </div>
</div>

<div class="card">
  <h2>Daftar Karyawan</h2>

  <form method="get" class="search-bar">
    <?php if ($editId > 0): ?>
    <input type="hidden" name="edit" value="<?= $editId ?>">
    <?php endif ?>
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama atau email...">
    <button class="btn btn-primary btn-sm" type="submit">Cari</button>
    <?php if ($q !== ''): ?>
    <a class="btn btn-ghost btn-sm" href="karyawan.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>">Reset</a>
    <?php endif ?>
  </form>

  <?php if ($pg['total']): ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'karyawan'); ?>
  <?php if ($q !== ''): ?>
  <p class="pagination-info" style="margin-top:-.5rem">Hasil pencarian: <strong><?= htmlspecialchars($q) ?></strong></p>
  <?php endif ?>
  <?php endif ?>

  <?php if (!$rows): ?>
    <p style="color:var(--muted);font-size:.9rem">
      <?= $q !== '' ? 'Tidak ada karyawan yang cocok dengan pencarian.' : 'Belum ada data karyawan.' ?>
    </p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>Status</th>
          <th>Logbook Terakhir</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php $isActive = !empty($r['last_log']) && strtotime($r['last_log']) >= strtotime('-7 days'); ?>
          <tr>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td>
              <span class="badge <?= $isActive ? 'badge-approved' : 'badge-declined' ?>">
                <?= $isActive ? 'Aktif' : 'Non Aktif' ?>
              </span>
            </td>
            <td><?= $r['last_log'] ? htmlspecialchars($r['last_log']) : 'Belum pernah input' ?></td>
            <td>
              <div class="btn-group">
                <a class="btn btn-primary btn-sm" href="<?= $qs(['edit' => $r['id'], 'page' => $page > 1 ? $page : null]) ?>">Edit</a>
                <button type="button" class="btn btn-danger btn-sm"
                  data-confirm-delete
                  data-id="<?= (int)$r['id'] ?>"
                  data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"
                  data-label="karyawan"
                  data-form="formDelKaryawan<?= (int)$r['id'] ?>">Hapus</button>
                <form id="formDelKaryawan<?= (int)$r['id'] ?>" method="post" action="action.php" hidden>
                  <input type="hidden" name="action" value="delete_karyawan">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi karyawan'); ?>
  <?php endif ?>
</div>
<?php
modal_html();
modal_script();
html_foot();
