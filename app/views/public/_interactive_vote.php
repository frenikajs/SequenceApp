<?php
/** Interactive group accusation vote.
 * @var string $slug @var string $groupCode @var array $suspects
 * @var int|null $myVote @var int $voteVoted @var int $voteTotal
 * @var int|null $voteResultIndex @var int $accuseRound @var bool $isHost
 * @var bool $voteClosed @var string $voteOutcome @var string|null $flashMsg @var string|null $flashType */
$voteClosed = $voteClosed ?? ($voteTotal > 0 && $voteVoted === $voteTotal);
$groupPick  = ($voteResultIndex !== null && !empty($suspects[$voteResultIndex])) ? $suspects[$voteResultIndex] : null;
?>
<section class="ix-vote" id="ix-vote" data-slug="<?= e($slug) ?>" data-code="<?= e($groupCode) ?>" data-round="<?= (int)$accuseRound ?>">
  <h3 class="wd-cat-title">📁 Accusation</h3>

  <?php if ($voteClosed && $voteOutcome === 'pending'): ?>
  <!-- The vote is closed (all voted, or the host forced it) but the pick was wrong -->
  <div class="gate-card ix-vote-fail">
    <div class="gate-icon">🚔</div>
    <h2>The culprit got away!</h2>
    <?php if ($groupPick): ?>
    <p class="gate-sub">Your group accused <strong><?= e($groupPick['name'] ?? '') ?></strong> — but that&rsquo;s not who did it.</p>
    <?php else: ?>
    <p class="gate-sub">Your group&rsquo;s accusation was wrong.</p>
    <?php endif; ?>
    <?php if ($isHost): ?>
    <div class="ix-fail-actions">
      <form method="POST" action="<?= url('s/' . $slug . '/accuse-again') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn-primary code-submit">🔁 Accuse Again</button>
      </form>
      <form method="POST" action="<?= url('s/' . $slug . '/reveal') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn-reset">👁 Reveal Mystery</button>
      </form>
    </div>
    <p class="lobby-note">Give the team another shot, or reveal who really did it.</p>
    <?php else: ?>
    <p class="lobby-note">Waiting for the host to decide — accuse again or reveal the mystery…</p>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- Voting is open -->
  <div class="gate-card accusation-gate">
    <div class="gate-icon">🕵️</div>
    <h2>Name the Culprit</h2>
    <p class="gate-sub ix-vote-count"><span id="ix-voted"><?= (int)$voteVoted ?></span> of <span id="ix-total"><?= (int)$voteTotal ?></span> detectives have voted</p>

    <?php if ($myVote !== null): ?>
    <p class="gate-sub">You accused <strong><?= e($suspects[$myVote]['name'] ?? '') ?></strong>. Standing by for the rest of your team&hellip;</p>
    <div class="lobby-spinner" aria-hidden="true"></div>
    <?php else: ?>
      <?php if ($flashType === 'error'): ?>
      <div class="code-error" role="alert"><span class="alert-icon">⚠</span> <?= e($flashMsg) ?></div>
      <?php endif; ?>
      <form method="POST" action="<?= url('s/' . $slug . '/vote') ?>" class="ix-vote-form">
        <?= csrf_field() ?>
        <div class="ix-vote-options">
          <?php foreach ($suspects as $i => $sus): ?>
          <label class="ix-vote-option">
            <input type="radio" name="vote" value="<?= $i ?>" required>
            <span class="ix-vote-name"><?= e($sus['name'] ?? ('Suspect ' . ($i + 1))) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="btn-primary code-submit">⚖️ Lock In Accusation</button>
      </form>
    <?php endif; ?>

    <?php if ($isHost): ?>
    <!-- Host can tally the vote early instead of waiting for stragglers. -->
    <form method="POST" action="<?= url('s/' . $slug . '/force-vote') ?>" class="ix-force-form"
          onsubmit="return confirm('Tally the vote now with the accusations cast so far? Anyone who hasn\'t voted will be left out of this round.');">
      <?= csrf_field() ?>
      <button type="submit" class="btn-reset"<?= $voteVoted < 1 ? ' disabled' : '' ?>>⏱ Tally Votes Now</button>
      <p class="lobby-note">As host you can close the vote early — the group's pick is decided from the accusations cast so far.</p>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>

<script>
(function () {
  var root = document.getElementById('ix-vote');
  if (!root) return;
  var slug = root.dataset.slug, code = root.dataset.code;
  var round = parseInt(root.dataset.round, 10) || 1;
  var wasClosed = <?= $voteClosed ? 'true' : 'false' ?>;
  var url = <?= json_encode(url('s/')) ?> + slug + '/vote/status?code=' + encodeURIComponent(code);
  var poll = setInterval(function () {
    fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.ok) return;
        // Solution revealed (solved or host revealed) → show the outcome.
        if (d.solved) { clearInterval(poll); location.reload(); return; }
        // A new round was opened (host chose "accuse again") → reset the panel.
        if (d.round !== round) { clearInterval(poll); location.reload(); return; }
        var v = document.getElementById('ix-voted'); if (v) v.textContent = d.voted;
        var t = document.getElementById('ix-total'); if (t) t.textContent = d.members;
        // Vote just closed (everyone voted, or the host forced it) → show the result.
        if (d.closed && !wasClosed) { clearInterval(poll); location.reload(); return; }
      }).catch(function () {});
  }, 3000);
  window.addEventListener('pagehide', function () { clearInterval(poll); });
})();
</script>
