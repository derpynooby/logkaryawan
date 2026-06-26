<?php
define('BASE', '..');
require '../shared/auth.php';
require '../shared/config.php';
require '../shared/layout.php';
require '../shared/pagination.php';
require '../shared/modal.php';

$user   = require_role('admin');
$page   = paginate_page();
$q      = trim($_GET['q'] ?? '');
$editId = (int)($_GET['edit'] ?? 0);

// FIX: Admin bisa kelola semua role
$filterRole = $_GET['role'] ?? '';
$validRoles = ['karyawan', 'pic', 'direktur', 'admin'];
if (!in_array($filterRole, $validRoles, true)) $filterRole = '';

$editData = null;
if ($editId > 0) {
    $s = $db->prepare("SELECT id, name, email, role FROM users WHERE id=?");
    $s->execute([$editId]);
    $editData = $s->fetch();
}

$conditions = ['1=1'];
$params     = [];

if ($filterRole !== '') {
    $conditions[] = 'u.role = ?';
    $params[]     = $filterRole;
}
if ($q !== '') {
    $like           = '%' . $q . '%';
    $conditions[]   = '(u.name LIKE ? OR u.email LIKE ?)';
    $params[]       = $like;
    $params[]       = $like;
}
$where = implode(' AND ', $conditions);

$countStmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$countStmt->execute($params);
$pg     = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page   = $pg['page'];
$offset = $pg['offset'];

$listSql = "SELECT u.id, u.name, u.email, u.role, u.created_at, MAX(l.tanggal) AS last_log
    FROM users u
    LEFT JOIN logbooks l ON l.user_id = u.id
    WHERE $where
    GROUP BY u.id
    ORDER BY FIELD(u.role,'admin','direktur','pic','karyawan'), u.id DESC
    LIMIT {$pg['perPage']} OFFSET $offset";
$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$baseQs = array_filter(['q' => $q ?: null, 'role' => $filterRole ?: null, 'edit' => $editId > 0 ? $editId : null]);
$qs     = fn(array $extra = []) => page_qs($baseQs, array_merge($page > 1 ? ['page' => $page] : [], $extra));

$roleLabel = ['karyawan' => 'Karyawan', 'pic' => 'PIC', 'direktur' => 'Direktur', 'admin' => 'Admin'];

html_head('Manajemen User');
nav($user, 'Manajemen User');
flash();
modal_styles();
?>
<div class="grid grid-2">
  <div class="card">
    <h2><?= $editData ? 'Edit User' : 'Tambah User' ?></h2>
    <form method="post" action="action.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editData ? 'update_user' : 'create_user' ?>">
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
        <label>Role</label>
        <select name="role" required <?= ($editData && (int)$editData['id'] === (int)$user['id']) ? 'disabled' : '' ?>>
          <?php foreach ($roleLabel as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($editData['role'] ?? $filterRole ?: 'karyawan') === $val ? 'selected' : '' ?>>
              <?= $lbl ?>
            </option>
          <?php endforeach ?>
        </select>
        <?php if ($editData && (int)$editData['id'] === (int)$user['id']): ?>
          <input type="hidden" name="role" value="admin">
          <small style="color:var(--muted)">Role akun sendiri tidak bisa diubah.</small>
        <?php endif ?>
      </div>
      <div class="field">
        <label><?= $editData ? 'Password Baru (opsional)' : 'Password' ?></label>
        <input type="password" name="password" <?= $editData ? '' : 'required' ?> minlength="8" placeholder="Minimal 8 karakter">
      </div>
      <div class="btn-group">
        <button class="btn btn-primary" type="submit"><?= $editData ? 'Simpan Perubahan' : 'Tambah User' ?></button>
        <?php if ($editData): ?>
        <a class="btn btn-ghost" href="karyawan.php<?= $qs(['edit' => null, 'page' => $page > 1 ? $page : null]) ?>">Batal</a>
        <?php endif ?>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>Catatan Status</h2>
    <p style="color:var(--muted);font-size:.9rem;line-height:1.7">
      Status <strong>Aktif</strong> ditentukan dari adanya logbook dalam 7 hari terakhir (khusus karyawan).
      Admin, Direktur, dan PIC tidak memiliki indikator aktif berdasarkan logbook.
    </p>
  </div>
</div>

<div class="card">
  <h2>Daftar User</h2>

  <form method="get" class="search-bar" style="flex-wrap:wrap;gap:.5rem">
    <?php if ($editId > 0): ?>
    <input type="hidden" name="edit" value="<?= $editId ?>">
    <?php endif ?>
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama atau email...">
    <select name="role" style="padding:.45rem .7rem;border:1.5px solid var(--border);border-radius:.45rem;font-size:.875rem">
      <option value="">Semua Role</option>
      <?php foreach ($roleLabel as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $filterRole === $val ? 'selected' : '' ?>><?= $lbl ?></option>
      <?php endforeach ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Cari</button>
    <?php if ($q !== '' || $filterRole !== ''): ?>
    <a class="btn btn-ghost btn-sm" href="karyawan.php">Reset</a>
    <?php endif ?>
  </form>

  <?php if ($pg['total']): ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'user'); ?>
  <?php endif ?>

  <?php if (!$rows): ?>
    <p style="color:var(--muted);font-size:.9rem">
      <?= ($q !== '' || $filterRole !== '') ? 'Tidak ada user yang cocok dengan filter.' : 'Belum ada data user.' ?>
    </p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status / Log Terakhir</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php
            $isKaryawan = $r['role'] === 'karyawan';
            $isActive   = !empty($r['last_log']) && strtotime($r['last_log']) >= strtotime('-7 days');
            $isSelf     = (int)$r['id'] === (int)$user['id'];
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($r['name']) ?>
              <?php if ($isSelf): ?><span style="font-size:.7rem;color:var(--muted)"> (Anda)</span><?php endif ?>
            </td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><span class="badge badge-pending"><?= $roleLabel[$r['role']] ?? $r['role'] ?></span></td>
            <td>
              <?php if ($isKaryawan): ?>
                <span class="badge <?= $isActive ? 'badge-approved' : 'badge-declined' ?>">
                  <?= $isActive ? 'Aktif' : 'Non Aktif' ?>
                </span>
              <?php else: ?>
                <?= $r['last_log'] ? htmlspecialchars($r['last_log']) : '<span style="color:var(--muted)">—</span>' ?>
              <?php endif ?>
            </td>
            <td>
              <div class="btn-group">
                <a class="btn btn-primary btn-sm" href="<?= $qs(['edit' => $r['id'], 'page' => $page > 1 ? $page : null]) ?>">Edit</a>
                <?php if (!$isSelf): ?>
                <button type="button" class="btn btn-danger btn-sm"
                  data-confirm-delete
                  data-id="<?= (int)$r['id'] ?>"
                  data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"
                  data-label="user"
                  data-form="formDelUser<?= (int)$r['id'] ?>">Hapus</button>
                <form id="formDelUser<?= (int)$r['id'] ?>" method="post" action="action.php" hidden>
                  <input type="hidden" name="action" value="delete_user">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                </form>
                <?php endif ?>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi user'); ?>
  <?php endif ?>
</div>
<?php
modal_html();
modal_script();
html_foot();
