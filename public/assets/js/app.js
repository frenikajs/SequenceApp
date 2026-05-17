'use strict';

// ── Hint toggle ───────────────────────────────────────────────────────────────

function toggleHint(btn) {
  const container = btn.closest('.hint-toggle');
  const content   = container.querySelector('.hint-content');
  const isHidden  = content.classList.contains('hidden');
  content.classList.toggle('hidden', !isHidden);
  btn.textContent = isHidden ? '💡 Hide hint' : '💡 Need a hint?';
}

// ── Lightbox ──────────────────────────────────────────────────────────────────

function openLightbox(src) {
  const lb  = document.getElementById('lightbox');
  const img = document.getElementById('lightbox-img');
  img.src = src;
  lb.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  document.getElementById('lightbox').classList.add('hidden');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeLightbox();
});

// ── Notification toast ────────────────────────────────────────────────────────

function showNotification(message, type = 'success') {
  const existing = document.querySelector('.notif-toast');
  if (existing) existing.remove();

  const el = document.createElement('div');
  el.className = `notif-toast notif-${type}`;
  el.textContent = message;
  document.body.appendChild(el);

  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transition = 'opacity .4s';
    setTimeout(() => el.remove(), 400);
  }, 3000);
}

// ── Code input: auto-uppercase ────────────────────────────────────────────────

document.querySelectorAll('.code-input').forEach(input => {
  input.addEventListener('input', function() {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });
});

// ── Smooth scroll to next gate after page load ────────────────────────────────

window.addEventListener('load', () => {
  const gate = document.getElementById('next-gate');
  if (gate) {
    setTimeout(() => gate.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
  }
});

// ── Flash alert auto-dismiss ──────────────────────────────────────────────────

const flash = document.getElementById('seq-flash');
if (flash) {
  setTimeout(() => {
    flash.style.transition = 'opacity .5s';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
  }, 5000);
}
