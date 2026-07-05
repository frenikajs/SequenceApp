<?php
$pageTitle = 'Whodunnit Setup: ' . ($sequence['title'] ?? '');
$activeNav = 'sequences';
$seqId     = (int)$sequence['id'];
$isWhodunit = ($sequence['type'] ?? '') === 'whodunit';
ob_start();
?>

<div class="breadcrumb">
  <a href="<?= url('admin/sequences') ?>">Sequences</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>"><?= e($sequence['title']) ?></a> /
  Whodunnit Setup
</div>

<div class="ov-head">
  <h1 class="ov-title">🕵️ Whodunnit Setup</h1>
  <div class="ov-actions">
    <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>" class="btn btn-ghost btn-sm">← Back to Sequence</a>
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-ghost btn-sm">Manage Clues</a>
  </div>
</div>

<?php if (!$isWhodunit): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">
  This sequence's type isn't set to <strong>Who-dun-it</strong> yet. You can build the accusation here, but switch the Type to “Who-dun-it (Deduction)” on the
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>">Edit screen</a> for it to play as a deduction case.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <p class="small muted" style="margin-bottom:1rem">Build a Whodunnit deduction case. Players study the <strong>clues</strong> (added on the Clues screen as evidence), work the <strong>notepad</strong> to eliminate options, then make an <strong>accusation</strong> — one option per category. Getting <strong>every</strong> category right reveals the ending. Add up to 5 categories (e.g. Suspects, Weapons, Rooms, Motive); each needs at least two options and one marked correct. <em>You're responsible for writing evidence that leads to a single solution.</em></p>

    <form method="POST" action="<?= url('admin/sequences/' . $seqId . '/whodunnit') ?>" id="whodunnit-form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label>Accusation Prompt <span class="muted">(optional)</span></label>
        <input type="text" name="accusation_prompt" id="acc-prompt" class="form-input"
               placeholder="e.g. So — who killed Lord Ashford, with what, and where?">
      </div>

      <label style="display:block;margin-bottom:.5rem">Categories &amp; Character Cards</label>
      <div id="acc-cats"></div>
      <button type="button" class="btn btn-ghost btn-sm mt-2" id="acc-add-cat">＋ Add category</button>
      <input type="hidden" name="accusation_json" id="accusation-json">

      <div class="form-actions" style="margin-top:1.5rem">
        <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Whodunnit Setup</button>
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

let _accUid = 0;

