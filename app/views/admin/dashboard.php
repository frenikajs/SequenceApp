<?php
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
ob_start();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📜</div>
    <div class="stat-body">
      <div class="stat-value"><?= (int)$stats['total'] ?></div>
      <div class="stat-label">Total Sequences</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div class="stat-body">
      <div class="stat-value"><?= (int)$stats['published'] ?></div>
      <div class="stat-label">Published</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👁</div>
    <div class="stat-body">
      <div class="stat-value"><?= number_format((int)$stats['views']) ?></div>
      <div class="stat-label">Total Views</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🏆</div>
    <div class="stat-body">
      <div class="stat-value"><?= number_format((int)$stats['completions']) ?></div>
      <div class="stat-label">Completions</div>
    </div>
  </div>
</div>

<div class="card mt-6">
  <div class="card-header">
    <h2>Recent Sequences</h2>
    <a href="<?= url('admin/sequences/create') ?>" class="btn btn-primary btn-sm">+ New Sequence</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Type</th>
          <th>Status</th>
          <th>Views</th>
          <th>Completions</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recent)): ?>
        <tr><td colspan="6" class="text-center muted">No sequences yet. <a href="<?= url('admin/sequences/create') ?>">Create one</a>.</td></tr>
        <?php else: ?>
        <?php foreach ($recent as $seq): ?>
        <tr>
          <td data-label="Title">
            <strong><?= e($seq['title']) ?></strong>
            <div class="muted small">/s/<?= e($seq['slug']) ?></div>
          </td>
          <td data-label="Type"><span class="badge badge-<?= $seq['type'] === 'sequential' ? 'blue' : 'purple' ?>"><?= e($seq['type']) ?></span></td>
          <td data-label="Status">
            <?php if ($seq['published']): ?>
              <span class="badge badge-green">Published</span>
            <?php else: ?>
              <span class="badge badge-gray">Draft</span>
            <?php endif; ?>
          </td>
          <td data-label="Views"><?= number_format((int)$seq['view_count']) ?></td>
          <td data-label="Completions"><?= number_format((int)$seq['completion_count']) ?></td>
          <td class="col-actions">
            <div class="actions">
              <a href="<?= url('admin/sequences/' . $seq['id'] . '/edit') ?>" class="btn btn-secondary btn-xs">Edit</a>
              <a href="<?= url('s/' . $seq['slug']) ?>" target="_blank" class="btn btn-ghost btn-xs">View</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($recent) >= 5): ?>
  <div class="card-footer">
    <a href="<?= url('admin/sequences') ?>">View all sequences →</a>
  </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
