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

// Hint-usage tally (per mystery), persisted across the page reloads between gates.
function hintKey() { return 'seqHints:' + ((document.body && document.body.dataset.seq) || ''); }
var hintsOpenedThisPage = {};
function recordHint(id) {
  if (!id || hintsOpenedThisPage[id]) { return; } // count each hint once per page
  hintsOpenedThisPage[id] = true;
  try {
    var n = parseInt(localStorage.getItem(hintKey()) || '0', 10) || 0;
    localStorage.setItem(hintKey(), String(n + 1));
  } catch (e) {}
}
// At the start of a fresh playthrough (the not-started gate) wipe per-sequence
// scratch state: the hint tally and the whodunit detective's notepad marks.
(function () {
  if (!document.body || document.body.dataset.started !== '0') { return; }
  var slug = document.body.dataset.seq || '';
  try { localStorage.removeItem(hintKey()); } catch (e) {}
  try { localStorage.removeItem('seqgrid:' + slug); } catch (e) {}
})();

function toggleHint(btn) {
  const container = btn.closest('.hint-toggle');
  const content   = container.querySelector('.hint-content');
  const isHidden  = content.classList.contains('hidden');
  content.classList.toggle('hidden', !isHidden);
  btn.textContent = isHidden ? '💡 Hide hint' : '💡 Need a hint?';
  btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
  if (isHidden) { recordHint(content.id || (btn.getAttribute('aria-controls') || '')); } // opened
  // Group host: broadcast this hint to the whole group (open → token, close → clear).
  broadcastGroupHint(isHidden ? (btn.getAttribute('data-hint-token') || '') : '');
}

// Group host only: tell the server which hint (if any) is currently revealed so
// joiners can show the same one. No-op for solo players and joiners.
function broadcastGroupHint(token) {
  var cfg = window.SEQ_GROUP_HINT;
  if (!cfg || !cfg.url) { return; }
  var body = new URLSearchParams();
  body.set('csrf_token', cfg.csrf || '');
  body.set('token', token || '');
  fetch(cfg.url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body: body.toString()
  }).catch(function () {});
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

// Post-load focus (freshly-unlocked clue, or the next code box) is handled inline
// in the sequence page so it can't be overridden by a stale cached copy of this file.

// ── Flash alert auto-dismiss ──────────────────────────────────────────────────

const flash = document.getElementById('seq-flash');
if (flash) {
  setTimeout(() => {
    flash.style.transition = 'opacity .5s';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
  }, 5000);
}

// ── Sound & ambiance (Web Audio, synthesized — no audio files) ─────────────────

