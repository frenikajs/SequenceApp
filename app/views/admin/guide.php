<?php
$pageTitle = 'How to Play Guide';
$activeNav = 'guide';
ob_start();
$guideContent = $guide ?? '';
?>

<div class="breadcrumb">
  <a href="<?= url('admin') ?>">Dashboard</a> /
  <span>How to Play Guide</span>
</div>

<p class="small muted" style="margin-bottom:1rem">
  This is the built-in guide players see when they click <strong>How to Play</strong> on a sequence page.
  Edit it once here; the same guide is shown for every sequence.
  <a href="<?= url('how-to-play') ?>" target="_blank" rel="noopener">Preview &#8599;</a>
</p>

<form method="POST" action="<?= url('admin/guide') ?>" id="guide-form">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header"><h2>Guide Content</h2></div>
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
document.addEventListener('DOMContentLoaded', function () {
  const q = initQuill('#guide-editor', GUIDE_CONTENT);
  document.getElementById('guide-form').addEventListener('submit', function () {
    document.getElementById('guide-content').value = q.root.innerHTML;
  });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
