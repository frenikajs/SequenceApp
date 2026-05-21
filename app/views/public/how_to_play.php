<?php
/** @var string $guide */
$hasContent = trim(strip_tags(str_replace('&nbsp;', ' ', $guide))) !== ''
    || preg_match('/<(img|audio|video|iframe|svg|figure|table)/i', $guide);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>How to Play</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:#0f0f1a;color:#e6e6ef;line-height:1.7;padding:2.5rem 1rem 4rem}
.htp-wrap{max-width:780px;margin:0 auto}
.htp-title{font-size:2rem;font-weight:700;letter-spacing:-.02em;margin-bottom:.4rem}
.htp-sub{color:rgba(255,255,255,.55);font-size:.95rem;margin-bottom:2rem}
.htp-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:2rem}
.htp-rich{line-height:1.8}
.htp-rich h1,.htp-rich h2,.htp-rich h3,.htp-rich h4{margin:1.4rem 0 .6rem;letter-spacing:-.01em}
.htp-rich h1:first-child,.htp-rich h2:first-child,.htp-rich h3:first-child{margin-top:0}
.htp-rich h2{font-size:1.35rem;color:#fff}
.htp-rich h3{font-size:1.1rem;color:#cfcde6}
.htp-rich p{margin-bottom:.9rem}
.htp-rich ul,.htp-rich ol{padding-left:1.6rem;margin-bottom:1rem}
.htp-rich li{margin-bottom:.35rem}
.htp-rich a{color:#b3acff}
.htp-rich strong{color:#fff}
.htp-rich blockquote{border-left:3px solid #6c63ff;padding:.4rem 0 .4rem 1rem;color:rgba(255,255,255,.7);margin:1rem 0}
.htp-rich img,.htp-rich video{max-width:100%;border-radius:10px;margin:.6rem 0}
.htp-empty{color:rgba(255,255,255,.45);font-style:italic;text-align:center;padding:1.5rem 0}
.htp-foot{text-align:center;margin-top:3rem;font-size:.78rem;color:rgba(255,255,255,.22)}
.htp-foot a{color:#b3acff;text-decoration:none}
</style>
</head>
<body>
<div class="htp-wrap">
  <h1 class="htp-title">How to Play</h1>
  <p class="htp-sub">A quick guide to making your way through a sequence.</p>
  <div class="htp-card">
    <?php if ($hasContent): ?>
    <div class="htp-rich"><?= $guide ?></div>
    <?php else: ?>
    <p class="htp-empty">The how-to-play guide hasn't been written yet.</p>
    <?php endif; ?>
  </div>
  <div class="htp-foot">Powered by <a href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener">Simply Creative Games</a></div>
</div>
</body>
</html>