(function () {
  var AC = window.AudioContext || window.webkitAudioContext;
  if (!AC) { return; } // unsupported → no sound, no control

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function pref(key, def) { var v = localStorage.getItem(key); return v === null ? def : v === '1'; }
  var fxOn  = pref('seqFx', !reduced);  // effects default on (off if reduced-motion)
  var ambOn = pref('seqAmb', false);    // ambient music default off

  var ctx = null, master = null, pending = [], armed = false, amb = null;

  function getCtx() {
    if (!ctx) {
      ctx = new AC();
      master = ctx.createGain();
      master.gain.value = 0.9;
      master.connect(ctx.destination);
    }
    if (ctx.state === 'suspended') { ctx.resume(); }
    return ctx;
  }
  function arm() {
    if (armed) { return; }
    armed = true;
    function fire() {
      document.removeEventListener('pointerdown', fire, true);
      document.removeEventListener('keydown', fire, true);
      armed = false;
      getCtx();
      var q = pending; pending = [];
      q.forEach(function (fn) { fn(); });
      if (ambOn) { ensureAmbient(); }
    }
    document.addEventListener('pointerdown', fire, true);
    document.addEventListener('keydown', fire, true);
  }
  function run(fn) {
    var c = getCtx();
    if (!c) { return; }
    if (c.state === 'running') { fn(c); return; }
    // Suspended: try to resume right now. On an already-engaged page this
    // resolves immediately (no extra click needed) so the sound is in sync;
    // otherwise fall back to playing on the next user gesture.
    var p;
    try { p = c.resume(); } catch (e) { p = null; }
    function deferToGesture() { pending.push(function () { fn(ctx); }); arm(); }
    if (p && p.then) {
      p.then(function () { c.state === 'running' ? fn(c) : deferToGesture(); }, deferToGesture);
    } else {
      c.state === 'running' ? fn(c) : deferToGesture();
    }
  }
  function tone(c, freq, t0, dur, type, peak) {
    var o = c.createOscillator(), g = c.createGain();
    o.type = type || 'sine'; o.frequency.value = freq;
    o.connect(g); g.connect(master);
    var t = c.currentTime + t0;
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(peak || 0.18, t + 0.015);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    o.start(t); o.stop(t + dur + 0.05);
  }

  function chime() {
    if (!fxOn) { return; }
    run(function (c) {
      tone(c, 659, 0,   0.18, 'triangle', 0.18);
      tone(c, 988, 0.09, 0.22, 'triangle', 0.16);
      tone(c, 1319, 0.18, 0.32, 'triangle', 0.14);
    });
  }
  function buzz() {
    if (!fxOn) { return; }
    run(function (c) {
      tone(c, 165, 0,    0.18, 'sawtooth', 0.14);
      tone(c, 110, 0.13, 0.24, 'sawtooth', 0.14);
    });
  }
  function fanfare() {
    if (!fxOn) { return; }
    run(function (c) {
      var notes = [523, 659, 784, 1047, 1047];
      var t     = [0,   0.13, 0.26, 0.42, 0.66];
      var d     = [0.22, 0.22, 0.22, 0.55, 0.7];
      for (var i = 0; i < notes.length; i++) {
        tone(c, notes[i], t[i], d[i], 'square', 0.16);
        tone(c, notes[i] / 2, t[i], d[i], 'triangle', 0.09);
      }
    });
  }

  function ensureAmbient() { if (!amb && ambOn) { run(buildAmbient); } }
  function buildAmbient(c) {
    if (amb) { return; }
    var g = c.createGain(); g.gain.value = 0.0001; g.connect(master);
    var lp = c.createBiquadFilter(); lp.type = 'lowpass'; lp.frequency.value = 480; lp.Q.value = 0.7; lp.connect(g);
    var freqs = [55, 110, 110.6, 164.81], oscs = [];
    freqs.forEach(function (f) {
      var o = c.createOscillator(); o.type = 'sine'; o.frequency.value = f; o.connect(lp); o.start(); oscs.push(o);
    });
    var lfo = c.createOscillator(), lg = c.createGain();
    lfo.frequency.value = 0.06; lg.gain.value = 0.018; lfo.connect(lg); lg.connect(g.gain); lfo.start();
    g.gain.setTargetAtTime(0.05, c.currentTime, 3);
    amb = { g: g, oscs: oscs, lfo: lfo };
  }
  function stopAmbient() {
    if (!amb) { return; }
    var a = amb; amb = null;
    try { a.g.gain.setTargetAtTime(0.0001, ctx.currentTime, 0.8); } catch (e) {}
    setTimeout(function () {
      a.oscs.forEach(function (o) { try { o.stop(); } catch (e) {} });
      try { a.lfo.stop(); } catch (e) {}
    }, 1600);
  }

  window.Sound = { chime: chime, buzz: buzz, fanfare: fanfare };

  // ── Floating control: effects mute + ambient toggle ──
  function buildControl() {
    var ctrl = document.createElement('div');
    ctrl.className = 'snd-ctrl';
    var fxBtn = document.createElement('button');
    var ambBtn = document.createElement('button');
    fxBtn.type = 'button'; fxBtn.className = 'snd-btn';
    ambBtn.type = 'button'; ambBtn.className = 'snd-btn';
    function render() {
      fxBtn.textContent = fxOn ? '🔊' : '🔇';
      fxBtn.classList.toggle('off', !fxOn);
      fxBtn.setAttribute('aria-pressed', fxOn ? 'true' : 'false');
      fxBtn.setAttribute('aria-label', fxOn ? 'Mute sound effects' : 'Unmute sound effects');
      fxBtn.title = fxOn ? 'Sound effects: on' : 'Sound effects: off';
      ambBtn.textContent = '🎵';
      ambBtn.classList.toggle('off', !ambOn);
      ambBtn.setAttribute('aria-pressed', ambOn ? 'true' : 'false');
      ambBtn.setAttribute('aria-label', ambOn ? 'Turn off ambient music' : 'Turn on ambient music');
      ambBtn.title = ambOn ? 'Ambient music: on' : 'Ambient music: off';
    }
    fxBtn.addEventListener('click', function () {
      fxOn = !fxOn; localStorage.setItem('seqFx', fxOn ? '1' : '0'); render();
      if (fxOn) { getCtx(); chime(); }
    });
    ambBtn.addEventListener('click', function () {
      ambOn = !ambOn; localStorage.setItem('seqAmb', ambOn ? '1' : '0'); render();
      if (ambOn) { getCtx(); ensureAmbient(); } else { stopAmbient(); }
    });
    render();
    ctrl.appendChild(fxBtn); ctrl.appendChild(ambBtn);
    document.body.appendChild(ctrl);
    if (ambOn) { arm(); } // resume ambient on the first user gesture
  }
  if (document.body) { buildControl(); }
  else { document.addEventListener('DOMContentLoaded', buildControl); }
})();
