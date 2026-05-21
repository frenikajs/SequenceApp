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
    preview.innerHTML = '';
    preview.classList.remove('hidden');

    if (type.startsWith('image/')) {
      const img = document.createElement('img');
      img.style.cssText = 'max-width:100%;max-height:150px;border-radius:8px;object-fit:cover;';
      img.src = URL.createObjectURL(file);
      preview.appendChild(img);
    } else if (type.startsWith('audio/')) {
      const audio = document.createElement('audio');
      audio.controls = true;
      audio.style.width = '100%';
      audio.src = URL.createObjectURL(file);
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
