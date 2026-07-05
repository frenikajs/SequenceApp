<?php
$pageTitle = 'Interactive Setup: ' . ($sequence['title'] ?? '');
$activeNav = 'sequences';
$seqId     = (int)$sequence['id'];
$isInteractive = ($sequence['type'] ?? '') === 'interactive';
ob_start();
?>

<div class="breadcrumb">
  <a href="<?= url('admin/sequences') ?>">Sequences</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>"><?= e($sequence['title']) ?></a> /
  Interactive Setup
</div>

<div class="ov-head">
  <h1 class="ov-title">🎭 Interactive Setup</h1>
  <div class="ov-actions">
    <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>" class="btn btn-ghost btn-sm">← Back to Sequence</a>
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-ghost btn-sm">Manage Clues</a>
    <a href="<?= url('admin/sequences/' . $seqId . '/suspect-clues') ?>" class="btn btn-ghost btn-sm">🎭 Suspect Clues</a>
  </div>
</div>

<?php if (!$isInteractive): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">
  This sequence's type isn't set to <strong>Interactive</strong> yet. You can build the suspect list here, but switch the Type to “Interactive” on the
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>">Edit screen</a> for it to play as an interactive case.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <p class="small muted" style="margin-bottom:1rem">Build the cast of <strong>Suspects</strong> for this Interactive mystery. Each player adopts one suspect, receives that suspect's private clues (set up on <a href="<?= url('admin/sequences/' . $seqId . '/suspect-clues') ?>">Suspect Clues</a>), and the group votes on the culprit at the end. Give each suspect a <strong>name</strong>, an optional description and portrait, and an optional <strong>game card</strong> (PNG, JPG, or PDF) players can open during play. Mark exactly one suspect as the <strong>culprit</strong> (the correct answer for the group vote).</p>
    <p class="small muted" style="margin-bottom:1rem"><strong>⚠ Don't reorder or remove suspects after assigning Suspect Clues</strong> — clues are tied to each suspect's position in this list.</p>

    <form method="POST" action="<?= url('admin/sequences/' . $seqId . '/interactive') ?>" id="interactive-form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label>Accusation Prompt <span class="muted">(optional)</span></label>
        <input type="text" name="accusation_prompt" id="acc-prompt" class="form-input"
               placeholder="e.g. Who do you think the culprit is?">
      </div>

      <label style="display:block;margin-bottom:.5rem">Suspects</label>
      <div class="acc-cat-edit" data-radio="suspects-culprit">
        <div class="acc-opts" id="suspect-opts"></div>
        <button type="button" class="btn btn-ghost btn-xs acc-add-opt mt-2" id="suspect-add">＋ Add suspect</button>
        <p class="small muted" style="margin-top:.35rem">Select the radio next to the <strong>culprit</strong> (the correct answer).</p>
      </div>
      <input type="hidden" name="accusation_json" id="accusation-json">

      <div class="form-actions" style="margin-top:1.5rem">
        <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Interactive Setup</button>
      </div>
    </form>
  </div>
</div>

<script>
<?php $accCfg = json_decode($sequence['accusation_json'] ?? '', true); ?>
const ACCUSATION  = <?= json_encode(is_array($accCfg) ? $accCfg : null) ?>;
const UPLOADS_URL = <?= json_encode(UPLOAD_URL) ?>;
const SEQ_ID      = <?= $seqId ?>;
const CSRF_TOKEN  = <?= json_encode(Security::generateCsrfToken()) ?>;

// Upload a file (image OR pdf) and report back the stored path + type.
function scUpload(file, onDone, onErr) {
  const fd = new FormData();
  fd.append('csrf_token', CSRF_TOKEN);
  fd.append('target', 'whodunit');
  fd.append('target_id', SEQ_ID);
  fd.append('file_key', 'file');
  fd.append('file', file);
  fetch('/admin/media/upload', { method: 'POST', credentials: 'same-origin', body: fd })
    .then(r => r.json())
    .then(d => { if (d && d.success) onDone(d); else onErr((d && d.error) || 'Upload failed.'); })
    .catch(() => onErr('Upload failed.'));
}

