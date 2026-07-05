<?php
$pageTitle = 'Diagnostic';
$activeNav = 'diagnostic';
ob_start();
?>

<div class="breadcrumb">
  <a href="<?= url('admin') ?>">Dashboard</a> /
  <span>Diagnostic</span>
</div>

<div class="card">
  <div class="card-header"><h2>🩺 Diagnostic</h2></div>
  <div class="card-body">
    <p class="small muted" style="margin-bottom:1.1rem">Run checks against the live database to confirm the app&rsquo;s schema is up to date (all required tables, fields and enum values).</p>
    <div class="diag-row">
      <button type="button" class="btn btn-primary" id="check-sql-btn" onclick="runSqlCheck()">Check SQL</button>
      <span id="check-sql-result" class="diag-result"></span>
    </div>
  </div>
</div>

<style>
.diag-row { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.diag-result { font-weight: 700; font-size: .95rem; }
.diag-result.diag-ok  { color: #4cc9a0; }
.diag-result.diag-bad { color: #f87171; }
.diag-result.diag-busy { color: #9ca3af; font-weight: 600; }
</style>

<script>
function runSqlCheck() {
  var btn = document.getElementById('check-sql-btn');
  var out = document.getElementById('check-sql-result');
  btn.disabled = true;
  out.className = 'diag-result diag-busy';
  out.textContent = 'Checking…';
  fetch('<?= url('admin/diagnostic/check') ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      btn.disabled = false;
      if (d && d.ok) {
        out.className = 'diag-result diag-ok';
        out.textContent = 'Successful!';
      } else {
        var list = (d && d.missing && d.missing.length) ? d.missing.join(', ') : 'unknown';
        out.className = 'diag-result diag-bad';
        out.textContent = 'Missing: ' + list;
      }
    })
    .catch(function () {
      btn.disabled = false;
      out.className = 'diag-result diag-bad';
      out.textContent = 'Check failed — please try again.';
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
