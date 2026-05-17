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
    </nav>
    <div class="sidebar-footer">
      <span class="sidebar-user">👤 <?= e($_SESSION['admin_username'] ?? '') ?></span>
      <form method="POST" action="<?= url('admin/logout') ?>">
        <?= csrf_field() ?>
        <button class="btn-logout" type="submit">Sign out</button>
      </form>
    </div>
  </aside>

  <!-- Main content -->
  <main class="admin-main">
    <header class="admin-topbar">
      <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
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
</body>
</html>
