<?php
/** @var string $guide @var string $typeLabel */
$typeLabel = $typeLabel ?? '';
$hasContent = trim(strip_tags(str_replace('&nbsp;', ' ', $guide))) !== ''
    || preg_match('/<(img|audio|video|iframe|svg|figure|table)/i', $guide);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>How to Play</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Inter:wght@400;500;600;700&display=swap">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Inter',system-ui,'Segoe UI',Arial,sans-serif;color:#e6e6ef;line-height:1.7;
  padding:2.5rem 1rem 4rem;background:#171410;
  background-image:
    radial-gradient(circle at 18% 12%, rgba(255,255,255,.04), transparent 55%),
    repeating-linear-gradient(45deg, rgba(0,0,0,.16) 0 2px, transparent 2px 10px);
}
.htp-board{
  max-width:800px;margin:0 auto;background:#232019;
  background-image:
    radial-gradient(circle at 18% 12%, rgba(255,255,255,.04), transparent 55%),
    repeating-linear-gradient(45deg, rgba(0,0,0,.10) 0 2px, transparent 2px 10px);
  border:1px solid rgba(0,0,0,.55);border-radius:10px;overflow:hidden;
  box-shadow:0 30px 80px rgba(0,0,0,.6);
}
.htp-head{
  display:flex;align-items:center;gap:.65rem;
  background:#7f1d1d;color:#fde8e8;border-bottom:3px solid #450a0a;padding:1rem 1.35rem;
}
.htp-head-icon{font-size:1.35rem;line-height:1}
.htp-title{
  font-family:'Special Elite','Courier New',monospace;font-size:1.3rem;font-weight:700;
  letter-spacing:.08em;text-transform:uppercase;margin:0;
}
.htp-body{padding:1.7rem 1.5rem 2.2rem}
.htp-sub{color:rgba(255,255,255,.5);font-size:.9rem;font-style:italic;margin-bottom:1.5rem}
.htp-card{
  position:relative;background:#efe7d2;background-image:linear-gradient(180deg,#f4eddb 0,#e7dbbd 100%);
  color:#2b2417;border:1px solid #b8a273;border-radius:3px;padding:2.2rem 1.7rem 1.7rem;
  box-shadow:0 8px 20px rgba(0,0,0,.45);
}
/* tape pinning the file to the board */
.htp-card::before{
  content:'';position:absolute;top:-11px;left:50%;transform:translateX(-50%) rotate(-1.5deg);
  width:130px;height:22px;background:rgba(214,203,160,.6);
  border:1px dashed rgba(120,90,40,.4);box-shadow:0 1px 3px rgba(0,0,0,.25);
}
/* red evidence stamp */
.htp-stamp{
  position:absolute;top:12px;right:12px;transform:rotate(3deg);
  border:2px solid #b3261e;color:#b3261e;background:rgba(255,255,255,.4);
  font-family:'Special Elite','Courier New',monospace;font-size:.6rem;letter-spacing:.16em;
  padding:.2rem .55rem;border-radius:3px;
}
.htp-rich{line-height:1.8;color:#34291a}
.htp-rich h1,.htp-rich h2,.htp-rich h3,.htp-rich h4{font-family:'Special Elite','Courier New',monospace;margin:1.4rem 0 .6rem;letter-spacing:.01em;color:#241d10}
.htp-rich h1:first-child,.htp-rich h2:first-child,.htp-rich h3:first-child,.htp-rich h4:first-child{margin-top:0}
.htp-rich h2{font-size:1.25rem}
.htp-rich h3{font-size:1.05rem}
.htp-rich p{margin-bottom:.9rem}
.htp-rich ul,.htp-rich ol{padding-left:1.6rem;margin-bottom:1rem}
.htp-rich li{margin-bottom:.35rem}
.htp-rich a{color:#8a3b12}
.htp-rich strong{color:#1a140b}
.htp-rich blockquote{border-left:3px solid #b3261e;padding:.4rem 0 .4rem 1rem;color:#5a4a2e;font-style:italic;margin:1rem 0}
.htp-rich img,.htp-rich video{max-width:100%;border-radius:6px;border:1px solid #b8a273;box-shadow:0 2px 6px rgba(0,0,0,.25);margin:.6rem 0}
.htp-empty{color:#6b5836;font-style:italic;text-align:center;padding:1.5rem 0}
.htp-foot{text-align:center;margin-top:2rem;font-size:.78rem;color:rgba(255,255,255,.3)}
.htp-foot a{color:#c9a86a;text-decoration:none}
.htp-foot a:hover{text-decoration:underline}
a:focus-visible{outline:3px solid #ffd166;outline-offset:2px;border-radius:4px}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.001ms!important;transition-duration:.001ms!important}}
</style>
</head>
<body>
<main class="htp-board">
  <div class="htp-head">
    <span class="htp-head-icon">🗂️</span>
    <h1 class="htp-title">How to Play</h1>
  </div>
  <div class="htp-body">
    <p class="htp-sub">A quick guide to making your way through the mystery.</p>
    <div class="htp-card">
      <div class="htp-stamp">FIELD GUIDE</div>
      <?php if ($hasContent): ?>
      <div class="htp-rich"><?= $guide ?></div>
      <?php else: ?>
      <p class="htp-empty">The how-to-play guide hasn't been written yet.</p>
      <?php endif; ?>
    </div>
    <div class="htp-foot">Powered by <a href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener">Simply Creative Games</a></div>
  </div>
</main>
</body>
</html>
