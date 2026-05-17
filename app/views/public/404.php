<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Not Found</title>
<link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f0f1a;color:#e0e0e0;font-family:Inter,sans-serif;text-align:center;">
<div>
  <div style="font-size:4rem;margin-bottom:1rem;">🔍</div>
  <h1 style="font-size:2rem;margin:0 0 0.5rem">404</h1>
  <p style="color:#888;"><?= e($message ?? 'Page not found') ?></p>
  <a href="<?= url() ?>" style="color:#6c63ff;margin-top:1rem;display:inline-block;">← Go Home</a>
</div>
</body>
</html>
