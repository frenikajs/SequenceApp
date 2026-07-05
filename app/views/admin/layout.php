<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Admin') ?> — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="brand-icon">🔮</span>
      <span class="brand-name">Sequence</span>
      <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Close menu">✕</button>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= url('admin') ?>" class="nav-item <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span> Dashboard
      </a>
      <a href="<?= url('admin/sequences') ?>" class="nav-item <?= ($activeNav ?? '') === 'sequences' ? 'active' : '' ?>">
        <span class="nav-icon">📜</span> Sequences
      </a>
      <a href="<?= url('admin/sequences/create') ?>" class="nav-item <?= ($activeNav ?? '') === 'create' ? 'active' : '' ?>">
        <span class="nav-icon">➕</span> New Sequence
      </a>
      <a href="<?= url('admin/guide') ?>" class="nav-item <?= ($activeNav ?? '') === 'guide' ? 'active' : '' ?>">
        <span class="nav-icon">❓</span> How to Play Guide
      </a>
      <a href="<?= url('admin/diagnostic') ?>" class="nav-item <?= ($activeNav ?? '') === 'diagnostic' ? 'active' : '' ?>">
        <span class="nav-icon">🩺</span> Diagnostic
      </a>
    </nav>
    <div class="sidebar-footer">
      <span class="sidebar-user">👤 <?= e($_SESSION['admin_username'] ?? '') ?></span>
      <form method="POST" action="<?= url('admin/logout') ?>">
        <?= csrf_field() ?>
        <button class="btn-logout" type="submit">Sign out</button>
      </form>
    </div>
  </aside>

  <!-- Tap-to-close backdrop (mobile, when the sidebar is open) -->
  <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebar()"></div>

  <!-- Main content -->
  <main class="admin-main">
    <header class="admin-topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
      <h1 class="page-title"><?= e($pageTitle ?? 'Admin') ?></h1>
    </header>

    <?php if (!empty($flash)): ?>
    <div class="toast toast-<?= e($flash['type']) ?>" id="toast">
      <?= e($flash['message']) ?>
      <button onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>

    <div class="admin-content">
      <?= $content ?? '' ?>
    </div>
  </main>
</div>

<div id="modal-overlay" class="modal-overlay hidden">
  <div class="modal" id="modal-box">
    <div class="modal-header">
      <h3 id="modal-title"></h3>
      <button onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modal-body"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="btn btn-danger" id="modal-confirm">Confirm</button>
    </div>
  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
// Mobile sidebar: open/close with a tap-to-close backdrop. Inline so it always
// works even if admin.js is cached.
(function () {
  var sidebar  = document.getElementById('sidebar');
  var backdrop = document.getElementById('sidebar-backdrop');
  if (!sidebar) { return; }
  window.toggleSidebar = function () {
    var open = sidebar.classList.toggle('open');
    if (backdrop) { backdrop.classList.toggle('visible', open); }
    document.body.classList.toggle('sidebar-open', open);
  };
  window.closeSidebar = function () {
    sidebar.classList.remove('open');
    if (backdrop) { backdrop.classList.remove('visible'); }
    document.body.classList.remove('sidebar-open');
  };
  // Close when a nav link is tapped (so navigating dismisses the menu).
  sidebar.querySelectorAll('.sidebar-nav a').forEach(function (a) {
    a.addEventListener('click', function () { window.closeSidebar(); });
  });
  // Close on Escape.
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { window.closeSidebar(); }
  });
})();
</script>
</body>
</html>
