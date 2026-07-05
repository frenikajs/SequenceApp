<?php
/** Interactive suspect-selection stage.
 * @var string $slug @var array $suspects @var int|null $mySuspectIndex
 * @var array $selTaken @var int $selAssigned @var int $selTotal
 * @var bool $isHost @var string $groupCode @var string|null $flashMsg @var string|null $flashType */
?>
<div class="suspect-select" id="suspect-select" data-slug="<?= e($slug) ?>" data-code="<?= e($groupCode) ?>">
  <div class="lobby-file-head"><span>🕵️ CHOOSE YOUR SUSPECT</span><span class="lobby-stamp">CONFIDENTIAL</span></div>
  <div class="ss-body">
    <h2>Who will you become?</h2>
    <p class="gate-sub">Each detective adopts a different suspect. Pick yours — once everyone has chosen, the host can enter the crime scene.</p>

    <?php if ($flashType === 'error'): ?>
    <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
    <?php endif; ?>

    <div class="ss-progress">
      <span id="ss-count"><?= (int)$selAssigned ?></span> of <span id="ss-total"><?= (int)$selTotal ?></span> suspects assigned
    </div>

    <div class="ss-grid" id="ss-grid">
      <?php foreach ($suspects as $i => $sus):
        $isMine  = ($mySuspectIndex !== null && (int)$mySuspectIndex === $i);
        $taken   = in_array($i, array_map('intval', $selTaken), true);
        $byOther = ($taken && !$isMine);
        $imgUrl  = !empty($sus['image']) ? htmlspecialchars(UPLOAD_URL . '/' . $sus['image'], ENT_QUOTES, 'UTF-8') : '';
      ?>
      <form method="POST" action="<?= url('s/' . $slug . '/suspect') ?>" class="ss-card-form">
        <?= csrf_field() ?>
        <input type="hidden" name="suspect" value="<?= $i ?>">
        <button type="submit"
                class="ss-card<?= $isMine ? ' ss-card-mine' : '' ?><?= $byOther ? ' ss-card-taken' : '' ?>"
                data-index="<?= $i ?>"<?= $byOther ? ' disabled' : '' ?>>
          <div class="ss-card-img">
            <?php if ($imgUrl !== ''): ?><img src="<?= $imgUrl ?>" alt="" loading="lazy"><?php else: ?><span class="ss-card-noimg">🔍</span><?php endif; ?>
          </div>
          <div class="ss-card-name"><?= e($sus['name'] ?? ('Suspect ' . ($i + 1))) ?></div>
          <?php if (!empty($sus['desc'])): ?><div class="ss-card-desc"><?= e($sus['desc']) ?></div><?php endif; ?>
          <div class="ss-card-status">
            <?php if ($isMine): ?><span class="ss-tag ss-tag-mine">✓ Your pick</span>
            <?php elseif ($byOther): ?><span class="ss-tag ss-tag-taken">Taken</span>
            <?php else: ?><span class="ss-tag ss-tag-open">Choose</span><?php endif; ?>
          </div>
        </button>
      </form>
      <?php endforeach; ?>
    </div>

    <?php if ($isHost): ?>
    <form method="POST" action="<?= url('s/' . $slug . '/crime-scene') ?>" class="ss-host-actions">
      <?= csrf_field() ?>
      <button type="submit" class="lobby-btn lobby-btn-start" id="ss-enter"
              <?= ($selTotal > 0 && $selAssigned === $selTotal) ? '' : 'disabled' ?>>
        🚪 Enter Crime Scene
      </button>
      <p class="lobby-note" id="ss-enter-note">
        <?= ($selTotal > 0 && $selAssigned === $selTotal)
            ? 'Everyone has a suspect — enter when ready.'
            : 'Waiting for all detectives to pick a suspect…' ?>
      </p>
    </form>
    <?php else: ?>
    <p class="lobby-note">Once you and everyone else have chosen, the host will lead you into the crime scene.</p>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var root = document.getElementById('suspect-select');
  if (!root) return;
  var slug = root.dataset.slug, code = root.dataset.code;
  var url  = <?= json_encode(url('s/')) ?> + slug + '/select/status?code=' + encodeURIComponent(code);
  var isHost = <?= $isHost ? 'true' : 'false' ?>;
  var myIndex = <?= $mySuspectIndex === null ? 'null' : (int)$mySuspectIndex ?>;

  var poll = setInterval(function () {
    fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.ok) return;
        if (d.status === 'active') { clearInterval(poll); location.reload(); return; }
        var c = document.getElementById('ss-count'); if (c) c.textContent = d.assigned;
        var t = document.getElementById('ss-total'); if (t) t.textContent = d.members;
        // Disable suspects taken by others (so two players can't grab the same one).
        var taken = d.taken || [];
        document.querySelectorAll('.ss-card').forEach(function (btn) {
          var idx = parseInt(btn.dataset.index, 10);
          if (idx === myIndex) return;
          var isTaken = taken.indexOf(idx) !== -1;
          btn.disabled = isTaken;
          btn.classList.toggle('ss-card-taken', isTaken);
          var tag = btn.querySelector('.ss-tag');
          if (tag && !btn.classList.contains('ss-card-mine')) {
            tag.className = 'ss-tag ' + (isTaken ? 'ss-tag-taken' : 'ss-tag-open');
            tag.textContent = isTaken ? 'Taken' : 'Choose';
          }
        });
        if (isHost) {
          var enter = document.getElementById('ss-enter');
          var note  = document.getElementById('ss-enter-note');
          var ready = (d.members > 0 && d.assigned === d.members);
          if (enter) enter.disabled = !ready;
          if (note)  note.textContent = ready ? 'Everyone has a suspect — enter when ready.' : 'Waiting for all detectives to pick a suspect…';
        }
      }).catch(function () {});
  }, 3000);
  window.addEventListener('pagehide', function () { clearInterval(poll); });
})();
</script>
