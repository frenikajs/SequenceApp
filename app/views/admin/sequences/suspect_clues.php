<?php
$pageTitle = 'Suspect Clues: ' . ($sequence['title'] ?? '');
$activeNav = 'sequences';
$seqId     = (int)$sequence['id'];
$isInteractive = ($sequence['type'] ?? '') === 'interactive';
ob_start();
?>

<div class="breadcrumb">
  <a href="<?= url('admin/sequences') ?>">Sequences</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>"><?= e($sequence['title']) ?></a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>">Clues</a> /
  Suspect Clues
</div>

<div class="ov-head">
  <h1 class="ov-title">🎭 Suspect Clues</h1>
  <div class="ov-actions">
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-ghost btn-sm">← Manage Clues</a>
    <a href="<?= url('admin/sequences/' . $seqId . '/interactive') ?>" class="btn btn-ghost btn-sm">Interactive Setup</a>
  </div>
</div>

<?php if (!$isInteractive): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">Suspect clues only apply to <strong>Interactive</strong> sequences.</div>
<?php endif; ?>

<?php if (empty($suspects)): ?>
<div class="card"><div class="card-body">
  <p>No suspects yet. Add suspects on <a href="<?= url('admin/sequences/' . $seqId . '/interactive') ?>">Interactive Setup</a> first.</p>
</div></div>
<?php elseif (empty($clues)): ?>
<div class="card"><div class="card-body">
  <p>No clue steps yet. Add clues on <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>">Manage Clues</a> first — each clue step gets a private clue per suspect here.</p>
</div></div>
<?php else: ?>

<p class="small muted" style="margin-bottom:1rem">For each clue step the host unlocks, give every suspect their own private clue (text + optional media: PNG, JPG, PDF, or audio). When the host unlocks Clue N, each player sees their suspect's clue for that step as a pop-up and in their evidence locker. Leave a cell blank if that suspect gets nothing for that step.</p>

<form method="POST" action="<?= url('admin/sequences/' . $seqId . '/suspect-clues') ?>" id="sc-form">
  <?= csrf_field() ?>

  <?php foreach ($clues as $ci => $clue): $clueId = (int)$clue['id']; ?>
  <div class="card sc-clue-card">
    <div class="card-header">
      <h2>Clue <?= $ci + 1 ?><?= $clue['title'] ? ': ' . e($clue['title']) : '' ?></h2>
    </div>
    <div class="card-body">
      <div class="sc-grid">
        <?php foreach ($suspects as $si => $sus):
          $cell = $matrix[$clueId][$si] ?? null;
          $body = $cell['body'] ?? '';
          $path = $cell['file_path'] ?? '';
          $ftype = $cell['file_type'] ?? '';
          $fname = $cell['original_filename'] ?? '';
        ?>
        <div class="sc-cell" data-clue="<?= $clueId ?>" data-suspect="<?= $si ?>">
          <div class="sc-cell-head">🕵️ <?= e($sus['name'] ?? ('Suspect ' . ($si + 1))) ?></div>
          <textarea name="sc_body[<?= $clueId ?>][<?= $si ?>]" class="form-input sc-body" rows="3"
                    placeholder="This suspect's clue for this step…"><?= e($body) ?></textarea>
          <input type="hidden" name="sc_path[<?= $clueId ?>][<?= $si ?>]" class="sc-path" value="<?= e($path) ?>">
          <input type="hidden" name="sc_type[<?= $clueId ?>][<?= $si ?>]" class="sc-type" value="<?= e($ftype) ?>">
          <input type="hidden" name="sc_name[<?= $clueId ?>][<?= $si ?>]" class="sc-name" value="<?= e($fname) ?>">
          <div class="sc-media"></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="form-actions" style="position:sticky;bottom:0;background:var(--admin-bg,#10131a);padding:1rem 0">
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary">Save Suspect Clues</button>
  </div>
</form>

<script>
const UPLOADS_URL = <?= json_encode(UPLOAD_URL) ?>;
const SEQ_ID      = <?= $seqId ?>;
const CSRF_TOKEN  = <?= json_encode(Security::generateCsrfToken()) ?>;

function scMediaPreview(type, path, name) {
  if (!path) return '';
  const url = UPLOADS_URL + '/' + path;
  let inner;
  if (type === 'image')      inner = '<img src="' + url + '" alt="" class="sc-media-thumb">';
  else if (type === 'pdf')   inner = '<a href="' + url + '" target="_blank" rel="noopener">📄 ' + (name || 'PDF') + '</a>';
  else if (type === 'audio') inner = '<audio controls src="' + url + '"></audio>';
  else                       inner = '<a href="' + url + '" target="_blank" rel="noopener">📎 ' + (name || 'File') + '</a>';
  return '<div class="sc-media-cur">' + inner + ' <button type="button" class="btn btn-danger btn-xs sc-media-del">Remove</button></div>';
}

function scRenderCell(cell) {
  const path = cell.querySelector('.sc-path').value;
  const type = cell.querySelector('.sc-type').value;
  const name = cell.querySelector('.sc-name').value;
  const media = cell.querySelector('.sc-media');
  if (path) {
    media.innerHTML = scMediaPreview(type, path, name);
    media.querySelector('.sc-media-del').addEventListener('click', function () {
      cell.querySelector('.sc-path').value = '';
      cell.querySelector('.sc-type').value = '';
      cell.querySelector('.sc-name').value = '';
      scRenderCell(cell);
    });
  } else {
    media.innerHTML = '<label class="btn btn-ghost btn-xs">📁 Attach media' +
      '<input type="file" accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a" hidden class="sc-file"></label>';
    media.querySelector('.sc-file').addEventListener('change', function () {
      const f = this.files[0]; if (!f) return;
      media.innerHTML = '<span class="muted small">Uploading…</span>';
      const fd = new FormData();
      fd.append('csrf_token', CSRF_TOKEN);
      fd.append('target', 'suspect');
      fd.append('target_id', SEQ_ID);
      fd.append('file_key', 'file');
      fd.append('file', f);
      fetch('/admin/media/upload', { method: 'POST', credentials: 'same-origin', body: fd })
        .then(r => r.json())
        .then(d => {
          if (d && d.success) {
            cell.querySelector('.sc-path').value = d.file_path;
            cell.querySelector('.sc-type').value = d.file_type;
            cell.querySelector('.sc-name').value = d.original_name || '';
          } else { alert((d && d.error) || 'Upload failed.'); }
          scRenderCell(cell);
        })
        .catch(() => { alert('Upload failed.'); scRenderCell(cell); });
    });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.sc-cell').forEach(scRenderCell);
});
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