function suspectRow(opt) {
  opt = opt || {};
  const row = document.createElement('div');
  row.className = 'acc-opt-row';
  row.dataset.image = opt.image || '';
  row.dataset.card  = opt.card || '';
  row.dataset.cardType = opt.card_type || '';
  row.innerHTML =
    '<label class="acc-opt-correct" title="Mark as the culprit">' +
      '<input type="radio" name="suspects-culprit"></label>' +
    '<div class="acc-thumb"></div>' +
    '<label class="acc-img-btn" title="Upload portrait">📷<input type="file" accept="image/*" class="acc-img-input" hidden></label>' +
    '<div class="acc-opt-fields">' +
      '<input type="text" class="acc-opt-name form-input" placeholder="Suspect name (e.g. Miss Scarlett)" maxlength="120">' +
      '<input type="text" class="acc-opt-desc form-input" placeholder="Short description (optional)" maxlength="240">' +
      '<div class="sc-card-row"></div>' +
    '</div>' +
    '<button type="button" class="btn btn-danger btn-xs acc-opt-del" title="Remove suspect">✕</button>';

  row.querySelector('.acc-opt-name').value = opt.name || '';
  row.querySelector('.acc-opt-desc').value = opt.desc || '';

  const thumb = row.querySelector('.acc-thumb');
  function renderThumb() {
    if (row.dataset.image) {
      thumb.innerHTML = '<img src="' + UPLOADS_URL + '/' + row.dataset.image + '" alt="">' +
        '<button type="button" class="acc-img-del" title="Remove portrait">✕</button>';
      thumb.querySelector('.acc-img-del').addEventListener('click', function () { row.dataset.image = ''; renderThumb(); });
    } else { thumb.innerHTML = ''; }
  }
  renderThumb();

  row.querySelector('.acc-img-input').addEventListener('change', function () {
    const f = this.files[0]; if (!f) return;
    thumb.innerHTML = '<span class="acc-thumb-load">…</span>';
    scUpload(f, function (d) { row.dataset.image = d.file_path; renderThumb(); },
                function (err) { alert(err); renderThumb(); });
    this.value = '';
  });

  // Game card (png/jpg/pdf) controls
  const cardRow = row.querySelector('.sc-card-row');
  function renderCard() {
    if (row.dataset.card) {
      const isPdf = row.dataset.cardType === 'pdf';
      cardRow.innerHTML = '<span class="sc-card-chip">' + (isPdf ? '📄' : '🎴') + ' Game card attached ' +
        '<a href="' + UPLOADS_URL + '/' + row.dataset.card + '" target="_blank" rel="noopener">view</a></span>' +
        '<button type="button" class="btn btn-danger btn-xs sc-card-del">Remove card</button>';
      cardRow.querySelector('.sc-card-del').addEventListener('click', function () {
        row.dataset.card = ''; row.dataset.cardType = ''; renderCard();
      });
    } else {
      cardRow.innerHTML = '<label class="btn btn-ghost btn-xs">🎴 Upload game card (PNG/JPG/PDF)' +
        '<input type="file" accept=".png,.jpg,.jpeg,.pdf" class="sc-card-input" hidden></label>';
      cardRow.querySelector('.sc-card-input').addEventListener('change', function () {
        const f = this.files[0]; if (!f) return;
        cardRow.innerHTML = '<span class="acc-thumb-load">Uploading…</span>';
        scUpload(f, function (d) { row.dataset.card = d.file_path; row.dataset.cardType = d.file_type; renderCard(); },
                    function (err) { alert(err); renderCard(); });
        this.value = '';
      });
    }
  }
  renderCard();

  row.querySelector('.acc-opt-del').addEventListener('click', function () {
    const list = row.parentNode;
    row.remove();
    while (list.querySelectorAll('.acc-opt-row').length < 2) addSuspect();
  });
  return row;
}

function addSuspect(opt, isCulprit) {
  const row = suspectRow(opt);
  document.getElementById('suspect-opts').appendChild(row);
  if (isCulprit) row.querySelector('input[type=radio]').checked = true;
}

function serializeInteractive() {
  const options = [];
  let answer = 0, ai = 0;
  document.querySelectorAll('#suspect-opts .acc-opt-row').forEach(function (row) {
    const name = (row.querySelector('.acc-opt-name').value || '').trim();
    if (name === '') return;
    if (row.querySelector('input[type=radio]').checked) answer = ai;
    options.push({
      name: name,
      image: row.dataset.image || '',
      desc: (row.querySelector('.acc-opt-desc').value || '').trim(),
      card: row.dataset.card || '',
      card_type: row.dataset.cardType || '',
    });
    ai++;
  });
  if (options.length === 0) return '';
  const prompt = (document.getElementById('acc-prompt').value || '').trim();
  return JSON.stringify({ prompt: prompt, categories: [{ label: 'Suspects', options: options, answer: answer }] });
}

function validateInteractive() {
  const issues = [];
  let named = 0, hasCulprit = false;
  document.querySelectorAll('#suspect-opts .acc-opt-row').forEach(function (row) {
    if ((row.querySelector('.acc-opt-name').value || '').trim() !== '') {
      named++;
      if (row.querySelector('input[type=radio]').checked) hasCulprit = true;
    }
  });
  if (named < 2) issues.push('Add at least two suspects.');
  if (named > 0 && !hasCulprit) issues.push('Mark one suspect as the culprit (the radio button).');
  return issues;
}

document.addEventListener('DOMContentLoaded', function () {
  const cat = (ACCUSATION && Array.isArray(ACCUSATION.categories) && ACCUSATION.categories[0]) ? ACCUSATION.categories[0] : null;
  if (ACCUSATION && ACCUSATION.prompt) document.getElementById('acc-prompt').value = ACCUSATION.prompt;
  if (cat && Array.isArray(cat.options) && cat.options.length) {
    const ans = parseInt(cat.answer, 10) || 0;
    cat.options.forEach(function (o, i) { addSuspect(typeof o === 'string' ? { name: o } : o, i === ans); });
  } else {
    addSuspect(); addSuspect();
  }

  document.getElementById('suspect-add').addEventListener('click', function () { addSuspect(); });
  document.getElementById('interactive-form').addEventListener('submit', function (e) {
    const issues = validateInteractive();
    if (issues.length) {
      e.preventDefault();
      alert('Please fix these before saving:\n\n• ' + issues.join('\n• '));
      return;
    }
    document.getElementById('accusation-json').value = serializeInteractive();
  });
});
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
