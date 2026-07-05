'use strict';

// ── Force access-code inputs to uppercase ─────────────────────────────────────

document.addEventListener('input', function (e) {
  var el = e.target;
  if (el && el.classList && el.classList.contains('code-upper')) {
    var s = el.selectionStart, en = el.selectionEnd;
    el.value = el.value.toUpperCase();
    try { el.setSelectionRange(s, en); } catch (_) {}
  }
});

// ── Modal (delete confirmation) ───────────────────────────────────────────────

let _pendingDeleteUrl = '';

function confirmDelete(url, message) {
  _pendingDeleteUrl = url;
  document.getElementById('modal-title').textContent = 'Confirm Delete';
  document.getElementById('modal-body').innerHTML = message;
  document.getElementById('modal-overlay').classList.remove('hidden');
  document.getElementById('modal-confirm').onclick = executeDelete;
}

function executeDelete() {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = _pendingDeleteUrl;
  const csrf = document.createElement('input');
  csrf.type = 'hidden';
  csrf.name = 'csrf_token';
  // Extract CSRF token from any visible hidden field on the page
  const existing = document.querySelector('input[name="csrf_token"]');
  csrf.value = existing ? existing.value : '';
  form.appendChild(csrf);
  document.body.appendChild(form);
  form.submit();
}

function closeModal() {
  document.getElementById('modal-overlay').classList.add('hidden');
}

document.getElementById('modal-overlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// ── Toast auto-dismiss ────────────────────────────────────────────────────────

const toast = document.getElementById('toast');
if (toast) {
  setTimeout(() => toast.style.opacity = '0', 4000);
  setTimeout(() => toast.remove(), 4500);
}

// ── Quill initializer ─────────────────────────────────────────────────────────

function initQuill(selector, content) {
  const el = document.querySelector(selector);
  if (!el) return null;
  const q = new Quill(el, {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        ['clean'],
      ],
    },
    placeholder: 'Write your content here…',
  });
  if (content) {
    q.root.innerHTML = content;
  }
  return q;
}

// ── Local file preview (before upload) ───────────────────────────────────────

function setupLocalPreview(inputId, previewId, areaId) {
  const input   = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  const area    = document.getElementById(areaId);
  if (!input || !preview) return;

  input.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const type = file.type;
    // Revoke the previous object URL before replacing it to avoid a blob leak.
    const prev = preview.querySelector('[data-objurl]');
    if (prev) URL.revokeObjectURL(prev.dataset.objurl);
    preview.innerHTML = '';
    preview.classList.remove('hidden');

    if (type.startsWith('image/')) {
      const img = document.createElement('img');
      img.style.cssText = 'max-width:100%;max-height:150px;border-radius:8px;object-fit:cover;';
      img.dataset.objurl = URL.createObjectURL(file);
      img.src = img.dataset.objurl;
      preview.appendChild(img);
    } else if (type.startsWith('audio/')) {
      const audio = document.createElement('audio');
      audio.controls = true;
      audio.style.width = '100%';
      audio.dataset.objurl = URL.createObjectURL(file);
      audio.src = audio.dataset.objurl;
      preview.appendChild(audio);
    } else {
      const p = document.createElement('p');
      p.style.cssText = 'font-size:.85rem;color:#9ca3af;padding:.5rem;';
      p.textContent = '📎 ' + file.name + ' (' + formatBytes(file.size) + ')';
      preview.appendChild(p);
    }
  });

  // Drag-and-drop highlighting
  if (area) {
    area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('drag-over'); });
    area.addEventListener('dragleave', () => area.classList.remove('drag-over'));
    area.addEventListener('drop', e => {
      e.preventDefault();
      area.classList.remove('drag-over');
      if (e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
      }
    });
  }
}

// ── AJAX media delete ─────────────────────────────────────────────────────────

function deleteMedia(target, targetId, slot, container) {
  if (!confirm('Remove this file?')) return;
  const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';

  fetch('/admin/media/delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ csrf_token: csrf, target, target_id: targetId, media_slot: slot }),
  })
    .then(r => r.json())
    .then(data => {
      if (data.success && container) {
        container.innerHTML = `
          <div class="upload-area" style="position:relative;border:2px dashed #374151;border-radius:10px;padding:1.5rem;text-align:center;">
            <div class="upload-placeholder" style="color:#6b7280;">📁 Upload a new file</div>
            <input type="file" name="${slot}_file" class="upload-input" style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                   accept=".png,.jpg,.jpeg,.pdf,.mp3,.wav,.ogg">
          </div>`;
      }
    })
    .catch(() => alert('Delete failed.'));
}

// ── Color pickers sync ────────────────────────────────────────────────────────

