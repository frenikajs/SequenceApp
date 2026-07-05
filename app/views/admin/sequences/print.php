<?php /** @var array $seq  @var array $targets */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Kit — <?= e($seq['title']) ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:#f4f4f7;color:#1a1a2e;padding:1.5rem}
.kit-toolbar{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;max-width:1000px;margin:0 auto 1.5rem}
.kit-toolbar h1{font-size:1.25rem;font-weight:700;flex:1;min-width:200px}
.kit-toolbar a,.kit-toolbar button{font:inherit;font-size:.9rem;font-weight:600;padding:.55rem 1rem;border-radius:8px;border:1px solid #c9c9d6;background:#fff;color:#1a1a2e;text-decoration:none;cursor:pointer}
.kit-toolbar button{background:#6c63ff;color:#fff;border-color:#6c63ff}
.kit-intro{max-width:1000px;margin:0 auto 1rem;font-size:.85rem;color:#555}
.kit-grid{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1rem}
.kit-card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:1.1rem;text-align:center;display:flex;flex-direction:column;align-items:center;gap:.5rem;break-inside:avoid;page-break-inside:avoid}
.kit-kind{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#6c63ff;background:#efeefe;padding:.2rem .6rem;border-radius:20px}
.kit-label{font-size:1rem;font-weight:700;line-height:1.25}
.kit-card .qr{width:170px;height:170px;display:flex;align-items:center;justify-content:center}
.kit-card .qr img,.kit-card .qr canvas{width:170px!important;height:170px!important}
.kit-url{font-size:.66rem;color:#888;word-break:break-all;font-family:'Courier New',monospace}
.kit-dl{font:inherit;font-size:.78rem;font-weight:600;padding:.4rem .85rem;border-radius:8px;border:1px solid #c9c9d6;background:#fff;color:#1a1a2e;cursor:pointer;margin-top:.25rem}
.kit-dl:hover{background:#f0f0f5}
.kit-foot{max-width:1000px;margin:1.5rem auto 0;text-align:center;font-size:.75rem;color:#aaa}
@media print{
  body{background:#fff;padding:0}
  .no-print{display:none!important}
  .kit-grid{gap:.5rem}
  .kit-card{border-color:#bbb}
}
</style>
</head>
<body>
<div class="kit-toolbar no-print">
  <a href="<?= url('admin/sequences/' . (int)$seq['id'] . '/edit') ?>">&larr; Back</a>
  <h1>QR Codes &mdash; <?= e($seq['title']) ?></h1>
  <a href="<?= url('admin/sequences/' . (int)$seq['id'] . '/promo') ?>" target="_blank">🪧 Print/Save Promo</a>
  <button type="button" id="kit-save-all">💾 Save all PNGs</button>
  <button type="button" onclick="window.print()">🖨 Print / Save PDF</button>
</div>
<p class="kit-intro no-print">Cut these out for your printed kit. Each code opens its page when scanned with a phone camera. <strong><?= count($targets) ?></strong> code<?= count($targets) === 1 ? '' : 's' ?> below.</p>

<div class="kit-grid">
  <?php foreach ($targets as $t): ?>
  <div class="kit-card">
    <span class="kit-kind"><?= e($t['kind']) ?></span>
    <div class="kit-label"><?= e($t['label']) ?></div>
    <div class="qr" data-url="<?= e($t['url']) ?>"></div>
    <div class="kit-url"><?= e($t['url']) ?></div>
    <button type="button" class="kit-dl no-print" data-name="<?= e($t['kind'] . ' ' . $t['label']) ?>">💾 Save PNG</button>
  </div>
  <?php endforeach; ?>
</div>
<div class="kit-foot">Powered by Simply Creative Games</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function qrFileName(name) {
  return (String(name || 'qr').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 50) || 'qr') + '.png';
}
function qrDataUrl(qrEl) {
  var canvas = qrEl.querySelector('canvas');
  if (canvas) { return canvas.toDataURL('image/png'); }
  var img = qrEl.querySelector('img');
  return img ? img.src : '';
}
function downloadDataUrl(dataUrl, filename) {
  if (!dataUrl) { return; }
  var a = document.createElement('a');
  a.href = dataUrl; a.download = filename;
  document.body.appendChild(a); a.click(); a.remove();
}

// Render each QR at high resolution (CSS scales it down on screen / in print),
// so the downloaded PNG stays crisp for reuse.
document.querySelectorAll('.kit-card').forEach(function (card) {
  var qrEl = card.querySelector('.qr');
  new QRCode(qrEl, {
    text: qrEl.getAttribute('data-url'),
    width: 512, height: 512,
    correctLevel: QRCode.CorrectLevel.M
  });
  var btn = card.querySelector('.kit-dl');
  if (btn) {
    btn.addEventListener('click', function () {
      downloadDataUrl(qrDataUrl(qrEl), qrFileName(btn.getAttribute('data-name')));
    });
  }
});

// Save every QR as its own PNG (staggered so the browser allows the downloads).
var saveAll = document.getElementById('kit-save-all');
if (saveAll) {
  saveAll.addEventListener('click', function () {
    var cards = [].slice.call(document.querySelectorAll('.kit-card'));
    cards.forEach(function (card, i) {
      setTimeout(function () {
        var qrEl = card.querySelector('.qr');
        var btn = card.querySelector('.kit-dl');
        downloadDataUrl(qrDataUrl(qrEl), qrFileName(btn ? btn.getAttribute('data-name') : 'qr-' + (i + 1)));
      }, i * 350);
    });
  });
}
</script>
</body>
</html>
