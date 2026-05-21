<?php
$pageTitle = 'Clues: ' . ($sequence['title'] ?? '');
$activeNav = 'sequences';
$seqId     = (int)$sequence['id'];
ob_start();
?>

<div class="breadcrumb">
  <a href="<?= url('admin/sequences') ?>">Sequences</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>"><?= e($sequence['title']) ?></a> /
  Clues
</div>

<div class="clues-layout">
  <!-- Clue list (left) -->
  <div class="clue-list-col">
    <div class="card">
      <div class="card-header">
        <h2>Clues <span class="badge badge-gray"><?= count($clues) ?></span></h2>
        <small>Drag to reorder</small>
      </div>
      <div id="clue-sortable" class="clue-list">
        <?php if (empty($clues)): ?>
        <div class="clue-empty">No clues yet. Add the first one →</div>
        <?php endif; ?>
        <?php foreach ($clues as $i => $clue): ?>
        <?php
          $clueData = json_encode([
            'index'                  => $i + 1,
            'title'                  => $clue['title'],
            'content'                => $clue['content'],
            'reward_content'         => $clue['reward_content'] ?? '',
            'access_code'            => $clue['access_code'],
            'instruction'            => $clue['instruction'] ?? '',
            'hint_text'              => $clue['hint_text'],
            'file_path'              => $clue['file_path'],
            'file_type'              => $clue['file_type'],
            'original_filename'      => $clue['original_filename'],
            'hint_file_path'         => $clue['hint_file_path'],
            'hint_file_type'         => $clue['hint_file_type'],
            'hint_original_filename' => $clue['hint_original_filename'],
          ]);
          $escaped = htmlspecialchars($clueData, ENT_QUOTES, 'UTF-8');
        ?>
        <div class="clue-item" data-id="<?= $clue['id'] ?>">
          <div class="clue-handle">⋮⋮</div>
          <div class="clue-meta">
            <span class="clue-num"><?= $i + 1 ?></span>
            <strong><?= $clue['title'] ? e($clue['title']) : 'Clue ' . ($i + 1) ?></strong>
            <span class="clue-code muted">Code: <code><?= e($clue['access_code']) ?></code></span>
          </div>
          <div class="clue-actions">
            <?php if ($clue['file_type']): ?>
              <span class="media-badge" title="Has media"><?= mediaIcon($clue['file_type']) ?></span>
            <?php endif; ?>
            <?php if ($clue['hint_text'] || $clue['hint_file_path']): ?>
              <span class="media-badge" title="Has hint">&#128161;</span>
            <?php endif; ?>
            <?php if (isset($pages[(int)$clue['id']])): ?>
              <a href="<?= url('admin/clues/' . $clue['id'] . '/page') ?>"
                 class="btn btn-ghost btn-xs" title="Edit decoy page">&#127760; Page</a>
            <?php else: ?>
              <a href="<?= url('admin/clues/' . $clue['id'] . '/page') ?>"
                 class="btn btn-ghost btn-xs" title="Create decoy page">&#43; Page</a>
            <?php endif; ?>
            <?php if (isset($puzzles[(int)$clue['id']])): ?>
              <a href="<?= url('admin/clues/' . $clue['id'] . '/puzzle') ?>"
                 class="btn btn-ghost btn-xs" title="Edit puzzle page">&#129513; Puzzle</a>
            <?php else: ?>
              <a href="<?= url('admin/clues/' . $clue['id'] . '/puzzle') ?>"
                 class="btn btn-ghost btn-xs" title="Create puzzle page">&#43; Puzzle</a>
            <?php endif; ?>
            <button class="btn btn-ghost btn-xs"
              data-clue='<?= $escaped ?>'
              onclick="openCluePreview(this)"
              title="Preview how this clue appears to players">
              👁 Preview
            </button>
            <button class="btn btn-secondary btn-xs"
              data-clue-id="<?= $clue['id'] ?>"
              data-clue='<?= $escaped ?>'
              onclick="openEditClue(this)">
              Edit
            </button>
            <button class="btn btn-danger btn-xs"
              onclick="confirmDelete('<?= url('admin/clues/' . $clue['id'] . '/delete') ?>','Delete this clue? This cannot be undone.')">
              ✕
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Add / Edit clue form (right) -->
  <div class="clue-form-col">
    <div class="card" id="clue-form-card">
      <div class="card-header">
        <h2 id="clue-form-title">Add New Clue</h2>
      </div>
      <div class="card-body">
        <form method="POST" id="clue-form" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="_action" id="clue-action" value="add">
          <input type="hidden" name="_clue_id" id="clue-id-field" value="">

          <div class="form-group">
            <label>Title <span class="muted">(optional)</span></label>
            <input type="text" name="title" id="clue-title" placeholder="Clue 1: The First Fragment">
          </div>
          <div class="form-group">
            <label>Content <span class="muted">(the clue the player sees when they click the locked tile)</span></label>
            <div class="quill-editor" id="clue-content-editor"></div>
            <input type="hidden" name="content" id="clue-content-hidden">
          </div>
          <div class="form-group">
            <label>Reward <span class="muted">(rich-text reward shown after the tile is unlocked)</span></label>
            <div class="quill-editor" id="clue-reward-editor"></div>
            <input type="hidden" name="reward_content" id="clue-reward-hidden">
          </div>
          <div class="form-group">
            <label>Access Code <span class="req">*</span></label>
            <input type="text" name="access_code" id="clue-code" class="code-upper" required placeholder="FRAGMENT1">
            <small>User enters this to unlock the next clue.</small>
          </div>
          <div class="form-group">
            <label>Gate Instruction</label>
            <input type="text" name="clue_instruction" id="clue-instruction"
                   placeholder="Shown above this clue's code box. Leave blank for none.">
          </div>

          <!-- Clue file -->
          <div class="form-group">
            <label>Evidence / Media</label>
            <div id="clue-current-media" class="hidden"></div>
            <div class="upload-area" id="clue-upload-area">
              <div class="upload-placeholder">
                <span>📁 Upload evidence file</span>
                <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG — max 10MB</span>
              </div>
              <input type="file" name="clue_file" id="clue-file" class="upload-input"
                     accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
            </div>
            <div id="clue-file-preview" class="media-preview hidden"></div>
          </div>

          <!-- Hint -->
          <details class="hint-section">
            <summary>💡 Hint (optional)</summary>
            <div class="hint-body">
              <div class="form-group">
                <label>Hint Text</label>
                <div class="quill-editor" id="hint-content-editor"></div>
                <input type="hidden" name="hint_text" id="hint-content-hidden">
              </div>
              <div class="form-group">
                <label>Hint Media</label>
                <div id="hint-current-media" class="hidden"></div>
                <div class="upload-area" id="hint-upload-area">
                  <div class="upload-placeholder">
                    <span>📁 Upload hint file</span>
                    <span class="small muted">PNG, JPG, PDF, MP3, WAV, OGG — max 10MB</span>
                  </div>
                  <input type="file" name="hint_file" id="hint-file" class="upload-input"
                         accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg,.m4a,.mov">
                </div>
                <div id="hint-file-preview" class="media-preview hidden"></div>
              </div>
            </div>
          </details>

          <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="resetClueForm()">Clear</button>
            <button type="submit" class="btn btn-primary" id="clue-submit-btn">Add Clue</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- â"€â"€ Clue preview modal â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€ -->