function accUploadImage(file, onDone, onErr) {
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

function accOptionRow(catEl, opt) {
  opt = opt || {};
  const radioName = catEl.dataset.radio;
  const row = document.createElement('div');
  row.className = 'acc-opt-row';
  row.dataset.image = opt.image || '';
  row.innerHTML =
    '<label class="acc-opt-correct" title="Mark as the correct answer">' +
      '<input type="radio" name="' + radioName + '"></label>' +
    '<div class="acc-thumb"></div>' +
    '<label class="acc-img-btn" title="Upload card image">📷<input type="file" accept="image/*" class="acc-img-input" hidden></label>' +
    '<div class="acc-opt-fields">' +
      '<input type="text" class="acc-opt-name form-input" placeholder="Name (e.g. Miss Scarlett)" maxlength="120">' +
      '<input type="text" class="acc-opt-desc form-input" placeholder="Short description (optional)" maxlength="240">' +
    '</div>' +
    '<button type="button" class="btn btn-danger btn-xs acc-opt-del" title="Remove option">✕</button>';

  row.querySelector('.acc-opt-name').value = opt.name || '';
  row.querySelector('.acc-opt-desc').value = opt.desc || '';

  const thumb = row.querySelector('.acc-thumb');
  function renderThumb() {
    if (row.dataset.image) {
      thumb.innerHTML = '<img src="' + UPLOADS_URL + '/' + row.dataset.image + '" alt="">' +
        '<button type="button" class="acc-img-del" title="Remove image">✕</button>';
      thumb.querySelector('.acc-img-del').addEventListener('click', function () {
        row.dataset.image = ''; renderThumb();
      });
    } else {
      thumb.innerHTML = '';
    }
  }
  renderThumb();

  row.querySelector('.acc-img-input').addEventListener('change', function () {
    const f = this.files[0];
    if (!f) return;
    thumb.innerHTML = '<span class="acc-thumb-load">…</span>';
    accUploadImage(f,
      function (d) { row.dataset.image = d.file_path; renderThumb(); },
      function (err) { alert(err); renderThumb(); });
    this.value = '';
  });

  row.querySelector('.acc-opt-del').addEventListener('click', function () {
    const list = row.parentNode;
    row.remove();
    while (list.querySelectorAll('.acc-opt-row').length < 2) accAddOption(catEl);
  });
  return row;
}

function accAddOption(catEl, opt, isAnswer) {
  const row = accOptionRow(catEl, opt);
  catEl.querySelector('.acc-opts').appendChild(row);
  if (isAnswer) row.querySelector('input[type=radio]').checked = true;
}

function accAddCategory(cat) {
  const wrap = document.getElementById('acc-cats');
  const uid = 'acccor-' + (_accUid++);
  const el = document.createElement('div');
  el.className = 'acc-cat-edit';
  el.dataset.radio = uid;
  el.innerHTML =
    '<div class="acc-cat-head">' +
      '<input type="text" class="acc-cat-label form-input" placeholder="Category (e.g. Suspects)" maxlength="80">' +
      '<button type="button" class="btn btn-danger btn-xs acc-cat-del" title="Remove category">Remove</button>' +
    '</div>' +
    '<div class="acc-opts"></div>' +
    '<button type="button" class="btn btn-ghost btn-xs acc-add-opt mt-2">＋ Add option</button>' +
    '<p class="small muted" style="margin-top:.35rem">Select the radio next to the correct answer.</p>';
  wrap.appendChild(el);

  el.querySelector('.acc-cat-label').value = (cat && cat.label) || '';
  el.querySelector('.acc-cat-del').addEventListener('click', () => el.remove());
  el.querySelector('.acc-add-opt').addEventListener('click', () => accAddOption(el));

  const opts = (cat && Array.isArray(cat.options) && cat.options.length) ? cat.options : [{}, {}];
  const ans  = cat ? (parseInt(cat.answer, 10) || 0) : 0;
  opts.forEach((o, i) => accAddOption(el, (typeof o === 'string' ? { name: o } : o), i === ans));
}

function serializeAccusation() {
  const cats = [];
  document.querySelectorAll('#acc-cats .acc-cat-edit').forEach(function (el) {
    const label = (el.querySelector('.acc-cat-label').value || '').trim();
    const options = [];
    let answer = 0, ai = 0;
    el.querySelectorAll('.acc-opt-row').forEach(function (row) {
      const name = (row.querySelector('.acc-opt-name').value || '').trim();
      if (name === '') return;
      if (row.querySelector('input[type=radio]').checked) answer = ai;
      options.push({
        name: name,
        image: row.dataset.image || '',
        desc: (row.querySelector('.acc-opt-desc').value || '').trim(),
      });
      ai++;
    });
    // Save in-progress work: a category just needs a label and at least one
    // option here. Health Check warns about under-built categories.
    if (label !== '' && options.length >= 1) {
      cats.push({ label: label, options: options, answer: answer });
    }
  });
  if (cats.length === 0) return '';
  const prompt = (document.getElementById('acc-prompt').value || '').trim();
  return JSON.stringify({ prompt: prompt, categories: cats });
}

// Surface anything the server would silently drop so users know exactly what's missing.
function validateAccusation() {
  const issues = [];
  let i = 0;
  document.querySelectorAll('#acc-cats .acc-cat-edit').forEach(function (el) {
    i++;
    const label = (el.querySelector('.acc-cat-label').value || '').trim();
    let validOpts = 0;
    el.querySelectorAll('.acc-opt-row .acc-opt-name').forEach(function (input) {
      if ((input.value || '').trim() !== '') validOpts++;
    });
    if (label === '') {
      issues.push('Category #' + i + ' has no name.');
    }
    if (validOpts < 1) {
      issues.push('Category "' + (label || '#' + i) + '" needs at least one option with a name.');
    }
  });
  return issues;
}

document.addEventListener('DOMContentLoaded', function () {
  if (ACCUSATION) {
    if (ACCUSATION.prompt) document.getElementById('acc-prompt').value = ACCUSATION.prompt;
    (ACCUSATION.categories || []).forEach(accAddCategory);
  }
  document.getElementById('acc-add-cat').addEventListener('click', () => accAddCategory());
  document.getElementById('whodunnit-form').addEventListener('submit', function (e) {
    const issues = validateAccusation();
    if (issues.length) {
      e.preventDefault();
      alert('Please fix these before saving:\n\n• ' + issues.join('\n• '));
      return;
    }
    document.getElementById('accusation-json').value = serializeAccusation();
  });
});
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
