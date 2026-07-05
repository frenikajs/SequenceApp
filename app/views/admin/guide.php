<?php
$pageTitle = 'How to Play Guide';
$activeNav = 'guide';
ob_start();
$guideContent = $guide ?? '';
$guideType    = $guideType ?? 'sequential';
$guideTypes   = $guideTypes ?? ['sequential' => 'Sequential'];
?>

<div class="breadcrumb">
  <a href="<?= url('admin') ?>">Dashboard</a> /
  <span>How to Play Guide</span>
</div>

<p class="small muted" style="margin-bottom:1rem">
  Players see this guide when they click <strong>How to Play</strong> on a sequence page.
  Each sequence type has its own guide &mdash; pick a type below to edit it. Players are
  shown only the guide that matches the sequence they&rsquo;re playing.
  <a href="<?= url('how-to-play') ?>?type=<?= e($guideType) ?>" target="_blank" rel="noopener">Preview &#8599;</a>
</p>

<div class="card" style="margin-bottom:1rem">
  <div class="card-body">
    <div class="form-group" style="margin:0">
      <label for="guide-type-select">Sequence Type</label>
      <select id="guide-type-select" class="form-input" onchange="switchGuideType(this.value)">
        <?php foreach ($guideTypes as $val => $label): ?>
        <option value="<?= e($val) ?>" <?= $guideType === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <small class="muted">Switching type loads that guide. Save before switching or unsaved edits are lost.</small>
    </div>
  </div>
</div>

<form method="POST" action="<?= url('admin/guide') ?>" id="guide-form">
  <?= csrf_field() ?>
  <input type="hidden" name="guide_type" value="<?= e($guideType) ?>">

  <div class="card">
    <div class="card-header"><h2><?= e($guideTypes[$guideType] ?? '') ?> &mdash; Guide Content</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>How to Play (rich text)</label>
        <div class="quill-editor" id="guide-editor"></div>
        <input type="hidden" name="guide_content" id="guide-content">
      </div>
    </div>
  </div>

  <div class="form-actions">
    <a href="<?= url('admin') ?>" class="btn btn-ghost">&#8592; Back to Dashboard</a>
    <button type="submit" class="btn btn-primary">Save Guide</button>
  </div>
</form>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const GUIDE_CONTENT = <?= json_encode($guideContent) ?>;
let guideDirty = false;
let guideArmed = false;
document.addEventListener('DOMContentLoaded', function () {
  const q = initQuill('#guide-editor', GUIDE_CONTENT);
  q.on('text-change', function (delta, oldDelta, source) { if (source === 'user' && guideArmed) guideDirty = true; });
  setTimeout(function () { guideArmed = true; }, 600);
  document.getElementById('guide-form').addEventListener('submit', function () {
    document.getElementById('guide-content').value = q.root.innerHTML;
    guideDirty = false;
  });
});

function switchGuideType(type) {
  if (guideDirty && !confirm('You have unsaved changes to this guide. Switch type and lose them?')) {
    document.getElementById('guide-type-select').value = <?= json_encode($guideType) ?>;
    return;
  }
  location.href = '?type=' + encodeURIComponent(type);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