<div id="clue-preview-overlay" class="cp-overlay hidden" onclick="handleOverlayClick(event)">
  <div class="cp-modal">
    <div class="cp-modal-header">
      <span class="cp-modal-label">Player Preview</span>
      <button class="cp-close" onclick="closeCluePreview()">✕</button>
    </div>
    <div class="cp-modal-body">
      <!-- Clue card -->
      <div class="cp-card">
        <div class="cp-accent-bar"></div>
        <div class="cp-clue-label" id="cp-label">Clue 1</div>
        <h2 class="cp-clue-title" id="cp-title"></h2>
        <div class="cp-rich-content" id="cp-content"></div>
        <div class="cp-media-block" id="cp-media"></div>
      </div>

      <!-- Hint section -->
      <div class="cp-hint-wrap" id="cp-hint-wrap">
        <div class="cp-hint-toggle">
          <button class="cp-hint-btn" onclick="togglePreviewHint(this)">💡 Need a hint?</button>
        </div>
        <div class="cp-hint-body hidden" id="cp-hint-body">
          <div class="cp-rich-content" id="cp-hint-text"></div>
          <div class="cp-media-block" id="cp-hint-media"></div>
        </div>
      </div>

      <!-- Locked gate (shows what the player sees next) -->
      <div class="cp-gate">
        <div class="cp-gate-icon">🔒</div>
        <p class="cp-gate-sub">Next clue is locked. Find the access code to continue.</p>
        <div class="cp-code-row">
          <input class="cp-code-input" type="text" placeholder="Enter access code…" disabled>
          <button class="cp-code-btn" disabled>Unlock</button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* â"€â"€ Preview modal â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€ */
