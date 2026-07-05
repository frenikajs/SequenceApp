<?php /** @var array|null $flash */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Simply Creative Games — Open Your Case</title>
<meta name="description" content="Enter your case access code to begin the mystery.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Inter:wght@400;500;600;700&display=swap">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;font-family:'Inter',system-ui,'Segoe UI',Arial,sans-serif;color:#e6e6ef;
  background:#171410;
  background-image:
    radial-gradient(circle at 20% 12%, rgba(255,255,255,.05), transparent 55%),
    repeating-linear-gradient(45deg, rgba(0,0,0,.16) 0 2px, transparent 2px 10px);
  display:flex;flex-direction:column;
}
/* top bar with admin sign-in */
.lp-topbar{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem}
.lp-brand{font-family:'Special Elite','Courier New',monospace;letter-spacing:.06em;color:#d8c7a0;font-size:.95rem}
/* centered case file */
.lp-wrap{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem 1rem 3rem;gap:1.5rem}
.lp-file{
  width:100%;max-width:520px;background:#232019;
  background-image:radial-gradient(circle at 18% 12%, rgba(255,255,255,.04), transparent 55%),repeating-linear-gradient(45deg, rgba(0,0,0,.10) 0 2px, transparent 2px 10px);
  border:1px solid rgba(0,0,0,.55);border-radius:10px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.6);
}
.lp-file-head{
  display:flex;align-items:center;justify-content:space-between;
  background:#7f1d1d;color:#fde8e8;border-bottom:3px solid #450a0a;padding:.85rem 1.25rem;
  font-family:'Special Elite','Courier New',monospace;letter-spacing:.18em;font-size:.9rem;
}
.lp-stamp{border:2px solid #fde8e8;border-radius:4px;padding:.1rem .5rem;font-size:.62rem;letter-spacing:.16em;transform:rotate(2deg)}
.lp-file-body{padding:2.2rem 1.8rem 2rem;text-align:center}
.lp-title{font-family:'Special Elite','Cinzel',serif;font-size:1.7rem;letter-spacing:.02em;margin-bottom:.5rem;color:#fff}
.lp-sub{color:rgba(255,255,255,.6);font-size:.95rem;margin-bottom:1.6rem}
.lp-error{background:rgba(179,38,30,.16);border:1px solid rgba(252,165,165,.5);color:#fca5a5;border-radius:8px;padding:.6rem .8rem;font-size:.9rem;margin-bottom:1.1rem}
.lp-form{display:flex;flex-direction:column;gap:.8rem;align-items:center}
.lp-code{
  width:100%;max-width:340px;text-align:center;
  font-family:'Courier New',monospace;font-size:1.25rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;
  padding:.85rem 1rem;border-radius:10px;border:1px solid rgba(255,255,255,.18);
  background:rgba(255,255,255,.06);color:#fff;
}
.lp-code::placeholder{color:rgba(255,255,255,.3);letter-spacing:.12em}
.lp-code:focus{outline:none;border-color:#ffd166;box-shadow:0 0 0 3px rgba(255,209,102,.18)}
.lp-btn{
  background:#c9b487;color:#2b2417;font-family:'Special Elite','Courier New',monospace;font-weight:700;letter-spacing:.06em;
  font-size:1rem;padding:.8rem 1.6rem;border:none;border-radius:10px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.4);
}
.lp-btn:hover{filter:brightness(1.07)}
.lp-foot{font-size:.8rem;color:rgba(255,255,255,.3)}
.lp-foot a{color:#c9a86a;text-decoration:none}
.lp-foot a:hover{text-decoration:underline}
a:focus-visible,button:focus-visible,input:focus-visible{outline:3px solid #ffd166;outline-offset:2px;border-radius:6px}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.001ms!important;transition-duration:.001ms!important}}
</style>
</head>
<body>
<header class="lp-topbar">
  <span class="lp-brand">🗂️ Simply Creative Games</span>
</header>
<main class="lp-wrap">
  <div class="lp-file">
    <div class="lp-file-head"><span>CONFIDENTIAL</span><span class="lp-stamp">CASE FILE</span></div>
    <div class="lp-file-body">
      <h1 class="lp-title">Open Your Case</h1>
      <p class="lp-sub">Enter your start code &mdash; or a group code from your host &mdash; to begin the investigation.</p>
      <?php if ($flash && ($flash['type'] ?? '') === 'error'): ?>
      <div class="lp-error" role="alert">⚠ <?= e($flash['message']) ?></div>
      <?php endif; ?>
      <form method="POST" action="<?= url('enter') ?>" class="lp-form">
        <?= csrf_field() ?>
        <input type="text" name="code" class="lp-code" placeholder="ENTER CODE"
               autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false" autofocus required>
        <button type="submit" class="lp-btn">🔓 Open Case</button>
      </form>
    </div>
  </div>
  <div class="lp-foot">Powered by <a href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener">Simply Creative Games</a></div>
</main>
</body>
</html>