document.querySelectorAll('.color-swatch').forEach(swatch => {
  const hex = document.querySelector(`.color-hex[data-for="${swatch.id}"]`);
  if (!hex) return;
  swatch.addEventListener('input', () => { hex.value = swatch.value; });
  hex.addEventListener('input', () => {
    if (/^#[0-9a-f]{6}$/i.test(hex.value)) swatch.value = hex.value;
  });
});

// ── Utility ───────────────────────────────────────────────────────────────────

function formatBytes(bytes) {
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  while (bytes >= 1024 && i < 3) { bytes /= 1024; i++; }
  return Math.round(bytes * 10) / 10 + ' ' + units[i];
}

// ── Autosave / draft recovery ─────────────────────────────────────────────────
// Any <form data-autosave="key"> is snapshotted to localStorage as the user
// edits (named fields + Quill editor HTML). On reload, if a newer draft than the
// rendered content exists, a restore bar offers to bring it back. The draft is
// cleared once the form is submitted successfully.

(function () {
  'use strict';
  var DEBOUNCE   = 600;   // ms after last edit before saving
  var READY_DELAY = 1000; // ms after load before we start saving (skips Quill-init noise)

  function fmtWhen(ts) {
    try {
      var d = new Date(ts);
      if (isNaN(d.getTime())) return '';
      return d.toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    } catch (_) { return ''; }
  }

  function setupAutosave(form) {
    var key    = 'draft:' + form.getAttribute('data-autosave');
    var ready  = false;
    var timer  = null;

    function fields() {
      return form.querySelectorAll('input[name], textarea[name], select[name]');
    }

    function quillBoxes() {
      return form.querySelectorAll('.quill-editor[id]');
    }

    function skip(el) {
      var t = (el.type || '').toLowerCase();
      return !el.name || el.name === 'csrf_token' ||
             t === 'file' || t === 'submit' || t === 'button' || t === 'hidden';
    }

    function snapshot() {
      var data = { f: {}, q: {}, ts: Date.now() };
      fields().forEach(function (el) {
        if (skip(el)) return;
        if (el.type === 'checkbox') {
          data.f[el.name] = el.checked ? '1' : '';
        } else if (el.type === 'radio') {
          if (el.checked) data.f[el.name] = el.value;
        } else {
          data.f[el.name] = el.value;
        }
      });
      quillBoxes().forEach(function (box) {
        var ed = box.querySelector('.ql-editor');
        if (ed) data.q[box.id] = ed.innerHTML;
      });
      return data;
    }

    function hasContent(data) {
      var k;
      for (k in data.f) { if (data.f[k] && String(data.f[k]).trim() !== '') return true; }
      for (k in data.q) {
        var v = (data.q[k] || '').replace(/<(p|br)[^>]*>/gi, '').replace(/<\/p>/gi, '').trim();
        if (v !== '' && v !== '<p></p>') return true;
      }
      return false;
    }

    function save() {
      if (!ready) return;
      var data = snapshot();
      if (!hasContent(data)) { return; }
      try { localStorage.setItem(key, JSON.stringify(data)); } catch (_) {}
    }

    function clearDraft() {
      try { localStorage.removeItem(key); } catch (_) {}
    }

    function scheduleSave() {
      if (timer) clearTimeout(timer);
      timer = setTimeout(save, DEBOUNCE);
    }

    function applyDraft(data) {
      fields().forEach(function (el) {
        if (skip(el)) return;
        if (el.type === 'checkbox') {
          if (el.name in data.f) el.checked = !!data.f[el.name];
        } else if (el.type === 'radio') {
          if (el.name in data.f) el.checked = (data.f[el.name] === el.value);
        } else if (el.name in data.f) {
          el.value = data.f[el.name];
          el.dispatchEvent(new Event('input', { bubbles: true }));
          el.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
      Object.keys(data.q || {}).forEach(function (id) {
        var box = document.getElementById(id);
        if (!box) return;
        var ed = box.querySelector('.ql-editor');
        if (ed) ed.innerHTML = data.q[id];
      });
      if (typeof pzTypeToggle === 'function') { try { pzTypeToggle(); } catch (_) {} }
    }

    function showBar(data) {
      var when = fmtWhen(data.ts);
      var bar = document.createElement('div');
      bar.className = 'as-bar';
      bar.innerHTML =
        '<span class="as-msg">💾 Unsaved draft found' +
        (when ? ' from <strong>' + when + '</strong>' : '') +
        '. Restore it?</span>' +
        '<span class="as-act">' +
        '<button type="button" class="btn btn-sm btn-primary as-yes">Restore</button>' +
        '<button type="button" class="btn btn-sm btn-ghost as-no">Discard</button>' +
        '</span>';
      form.insertBefore(bar, form.firstChild);
      bar.querySelector('.as-yes').addEventListener('click', function () {
        applyDraft(data);
        bar.remove();
      });
      bar.querySelector('.as-no').addEventListener('click', function () {
        clearDraft();
        bar.remove();
      });
    }

    // Offer restore if a draft already exists.
    var raw = null;
    try { raw = localStorage.getItem(key); } catch (_) {}
    if (raw) {
      try {
        var saved = JSON.parse(raw);
        if (saved && hasContent(saved)) showBar(saved);
        else clearDraft();
      } catch (_) { clearDraft(); }
    }

    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);
    form.addEventListener('submit', function () {
      ready = false;
      if (timer) clearTimeout(timer);
      clearDraft();
    });

    // Wait out the initial Quill render before arming, so a fresh page load
    // with default content doesn't create a phantom draft.
    setTimeout(function () { ready = true; }, READY_DELAY);
  }

  document.querySelectorAll('form[data-autosave]').forEach(setupAutosave);
})();
