<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="login-body">
<div class="login-container">
  <div class="login-card">
    <div class="login-brand">
      <span class="brand-icon-lg">🔮</span>
      <h1><?= APP_NAME ?></h1>
      <p>Admin Portal</p>
    </div>

    <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('admin/login') ?>" class="login-form">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required
               autocomplete="username" placeholder="Enter username">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required
               autocomplete="current-password" placeholder="Enter password">
      </div>
      <button type="submit" class="btn btn-primary btn-full">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>
