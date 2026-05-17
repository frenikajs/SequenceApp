<?php
$pageTitle = 'Sequences';
$activeNav = 'sequences';
ob_start();
?>

<div class="toolbar">
  <form method="GET" class="search-form">
    <input type="text" name="search" placeholder="Search sequences…" value="<?= e($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if ($search): ?><a href="<?= url('admin/sequences') ?>" class="btn btn-ghost">Clear</a><?php endif; ?>
  </form>
  <a href="<?= url('admin/sequences/create') ?>" class="btn btn-primary">+ New Sequence</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Title / Slug</th>
          <th>Type</th>
          <th>Status</th>
          <th>Views</th>
          <th>Clues</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sequences)): ?>
        <tr>
          <td colspan="7" class="text-center muted py-8">
            <?= $search ? 'No sequences match your search.' : 'No sequences yet.' ?>
            <a href="<?= url('admin/sequences/create') ?>">Create your first sequence</a>.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($sequences as $seq): ?>
        <tr>
          <td>
            <strong><?= e($seq['title']) ?></strong>
            <div class="muted small">/s/<?= e($seq['slug']) ?></div>
          </td>
          <td>
            <span class="badge badge-<?= $seq['type'] === 'sequential' ? 'blue' : 'purple' ?>">
              <?= e(ucfirst($seq['type'])) ?>
            </span>
          </td>
          <td>
            <form method="POST" action="<?= url('admin/sequences/' . $seq['id'] . '/publish') ?>">
              <?= csrf_field() ?>
              <button type="submit" class="badge badge-<?= $seq['published'] ? 'green' : 'gray' ?> btn-badge">
                <?= $seq['published'] ? 'Published' : 'Draft' ?>
              </button>
            </form>
          </td>
          <td><?= number_format((int)$seq['view_count']) ?></td>
          <td>
            <a href="<?= url('admin/sequences/' . $seq['id'] . '/clues') ?>">
              Manage clues
            </a>
          </td>
          <td class="small muted"><?= formatDate($seq['created_at'], 'M j, Y') ?></td>
          <td class="actions">
            <a href="<?= url('admin/sequences/' . $seq['id'] . '/edit') ?>" class="btn btn-secondary btn-xs">Edit</a>
            <a href="<?= url('s/' . $seq['slug']) ?>" target="_blank" class="btn btn-ghost btn-xs">View ↗</a>
            <form method="POST" action="<?= url('admin/sequences/' . $seq['id'] . '/duplicate') ?>" class="inline">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-ghost btn-xs" title="Duplicate">⊕ Copy</button>
            </form>
            <button class="btn btn-danger btn-xs"
              onclick="confirmDelete('<?= url('admin/sequences/' . $seq['id'] . '/delete') ?>','Delete sequence &quot;<?= e(addslashes($seq['title'])) ?>&quot;? This cannot be undone.')">
              Delete
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php
  $totalPages = (int)ceil($total / $per_page);
  if ($totalPages > 1):
  ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
       class="page-btn <?= $p === $page ? 'active' : '' ?>">
      <?= $p ?>
    </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
