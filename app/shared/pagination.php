<?php
const PER_PAGE = 10;

function paginate_page(): int {
    return max(1, (int)($_GET['page'] ?? 1));
}

function paginate_meta(int $total, ?int $page = null, int $perPage = PER_PAGE): array {
    $page = $page ?? paginate_page();
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;
    return [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'from' => $total ? $offset + 1 : 0,
        'to' => min($offset + $perPage, $total),
    ];
}

function page_qs(array $base, array $extra = []): string {
    $p = array_merge($base, $extra);
    $p = array_filter($p, fn($v) => $v !== null && $v !== '');
    return $p ? '?' . http_build_query($p) : '';
}

function render_pagination_info(int $from, int $to, int $total, string $label = 'data'): void {
    if (!$total) return;
    echo '<p class="pagination-info">Menampilkan ' . $from . '–' . $to . ' dari ' . $total . ' ' . htmlspecialchars($label) . '</p>';
}

function render_pagination(int $page, int $totalPages, callable $qs, string $ariaLabel = 'Paginasi'): void {
    if ($totalPages <= 1) return;

    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    ?>
<nav class="pagination" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
  <?php if ($page > 1): ?>
  <a href="<?= $qs(['page' => $page - 1]) ?>">« Sebelumnya</a>
  <?php else: ?>
  <span class="disabled">« Sebelumnya</span>
  <?php endif ?>

  <?php if ($start > 1): ?>
  <a href="<?= $qs(['page' => 1]) ?>">1</a>
  <?php if ($start > 2): ?><span class="disabled">…</span><?php endif ?>
  <?php endif ?>

  <?php for ($i = $start; $i <= $end; $i++): ?>
  <?php if ($i === $page): ?>
  <span class="active"><?= $i ?></span>
  <?php else: ?>
  <a href="<?= $qs(['page' => $i]) ?>"><?= $i ?></a>
  <?php endif ?>
  <?php endfor ?>

  <?php if ($end < $totalPages): ?>
  <?php if ($end < $totalPages - 1): ?><span class="disabled">…</span><?php endif ?>
  <a href="<?= $qs(['page' => $totalPages]) ?>"><?= $totalPages ?></a>
  <?php endif ?>

  <?php if ($page < $totalPages): ?>
  <a href="<?= $qs(['page' => $page + 1]) ?>">Berikutnya »</a>
  <?php else: ?>
  <span class="disabled">Berikutnya »</span>
  <?php endif ?>
</nav>
<?php }