.cp-overlay {
  position: fixed; inset: 0; z-index: 600;
  background: rgba(0,0,0,.75);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}
.cp-overlay.hidden { display: none; }

.cp-modal {
  background: #0f0f1a;
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 16px;
  width: 100%; max-width: 680px;
  max-height: 90vh;
  display: flex; flex-direction: column;
  box-shadow: 0 30px 80px rgba(0,0,0,.7);
  overflow: hidden;
}

.cp-modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .85rem 1.25rem;
  background: #161b27;
  border-bottom: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}
.cp-modal-label {
  font-size: .78rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: #6b7280;
}
.cp-close {
  background: none; border: none; color: #6b7280;
  font-size: 1.1rem; cursor: pointer; padding: .2rem .4rem;
  border-radius: 4px; line-height: 1; transition: color .15s;
}
.cp-close:hover { color: #f3f4f6; }

.cp-modal-body {
  overflow-y: auto; padding: 1.5rem;
  display: flex; flex-direction: column; gap: 1rem;
  /* Dark sequence-like background */
  background: #0f0f1a;
  color: #e0e0e0;
  font-family: 'Inter', system-ui, sans-serif;
}

/* Clue card */
.cp-card {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 14px; padding: 1.5rem;
  position: relative; overflow: hidden;
}
.cp-accent-bar {
  position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
  background: linear-gradient(180deg, #6c63ff, #ff6b6b);
}
.cp-clue-label {
  font-size: .72rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: rgba(255,255,255,.35);
  margin-bottom: .6rem;
}
.cp-clue-title {
  font-size: 1.2rem; font-weight: 600; color: #f0f0f0;
  margin-bottom: .85rem; line-height: 1.3;
}
.cp-rich-content {
  font-size: .95rem; line-height: 1.75; color: #d0d0d0;
}
.cp-rich-content p  { margin-bottom: .75rem; }
.cp-rich-content strong { color: #fff; }
.cp-rich-content em { font-style: italic; }
.cp-rich-content blockquote {
  border-left: 3px solid #ff6b6b;
  padding: .6rem 1rem; margin: .75rem 0;
  background: rgba(255,255,255,.03);
  border-radius: 0 6px 6px 0;
  font-style: italic; color: rgba(255,255,255,.65);
}
.cp-rich-content ul, .cp-rich-content ol { padding-left: 1.4rem; margin-bottom: .75rem; }
.cp-rich-content a { color: #6c63ff; }

.cp-media-block { margin-top: 1rem; }
.cp-media-block:empty { display: none; }
.cp-media-block img {
  width: 100%; max-height: 340px; object-fit: contain;
  border-radius: 10px; background: rgba(0,0,0,.3);
}
.cp-media-block audio { width: 100%; }
.cp-media-block iframe { width: 100%; height: 320px; border: none; border-radius: 8px; }
.cp-media-block a {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .6rem 1rem; border-radius: 8px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  color: #d0d0d0; font-size: .875rem;
}

/* Hint */
.cp-hint-wrap { display: flex; flex-direction: column; gap: .5rem; }
.cp-hint-toggle { text-align: center; }
.cp-hint-btn {
  background: none; border: 1px solid rgba(255,215,0,.25);
  color: rgba(255,215,0,.7); padding: .4rem 1rem;
  border-radius: 20px; cursor: pointer;
  font-size: .85rem; font-family: inherit; transition: all .2s;
}
.cp-hint-btn:hover { background: rgba(255,215,0,.08); border-color: rgba(255,215,0,.45); }
.cp-hint-body {
  background: rgba(255,215,0,.04);
  border: 1px solid rgba(255,215,0,.12);
  border-radius: 10px; padding: 1rem;
}
.cp-hint-body.hidden { display: none; }

/* Locked gate */
.cp-gate {
  background: rgba(255,255,255,.03);
  border: 1px dashed rgba(255,255,255,.12);
  border-radius: 14px; padding: 1.5rem;
  text-align: center;
}
.cp-gate-icon { font-size: 1.75rem; margin-bottom: .5rem; }
.cp-gate-sub  { color: rgba(255,255,255,.45); font-size: .875rem; margin-bottom: 1rem; }
.cp-code-row  { display: flex; gap: .5rem; justify-content: center; }
.cp-code-input {
  padding: .6rem 1rem; border-radius: 8px;
  border: 1px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.06);
  color: #aaa; font-size: .9rem; text-align: center;
  letter-spacing: .06em; font-family: 'Courier New', monospace;
  width: 200px;
}
.cp-code-btn {
  padding: .6rem 1.25rem; border-radius: 8px; border: none;
  background: #6c63ff; color: #fff;
  font-size: .875rem; font-weight: 600; opacity: .5; cursor: not-allowed;
  font-family: inherit;
}
</style>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
const SEQ_ID      = <?= $seqId ?>;
const CSRF_TOKEN  = <?= json_encode(Security::generateCsrfToken()) ?>;
const ADD_URL     = <?= json_encode(url('admin/sequences/' . $seqId . '/clues')) ?>;
const EDIT_URL    = <?= json_encode(url('admin/clues/')) ?>;
const UPLOAD_BASE = <?= json_encode(UPLOAD_URL) ?>;

document.addEventListener('DOMContentLoaded', function () {
  window.clueQ   = initQuill('#clue-content-editor', '');
  window.rewardQ = initQuill('#clue-reward-editor', '');
  window.hintQ   = initQuill('#hint-content-editor', '');

  document.getElementById('clue-form').addEventListener('submit', function (e) {
    e.preventDefault();
    document.getElementById('clue-content-hidden').value = window.clueQ.root.innerHTML;
    document.getElementById('clue-reward-hidden').value  = window.rewardQ.root.innerHTML;
    document.getElementById('hint-content-hidden').value  = window.hintQ.root.innerHTML;

    const action = document.getElementById('clue-action').value;
    const clueId = document.getElementById('clue-id-field').value;
    const url    = action === 'edit' ? EDIT_URL + clueId + '/edit' : ADD_URL;

    fetch(url, { method: 'POST', body: new FormData(this) })
      .then(() => window.location.reload())
      .catch(() => window.location.reload());
  });

  Sortable.create(document.getElementById('clue-sortable'), {
    handle: '.clue-handle',
    animation: 150,
    onEnd: function () {
      const ids = [...document.querySelectorAll('.clue-item')].map(el => +el.dataset.id);
      fetch(<?= json_encode(url('admin/clues/reorder')) ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: CSRF_TOKEN, order: ids }),
      });
    }
  });

  setupLocalPreview('clue-file', 'clue-file-preview', 'clue-upload-area');
  setupLocalPreview('hint-file', 'hint-file-preview', 'hint-upload-area');
});

