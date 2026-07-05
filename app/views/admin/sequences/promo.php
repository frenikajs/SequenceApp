<?php
/** @var array $seq  @var array $theme  @var string $startUrl */
$accent  = $theme['accent_color'] ?? '#b8860b';
$button  = $theme['button_color'] ?? '#6c63ff';
$desc    = (string)($seq['description'] ?? '');
$hasDesc = richHasContent($desc);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Promo — <?= e($seq['title']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;500;600&display=swap">
<style>
:root{--accent:<?= e($accent) ?>;--btn:<?= e($button) ?>}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:#e9e6df;color:#1d1a16;padding:1.5rem;display:flex;flex-direction:column;align-items:center}
.promo-toolbar{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.25rem}
.promo-toolbar a,.promo-toolbar button{font:inherit;font-size:.9rem;font-weight:600;padding:.55rem 1rem;border-radius:8px;border:1px solid #c3bdb0;background:#fff;color:#1d1a16;text-decoration:none;cursor:pointer}
.promo-toolbar button{background:var(--btn);color:#fff;border-color:var(--btn)}
.promo{
  width:100%;max-width:640px;background:#fffdf7;
  border:2px solid var(--accent);border-radius:18px;
  padding:3rem 2.5rem;text-align:center;position:relative;
  box-shadow:0 18px 50px rgba(0,0,0,.25);
}
.promo::before{content:"";position:absolute;inset:10px;border:1px solid rgba(0,0,0,.14);border-radius:12px;pointer-events:none}
.promo-eyebrow{position:relative;font-size:.78rem;letter-spacing:.34em;text-transform:uppercase;color:var(--accent);font-weight:700;margin-bottom:1rem}
.promo-title{position:relative;font-family:'Cinzel',Georgia,serif;font-size:clamp(1.9rem,5vw,2.6rem);font-weight:700;line-height:1.15}
.promo-rule{position:relative;width:84px;height:3px;background:var(--accent);margin:1.1rem auto 1.5rem;border-radius:2px}
.promo-desc{position:relative;font-size:1.04rem;line-height:1.75;color:#3a352d;max-width:480px;margin:0 auto 2rem}
.promo-desc p{margin:0 0 .6rem}.promo-desc p:last-child{margin-bottom:0}
.promo-desc strong{color:#1d1a16}.promo-desc em{font-style:italic}
.promo-desc ul,.promo-desc ol{text-align:left;display:inline-block;margin:.3rem 0;padding-left:1.3rem}
.promo-code{position:relative;margin:0 auto 1.5rem}
.promo-code-label{display:block;font-size:.74rem;letter-spacing:.32em;text-transform:uppercase;color:#8a8378;font-weight:600;margin-bottom:.5rem}
.promo-code-val{display:inline-block;font-family:'Courier New',monospace;font-weight:700;font-size:clamp(1.8rem,5vw,2.6rem);letter-spacing:.14em;color:#1d1a16;background:#fff;border:2px dashed var(--accent);border-radius:12px;padding:.5rem 1.3rem;text-transform:uppercase}
.promo-qr{position:relative;width:248px;height:248px;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;background:#fff;padding:14px;border-radius:14px;border:1px solid #e0dccf}
.promo-qr img,.promo-qr canvas{width:220px!important;height:220px!important}
.promo-cta{position:relative;font-size:1.15rem;font-weight:700;color:var(--btn);margin-bottom:.35rem}
.promo-url{position:relative;font-size:.74rem;color:#8a8378;font-family:'Courier New',monospace;word-break:break-all}
.promo-foot{position:relative;margin-top:2rem;font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:#a39a8a}
.promo-foot a{color:var(--accent);text-decoration:none}
@media print{
  @page{ size: letter portrait; margin: 0.4in; }
  html,body{background:#fff;padding:0;margin:0}
  .no-print{display:none!important}
  /* Border hugs the content (stays on one page); extra top/bottom padding
     keeps the border off the text. */
  .promo{
    box-sizing:border-box;
    box-shadow:none;border:2px solid var(--accent);border-radius:0;
    max-width:100%;width:100%;min-height:0;
    margin:0;padding:3rem 1.75rem;
    break-inside:avoid;page-break-inside:avoid;
  }
  .promo::before{inset:11px;border:1px solid var(--accent)}
  .promo-eyebrow{margin-bottom:.6rem}
  .promo-title{font-size:2rem}
  .promo-rule{margin:.65rem auto .9rem}
  .promo-desc{font-size:.95rem;line-height:1.5;margin-bottom:1rem}
  .promo-code{margin-bottom:.9rem}
  .promo-code-val{font-size:1.9rem;padding:.4rem 1.1rem}
  .promo-qr{width:208px;height:208px;margin-bottom:.5rem;padding:10px}
  .promo-qr img,.promo-qr canvas{width:188px!important;height:188px!important}
  .promo-cta{font-size:1.05rem}
  .promo-url{font-size:.7rem}
  .promo-foot{margin-top:1rem}
}
</style>
</head>
<body>
<div class="promo-toolbar no-print">
  <a href="<?= url('admin/sequences/' . (int)$seq['id'] . '/edit') ?>">&larr; Back</a>
  <button type="button" onclick="window.print()">🖨 Print / Save PDF</button>
</div>

<div class="promo">
  <div class="promo-eyebrow">🔍 A Mystery Awaits</div>
  <h1 class="promo-title"><?= e($seq['title']) ?></h1>
  <div class="promo-rule"></div>
  <?php if ($hasDesc): ?>
  <div class="promo-desc"><?= $desc ?></div>
  <?php else: ?>
  <p class="promo-desc">Can you crack the case? Scan the code below to begin your investigation.</p>
  <?php endif; ?>
  <?php if (trim((string)($seq['start_code'] ?? '')) !== ''): ?>
  <div class="promo-code">
    <span class="promo-code-label">Start Code</span>
    <span class="promo-code-val"><?= e($seq['start_code']) ?></span>
  </div>
  <?php endif; ?>
  <div class="promo-qr" id="promo-qr" data-url="<?= e($startUrl) ?>"></div>
  <div class="promo-cta">Scan to Start</div>
  <div class="promo-url"><?= e($startUrl) ?></div>
  <div class="promo-foot">Powered by <a href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener">Simply Creative Games</a></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
  var el = document.getElementById('promo-qr');
  new QRCode(el, { text: el.getAttribute('data-url'), width: 512, height: 512, correctLevel: QRCode.CorrectLevel.M });
})();
</script>
</body>
</html>
