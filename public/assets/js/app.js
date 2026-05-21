'use strict';

// ── Accessibility helpers ─────────────────────────────────────────────────────

// Announce a message to assistive tech via a polite/assertive live region.
function announce(message, assertive) {
  var id = assertive ? 'a11y-live-assertive' : 'a11y-live-polite';
  var region = document.getElementById(id);
  if (!region) {
    region = document.createElement('div');
    region.id = id;
    region.className = 'sr-only';
    region.setAttribute('aria-live', assertive ? 'assertive' : 'polite');
    region.setAttribute('aria-atomic', 'true');
    document.body.appendChild(region);
  }
  region.textContent = '';
  // Re-set on next tick so identical consecutive messages still announce.
  setTimeout(function () { region.textContent = message; }, 60);
}
window.announce = announce;

// Keep Tab focus inside a container (call from a keydown handler).
function trapFocus(container, e) {
  if (e.key !== 'Tab') return;
  var sel = 'a[href], button:not([disabled]), input:not([disabled]), ' +
            'select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
  var nodes = Array.prototype.slice.call(container.querySelectorAll(sel))
    .filter(function (el) { return el.offsetParent !== null || el === document.activeElement; });
  if (!nodes.length) return;
  var first = nodes[0], last = nodes[nodes.length - 1];
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
}
window.trapFocus = trapFocus;

// ── Force access-code inputs to uppercase ─────────────────────────────────────

document.addEventListener('input', function (e) {
  var el = e.target;
  if (el && el.classList && el.classList.contains('code-input')) {
    var s = el.selectionStart, en = el.selectionEnd;
    el.value = el.value.toUpperCase();
    try { el.setSelectionRange(s, en); } catch (_) {}
  }
});

// ── Hint toggle ───────────────────────────────────────────────────────────────

function toggleHint(btn) {
  const container = btn.closest('.hint-toggle');
  const content   = container.querySelector('.hint-content');
  const isHidden  = content.classList.contains('hidden');
  content.classList.toggle('hidden', !isHidden);
  btn.textContent = isHidden ? '💡 Hide hint' : '💡 Need a hint?';
  btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
}

// ── Lightbox ──────────────────────────────────────────────────────────────────

let lightboxReturnFocus = null;

function openLightbox(src) {
  const lb  = document.getElementById('lightbox');
  const img = document.getElementById('lightbox-img');
  img.src = src;
  lb.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  lightboxReturnFocus = document.activeElement;
  const closeBtn = lb.querySelector('.lightbox-close');
  if (closeBtn) closeBtn.focus();
}

function closeLightbox() {
  const lb = document.getElementById('lightbox');
  if (!lb || lb.classList.contains('hidden')) return;
  lb.classList.add('hidden');
  document.body.style.overflow = '';
  // Return focus to whatever opened the lightbox.
  if (lightboxReturnFocus && typeof lightboxReturnFocus.focus === 'function') {
    lightboxReturnFocus.focus();
  }
  lightboxReturnFocus = null;
}

document.addEventListener('keydown', e => {
  const lb = document.getElementById('lightbox');
  if (!lb || lb.classList.contains('hidden')) return;
  if (e.key === 'Escape') closeLightbox();
  else trapFocus(lb, e);
});

// ── Notification toast ────────────────────────────────────────────────────────

function showNotification(message, type = 'success') {
  const existing = document.querySelector('.notif-toast');
  if (existing) existing.remove();

  const el = document.createElement('div');
  el.className = `notif-toast notif-${type}`;
  el.textContent = message;
  document.body.appendChild(el);

  // Mirror the toast to assistive tech.
  announce(message, type === 'error');

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
