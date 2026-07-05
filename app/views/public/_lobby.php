<?php
/** Group-play lobby. @var string $lobby  @var string $slug @var bool $isHost
 *  @var string $groupCode @var int $groupMembers @var string|null $flashMsg @var string|null $flashType */
?>
<?php if ($lobby === 'choose'): ?>
<div class="lobby-file">
  <div class="lobby-file-head"><span>🗂️ CASE BRIEFING</span><span class="lobby-stamp">CONFIDENTIAL</span></div>
  <div class="lobby-file-body">
    <h2>Start the Investigation</h2>
    <?php if ($flashType === 'error'): ?>
    <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
    <?php endif; ?>

    <div class="lobby-actions">
      <div class="lobby-tip" data-tip="Playing with others? Start here — your group shares clues and solves the case together.">
        <form method="POST" action="<?= url('s/' . $slug . '/group/create') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="lobby-btn">🤝 Start Group Investigation</button>
        </form>
      </div>
      <div class="lobby-tip" data-tip="Flying solo? Start here to work the case alone at your own pace.">
        <form method="POST" action="<?= url('s/' . $slug . '/mode/solo') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="lobby-btn lobby-btn-ghost">🕵️ Start Solo Investigation</button>
        </form>
      </div>
    </div>

    <details class="lobby-help">
      <summary class="lobby-help-toggle">Need help choosing?</summary>
      <div class="lobby-help-body">
        <p><strong>🤝 Group Investigation</strong> — You&rsquo;re playing with others. Everyone joins the same case and shares the experience together.</p>
        <p><strong>🕵️ Solo Investigation</strong> — You&rsquo;re on your own. Work through the case at your own pace.</p>
      </div>
    </details>

    <div class="lobby-join">
      <div class="lobby-or">— or join a group —</div>
      <form method="POST" action="<?= url('s/' . $slug . '/group/join') ?>" class="code-form">
        <?= csrf_field() ?>
        <div class="code-input-wrap">
          <input type="text" name="group_code" class="code-input" placeholder="GROUP CODE"
                 autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" required>
          <button type="submit" class="lobby-btn lobby-btn-join">Join</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php elseif ($lobby === 'host'): ?>
<div class="lobby-file lobby-file-host">
  <div class="lobby-file-head"><span>🗂️ GROUP INVESTIGATION</span><span class="lobby-stamp">CONFIDENTIAL</span></div>
  <div class="lobby-file-body">
    <h2>Assemble Your Team</h2>
    <p class="gate-sub">Read this case number to your group so they can join — tap it to copy:</p>
    <div class="lobby-code" id="lobby-code" role="button" tabindex="0" title="Click to copy"
         onclick="copyGroupCode()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();copyGroupCode();}">
      <?= e($groupCode) ?>
      <span class="lobby-copied" id="lobby-copied" aria-hidden="true">Copied!</span>
    </div>
    <button type="button" class="lobby-share" id="lobby-share" onclick="shareGroupCode()">📤 Share Code</button>
    <p class="lobby-members">👥 <span id="lobby-members"><?= (int)$groupMembers ?></span> detective(s) in the group</p>
    <form method="POST" action="<?= url('s/' . $slug . '/group/start') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="lobby-btn lobby-btn-start">▶ Start Investigation</button>
    </form>
    <p class="lobby-note">⚠ Don&rsquo;t start until everyone has joined — once you begin, the case is sealed and no one else can join.</p>
  </div>
</div>
<script>
(function () {
  var url = <?= json_encode(url('s/' . $slug . '/group/status') . '?code=' . urlencode($groupCode)) ?>;
  var el = document.getElementById('lobby-members');
  var _hostPoll = setInterval(function () {
    fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.ok) return;
        if (el) el.textContent = d.members;
        if (d.status === 'active') { clearInterval(_hostPoll); location.reload(); }
      }).catch(function () {});
  }, 3000);
  window.addEventListener('pagehide', function () { clearInterval(_hostPoll); });
})();

// Tap the case number to copy it; flash "Copied!" over it.
window.copyGroupCode = function () {
  var code = <?= json_encode($groupCode) ?>;
  function flash() {
    var t = document.getElementById('lobby-copied');
    if (!t) return;
    t.classList.add('show');
    setTimeout(function () { t.classList.remove('show'); }, 1200);
  }
  function fallback() {
    var ta = document.createElement('textarea');
    ta.value = code; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.focus(); ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta); flash();
  }
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(flash).catch(fallback);
  } else { fallback(); }
};

// Share the join link + code via the native share sheet (text/email/etc).
window.shareGroupCode = function () {
  var link = <?= json_encode(rtrim(url(''), '/')) ?>;
  var code = <?= json_encode($groupCode) ?>;
  var msg  = 'Join the group investigation at ' + link + ' use Group Code: ' + code;
  if (navigator.share) {
    navigator.share({ title: 'Group Investigation', text: msg }).catch(function () {});
    return;
  }
  // Desktop fallback: copy the message, flashing the button; else open an email draft.
  var btn = document.getElementById('lobby-share');
  function flashBtn(label) {
    if (!btn) return;
    var orig = btn.textContent;
    btn.textContent = label;
    setTimeout(function () { btn.textContent = orig; }, 1600);
  }
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(msg).then(function () { flashBtn('✓ Copied invite!'); })
      .catch(function () { window.location.href = 'mailto:?subject=Group%20Investigation&body=' + encodeURIComponent(msg); });
  } else {
    window.location.href = 'mailto:?subject=Group%20Investigation&body=' + encodeURIComponent(msg);
  }
};
</script>

<?php elseif ($lobby === 'join'): ?>
<div class="lobby-file">
  <div class="lobby-file-head"><span>🗂️ GROUP INVESTIGATION</span><span class="lobby-stamp">ON THE CASE</span></div>
  <div class="lobby-file-body">
    <h2>You&rsquo;re on the Team</h2>
    <p class="gate-sub">Joined case <strong class="lobby-code-inline"><?= e($groupCode) ?></strong></p>
    <div class="lobby-spinner" aria-hidden="true"></div>
    <p class="lobby-note">Standing by for the lead detective to open the case&hellip;</p>
  </div>
</div>
<script>
(function () {
  var url = <?= json_encode(url('s/' . $slug . '/group/status') . '?code=' . urlencode($groupCode)) ?>;
  var timer = setInterval(function () {
    fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (d) {
        // Reload when the host moves the group out of the lobby — Interactive goes
        // to the suspect-selection stage ('select'); other types go straight to play.
        if (d && d.ok && d.status !== 'lobby') { clearInterval(timer); location.reload(); }
      }).catch(function () {});
  }, 2500);
})();
</script>
<?php endif; ?>