// â"€â"€ Clue preview modal â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€

function openCluePreview(btn) {
  const data = JSON.parse(btn.dataset.clue);

  // Label + title
  document.getElementById('cp-label').textContent = 'Clue ' + data.index;
  document.getElementById('cp-title').textContent = data.title || ('Clue ' + data.index);

  // Rich content
  document.getElementById('cp-content').innerHTML = data.content || '<em style="color:rgba(255,255,255,.3)">No content added yet.</em>';

  // Main media
  const mediaEl = document.getElementById('cp-media');
  mediaEl.innerHTML = data.file_path ? buildPublicMedia(data.file_type, UPLOAD_BASE + '/' + data.file_path, data.original_filename) : '';

  // Hint
  const hintWrap = document.getElementById('cp-hint-wrap');
  const hintBody = document.getElementById('cp-hint-body');
  const hintText = document.getElementById('cp-hint-text');
  const hintMedia = document.getElementById('cp-hint-media');

  if (data.hint_text || data.hint_file_path) {
    hintWrap.style.display = '';
    hintBody.classList.add('hidden'); // always start collapsed
    hintText.innerHTML  = data.hint_text || '';
    hintMedia.innerHTML = data.hint_file_path
      ? buildPublicMedia(data.hint_file_type, UPLOAD_BASE + '/' + data.hint_file_path, data.hint_original_filename)
      : '';
    // Reset hint button text
    hintWrap.querySelector('.cp-hint-btn').textContent = '💡 Need a hint?';
  } else {
    hintWrap.style.display = 'none';
  }

  document.getElementById('clue-preview-overlay').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeCluePreview() {
  document.getElementById('clue-preview-overlay').classList.add('hidden');
  document.body.style.overflow = '';
}

function handleOverlayClick(e) {
  if (e.target === document.getElementById('clue-preview-overlay')) closeCluePreview();
}

function togglePreviewHint(btn) {
  const body = document.getElementById('cp-hint-body');
  const open = body.classList.toggle('hidden') === false;
  btn.textContent = open ? '💡 Hide hint' : '💡 Need a hint?';
}

function buildPublicMedia(type, url, name) {
  if (type === 'image') return `<img src="${url}" alt="${name || ''}">`;
  if (type === 'audio') return `<audio controls preload="metadata"><source src="${url}"></audio>`;
  if (type === 'pdf')   return `<iframe src="${url}" title="${name || 'PDF'}"></iframe>`;
  return `<a href="${url}" target="_blank">📎 ${name || 'Download file'}</a>`;
}

// â"€â"€ Edit clue form â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€

function openEditClue(btn) {
  const id   = btn.dataset.clueId;
  const data = JSON.parse(btn.dataset.clue);

  document.getElementById('clue-form-title').textContent = 'Edit Clue';
  document.getElementById('clue-action').value = 'edit';
  document.getElementById('clue-id-field').value = id;
  document.getElementById('clue-title').value = data.title || '';
  document.getElementById('clue-code').value  = (data.access_code || '').toUpperCase();
  document.getElementById('clue-instruction').value = data.instruction || '';
  document.getElementById('clue-submit-btn').textContent = 'Save Clue';

  window.clueQ.root.innerHTML   = data.content || '';
  window.rewardQ.root.innerHTML = data.reward_content || '';
  window.hintQ.root.innerHTML   = data.hint_text || '';

  if (data.file_path) {
    const wrap = document.getElementById('clue-current-media');
    wrap.innerHTML = buildAdminMediaThumb(data.file_type, UPLOAD_BASE + '/' + data.file_path, data.original_filename)
      + ' <button type="button" class="btn btn-danger btn-xs" onclick="deleteClueMedia(\'clue\')">Remove</button>';
    wrap.classList.remove('hidden');
    document.getElementById('clue-upload-area').classList.add('hidden');
  }
  if (data.hint_file_path) {
    const wrap = document.getElementById('hint-current-media');
    wrap.innerHTML = buildAdminMediaThumb(data.hint_file_type, UPLOAD_BASE + '/' + data.hint_file_path, data.hint_original_filename)
      + ' <button type="button" class="btn btn-danger btn-xs" onclick="deleteClueMedia(\'hint\')">Remove</button>';
    wrap.classList.remove('hidden');
    document.getElementById('hint-upload-area').classList.add('hidden');
  }

  document.getElementById('clue-form-card').scrollIntoView({ behavior: 'smooth' });
}

function buildAdminMediaThumb(type, url, name) {
  if (type === 'image') return `<img src="${url}" class="media-thumb">`;
  if (type === 'audio') return `<audio controls src="${url}" class="media-audio"></audio>`;
  if (type === 'pdf')   return `<a href="${url}" target="_blank">📄 ${name}</a>`;
  return `<a href="${url}">📎 ${name}</a>`;
}

function deleteClueMedia(slot) {
  const clueId = document.getElementById('clue-id-field').value;
  if (!clueId) { return; }
  if (!confirm('Remove this file?')) { return; }
  const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
  fetch('/admin/media/delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ csrf_token: csrf, target: 'clue', target_id: clueId, media_slot: slot }),
  })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { alert(data.error || 'Delete failed.'); return; }
      const cur  = document.getElementById(slot === 'hint' ? 'hint-current-media' : 'clue-current-media');
      const area = document.getElementById(slot === 'hint' ? 'hint-upload-area'  : 'clue-upload-area');
      const inp  = document.getElementById(slot === 'hint' ? 'hint-file'         : 'clue-file');
      if (cur)  { cur.innerHTML = ''; cur.classList.add('hidden'); }
      if (inp)  { inp.value = ''; }
      if (area) { area.classList.remove('hidden'); }
    })
    .catch(() => alert('Delete failed.'));
}

function resetClueForm() {
  document.getElementById('clue-form-title').textContent = 'Add New Clue';
  document.getElementById('clue-action').value = 'add';
  document.getElementById('clue-id-field').value = '';
  document.getElementById('clue-title').value = '';
  document.getElementById('clue-code').value  = '';
  document.getElementById('clue-instruction').value = '';
  document.getElementById('clue-submit-btn').textContent = 'Add Clue';
  window.clueQ.root.innerHTML   = '';
  window.rewardQ.root.innerHTML = '';
  window.hintQ.root.innerHTML   = '';
  document.getElementById('clue-current-media').classList.add('hidden');
  document.getElementById('hint-current-media').classList.add('hidden');
  document.getElementById('clue-upload-area').classList.remove('hidden');
  document.getElementById('hint-upload-area').classList.remove('hidden');
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';

