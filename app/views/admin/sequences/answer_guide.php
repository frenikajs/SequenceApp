<?php
/**
 * Answer Guide — admin-only. Builds a PDF of every code and puzzle answer in play
 * order (client-side via jsPDF) and downloads it automatically on load. The same
 * content is rendered as HTML below so it's never lost if the PDF library is blocked.
 * Vars: $seq (sequence row), $guide (ordered sections: ['label'=>..,'lines'=>[['k','v']]]).
 */
$backUrl  = url('admin/sequences/' . (int)$seq['id'] . '/edit');
$title    = (string)($seq['title'] ?? 'Mystery');
$slug     = (string)($seq['slug'] ?? ('sequence-' . (int)$seq['id']));
$fileBase = preg_replace('/[^a-z0-9]+/', '-', strtolower($slug));
$fileBase = trim((string)$fileBase, '-') ?: 'answer-guide';
$generated = date('M j, Y g:i a');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Answer Guide — <?= e($title) ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:#e9e6df;color:#1a1a1a;padding:1.5rem;line-height:1.55}
.ag-toolbar{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;max-width:760px;margin:0 auto 1.25rem}
.ag-toolbar a,.ag-toolbar button{font:inherit;font-size:.9rem;font-weight:600;padding:.55rem 1rem;border-radius:8px;border:1px solid #c3bdb0;background:#fff;color:#1a1a1a;text-decoration:none;cursor:pointer}
.ag-toolbar button{background:#6c63ff;color:#fff;border-color:#6c63ff}
.ag-toolbar .ag-status{flex:1;min-width:160px;font-size:.85rem;font-weight:600;color:#555;text-align:right}
.sheet{max-width:760px;margin:0 auto;background:#fff;border:1px solid #ccc;border-radius:6px;padding:2.5rem;box-shadow:0 10px 30px rgba(0,0,0,.2)}
.ag-title{font-size:1.8rem;font-weight:800;letter-spacing:-.01em;margin-bottom:.25rem}
.ag-sub{color:#777;font-size:.85rem;margin-bottom:1.75rem}
.ag-section{margin:1.4rem 0;padding-top:1rem;border-top:1px solid #e3e3e3}
.ag-section:first-of-type{border-top:none;padding-top:0}
.ag-h{font-size:1.05rem;font-weight:700;color:#2a2a4a;margin-bottom:.55rem}
.ag-lines{list-style:none;padding:0;display:flex;flex-direction:column;gap:.35rem}
.ag-lines li{display:flex;gap:.6rem;font-size:1rem;align-items:baseline}
.ag-k{font-weight:600;color:#555;min-width:120px;flex-shrink:0}
.ag-v{font-family:'Courier New',monospace;font-weight:700;color:#1a1a1a;word-break:break-word}
.ag-foot{text-align:center;color:#999;font-size:.72rem;margin-top:2rem;letter-spacing:.06em}
@media print{
  @page{ size: letter portrait; margin: 0.6in; }
  html,body{background:#fff;padding:0;margin:0}
  .no-print{display:none!important}
  .sheet{box-shadow:none;border:none;border-radius:0;max-width:100%;padding:0}
}
</style>
</head>
<body>
<div class="ag-toolbar no-print">
  <a href="<?= e($backUrl) ?>">&larr; Back</a>
  <button type="button" id="ag-download">📥 Download Answer Guide (PDF)</button>
  <span class="ag-status" id="ag-status"></span>
</div>

<div class="sheet">
  <h1 class="ag-title"><?= e($title) ?> — Answer Guide</h1>
  <p class="ag-sub">Generated <?= e($generated) ?> · For your eyes only — contains all codes &amp; puzzle answers.</p>

  <?php foreach ($guide as $section): ?>
  <div class="ag-section">
    <div class="ag-h"><?= e($section['label']) ?></div>
    <ul class="ag-lines">
      <?php foreach ($section['lines'] as $line): ?>
      <li><span class="ag-k"><?= e((string)$line['k']) ?></span><span class="ag-v"><?= e((string)$line['v']) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>

  <div class="ag-foot">Simply Creative Games</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const AG_TITLE     = <?= json_encode($title, JSON_UNESCAPED_UNICODE) ?>;
const AG_FILENAME  = <?= json_encode($fileBase . '-answer-guide.pdf') ?>;
const AG_GUIDE     = <?= json_encode($guide, JSON_UNESCAPED_UNICODE) ?>;

// jsPDF's standard fonts use WinAnsi encoding — swap the few non-Latin-1 chars
// (smart dashes/quotes) the data might contain for safe ASCII equivalents.
function asciiSafe(s) {
  return String(s == null ? '' : s)
    .replace(/[–—]/g, '-')
    .replace(/[‘’]/g, "'")
    .replace(/[“”]/g, '"')
    .replace(/…/g, '...');
}

function buildPdf() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'pt', format: 'letter' });
  const margin = 54;                       // 0.75"
  const pageW = doc.internal.pageSize.getWidth();
  const pageH = doc.internal.pageSize.getHeight();
  const maxW = pageW - margin * 2;
  let y = margin;

  const ensure = (space) => { if (y + space > pageH - margin) { doc.addPage(); y = margin; } };

  // Title + subtitle
  doc.setFont('helvetica', 'bold'); doc.setFontSize(20); doc.setTextColor(20);
  doc.splitTextToSize(asciiSafe(AG_TITLE) + ' - Answer Guide', maxW).forEach((ln) => {
    ensure(26); doc.text(ln, margin, y); y += 26;
  });
  doc.setFont('helvetica', 'normal'); doc.setFontSize(10); doc.setTextColor(130);
  ensure(20);
  doc.text('Codes & puzzle answers', margin, y);
  y += 22;

  AG_GUIDE.forEach((section) => {
    // Section header with a divider above it
    ensure(40);
    y += 6;
    doc.setDrawColor(210); doc.setLineWidth(0.5);
    doc.line(margin, y, pageW - margin, y);
    y += 18;
    doc.setFont('helvetica', 'bold'); doc.setFontSize(13); doc.setTextColor(42, 42, 74);
    doc.splitTextToSize(asciiSafe(section.label), maxW).forEach((ln) => {
      ensure(18); doc.text(ln, margin, y); y += 18;
    });
    y += 2;

    // Key/value lines
    (section.lines || []).forEach((line) => {
      const kStr = asciiSafe(line.k) + ':  ';
      doc.setFont('helvetica', 'bold'); doc.setFontSize(11); doc.setTextColor(85);
      const kW = doc.getTextWidth(kStr);
      doc.setFont('helvetica', 'normal'); doc.setTextColor(20);
      const vLines = doc.splitTextToSize(asciiSafe(line.v), Math.max(60, maxW - 14 - kW));
      ensure(16 * vLines.length);
      // key on the first line, value (possibly wrapped) beside it
      doc.setFont('helvetica', 'bold'); doc.setTextColor(85);
      doc.text(kStr, margin + 14, y);
      doc.setFont('helvetica', 'normal'); doc.setTextColor(20);
      doc.text(vLines, margin + 14 + kW, y);
      y += 16 * vLines.length;
    });
  });

  doc.save(AG_FILENAME);
}

function setStatus(msg) { const el = document.getElementById('ag-status'); if (el) el.textContent = msg; }

function downloadGuide() {
  if (!window.jspdf || !window.jspdf.jsPDF) {
    setStatus('PDF library failed to load — use your browser Print to save as PDF.');
    return false;
  }
  try {
    buildPdf();
    setStatus('Downloaded ✓');
    return true;
  } catch (err) {
    setStatus('Could not build the PDF — use your browser Print instead.');
    return false;
  }
}

document.getElementById('ag-download').addEventListener('click', downloadGuide);

// Auto-download once on load (the page was opened by clicking the button).
window.addEventListener('load', function () {
  setStatus('Preparing your download…');
  setTimeout(downloadGuide, 250);
});
</script>
</body>
</html>
