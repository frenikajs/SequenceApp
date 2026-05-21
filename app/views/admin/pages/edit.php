<?php
$pageTitle = 'Clue Page';
$activeNav = 'sequences';
ob_start();
$clueId  = (int)$clue['id'];
$seqId   = (int)$clue['sequence_id'];
$isNew   = ($page === null);
$slug    = $page['slug']         ?? '';
$siteType = $page['site_type']  ?? ($_POST['site_type'] ?? 'news');
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
  <strong>Please fix the following:</strong>
  <ul><?php foreach ($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="breadcrumb">
  <a href="<?= url('admin/sequences') ?>">Sequences</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/edit') ?>">Edit</a> /
  <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>">Clues</a> /
  <span>Clue Page</span>
</div>

<?php if (!$isNew): ?>
<div class="analytics-strip">
  <div class="analytic-item">
    <span class="analytic-icon">&#127760;</span>
    <span class="muted">Public URL</span>
    <a href="<?= url('p/' . $page['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">
      /p/<?= e($page['slug']) ?> &#8599;
    </a>
  </div>
</div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/clues/' . $clueId . '/page') ?>" id="page-form">
  <?= csrf_field() ?>

  <div class="form-grid">
    <!-- Left column: content -->
    <div class="form-col">

      <!-- Site type selector -->
      <div class="card">
        <div class="card-header"><h2>Site Type</h2></div>
        <div class="card-body">
          <div class="site-type-grid">
            <?php
            $types = [
              'news'        => ['&#128240;', 'News Article',    'Newspaper / news website style'],
              'corporate'   => ['&#128188;', 'Corporate',       'Company blog or intranet post'],
              'blog'        => ['&#9997;',   'Personal Blog',   'Elegant personal blog entry'],
              'archive'     => ['&#128190;', 'Archive',         'Digital archive / document database'],
              'calendar'    => ['&#128197;', 'Calendar',        'Calendar with meetings'],
              'inbox'       => ['&#9993;',   'Email Inbox',     'Outlook-style email inbox'],
              'sms'         => ['&#128172;', 'Text Messages',   'iOS iMessage conversation'],
              'invoice'     => ['&#129534;', 'Invoice',         'Professional invoice sheet'],
              'receipt'     => ['&#129534;', 'Receipt',         'Store receipt'],
              'map'         => ['&#128506;', 'Map',             'Street or festival map with up to 6 pins'],
            ];
            foreach ($types as $val => [$icon, $label, $desc]):
              $checked = $siteType === $val ? 'checked' : '';
            ?>
            <label class="site-type-card <?= $siteType === $val ? 'selected' : '' ?>">
              <input type="radio" name="site_type" value="<?= $val ?>" <?= $checked ?> onchange="markSelected(this)">
              <span class="st-icon"><?= $icon ?></span>
              <strong><?= $label ?></strong>
              <small><?= $desc ?></small>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Page content -->
      <div class="card mt-4">
        <div class="card-header"><h2 id="content-card-title">Page Content</h2></div>
        <div class="card-body">
          <div class="form-group">
            <label id="lbl-title"><span id="lbl-title-text">Page Title</span> <span class="req">*</span></label>
            <input type="text" name="page_title" id="fld-title" required
                   value="<?= e($page['page_title'] ?? ($_POST['page_title'] ?? '')) ?>"
                   placeholder="e.g. Mysterious Fire Destroys Archive Records">
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label id="lbl-author"></label>
              <input type="text" name="author" id="fld-author"
                     value="<?= e($page['author'] ?? ($_POST['author'] ?? '')) ?>">
            </div>
            <div class="form-group flex-1" id="grp-date">
              <label id="lbl-date"></label>
              <input type="date" name="publish_date" id="fld-date" onchange="calSyncDays()"
                     value="<?= e($page['publish_date'] ?? ($_POST['publish_date'] ?? '')) ?>">
            </div>
          </div>
          <div class="form-group" id="grp-body" <?= $siteType === 'sms' ? 'style="display:none"' : '' ?>>
            <label id="lbl-body">Body Content</label>
            <div class="quill-editor" id="page-editor"></div>
            <input type="hidden" name="content" id="page-content-hidden">
          </div>

          <?php
          $smsMsgs = ['', '', '', ''];
          if ($siteType === 'sms') {
              if (!empty($_POST['sms_msg1'])) {
                  for ($__i = 0; $__i < 4; $__i++) $smsMsgs[$__i] = $_POST['sms_msg' . ($__i + 1)] ?? '';
              } elseif ($page) {
                  $__raw = json_decode($page['nav_json'] ?? '[]', true) ?: [];
                  foreach ($__raw as $__i => $__item) { if ($__i < 4) $smsMsgs[$__i] = $__item['text'] ?? ''; }
              }
          }
          $smsRowLabels = ['Message 1 — from contact', 'Message 2 — your reply', 'Message 3 — from contact', 'Message 4 — your reply'];
          ?>
          <div class="form-group" id="grp-sms" <?= $siteType !== 'sms' ? 'style="display:none"' : '' ?>>
            <label>Messages</label>
            <?php foreach ($smsRowLabels as $__i => $__lbl): ?>
            <div class="sms-row">
              <small class="sms-lbl-<?= $__i % 2 === 0 ? 'them' : 'me' ?>"><?= $__lbl ?></small>
              <textarea name="sms_msg<?= $__i + 1 ?>" rows="2" class="sms-textarea"
                        placeholder="Enter message…"><?= e($smsMsgs[$__i]) ?></textarea>
            </div>
            <?php endforeach; ?></div>

          <?php
          $invLines = [['item'=>'','desc'=>'','qty'=>'','price'=>''], ['item'=>'','desc'=>'','qty'=>'','price'=>'']];
          if ($siteType === 'invoice') {
              if (isset($_POST['inv_item1'])) {
                  for ($__i = 0; $__i < 2; $__i++) {
                      $invLines[$__i] = [
                          'item'  => $_POST['inv_item'  . ($__i + 1)] ?? '',
                          'desc'  => $_POST['inv_desc'  . ($__i + 1)] ?? '',
                          'qty'   => $_POST['inv_qty'   . ($__i + 1)] ?? '',
                          'price' => $_POST['inv_price' . ($__i + 1)] ?? '',
                      ];
                  }
              } elseif ($page) {
                  $__raw = json_decode($page['nav_json'] ?? '[]', true) ?: [];
                  foreach ($__raw as $__i => $__row) {
                      if ($__i < 2) $invLines[$__i] = [
                          'item'  => $__row['item']  ?? '',
                          'desc'  => $__row['desc']  ?? '',
                          'qty'   => $__row['qty']   ?? '',
                          'price' => $__row['price'] ?? '',
                      ];
                  }
              }
          }
          ?>
          <div class="form-group" id="grp-invoice" <?= $siteType !== 'invoice' ? 'style="display:none"' : '' ?>>
            <label>Line Items</label>
            <?php for ($__i = 0; $__i < 2; $__i++): ?>
            <div class="inv-line">
              <small>Item <?= $__i + 1 ?></small>
              <input type="text" name="inv_item<?= $__i + 1 ?>" class="inv-field"
                     placeholder="Item" value="<?= e($invLines[$__i]['item']) ?>">
              <input type="text" name="inv_desc<?= $__i + 1 ?>" class="inv-field"
                     placeholder="Description" value="<?= e($invLines[$__i]['desc']) ?>">
              <div class="inv-line-num">
                <input type="number" step="any" min="0" name="inv_qty<?= $__i + 1 ?>" class="inv-field"
                       placeholder="Qty" value="<?= e($invLines[$__i]['qty']) ?>">
                <input type="number" step="any" min="0" name="inv_price<?= $__i + 1 ?>" class="inv-field"
                       placeholder="Unit price" value="<?= e($invLines[$__i]['price']) ?>">
              </div>
            </div>
            <?php endfor; ?>
            <small class="inv-hint">Total is calculated automatically from quantity × unit price.</small>
          </div>

          <?php
          $rcptLines = [['item'=>'','price'=>''], ['item'=>'','price'=>''], ['item'=>'','price'=>''], ['item'=>'','price'=>'']];
          if ($siteType === 'receipt') {
              if (isset($_POST['rcpt_item1'])) {
                  for ($__i = 0; $__i < 4; $__i++) {
                      $rcptLines[$__i] = [
                          'item'  => $_POST['rcpt_item'  . ($__i + 1)] ?? '',
                          'price' => $_POST['rcpt_price' . ($__i + 1)] ?? '',
                      ];
                  }
              } elseif ($page) {
                  $__raw = json_decode($page['nav_json'] ?? '[]', true) ?: [];
                  foreach ($__raw as $__i => $__row) {
                      if ($__i < 4) $rcptLines[$__i] = [
                          'item'  => $__row['item']  ?? '',
                          'price' => $__row['price'] ?? '',
                      ];
                  }
              }
          }
          ?>
          <div class="form-group" id="grp-receipt" <?= $siteType !== 'receipt' ? 'style="display:none"' : '' ?>>
            <label>Line Items</label>
            <?php for ($__i = 0; $__i < 4; $__i++): ?>
            <div class="rcpt-line">
              <input type="text" name="rcpt_item<?= $__i + 1 ?>" class="inv-field"
                     placeholder="Item <?= $__i + 1 ?>" value="<?= e($rcptLines[$__i]['item']) ?>">
              <input type="number" step="any" min="0" name="rcpt_price<?= $__i + 1 ?>" class="inv-field rcpt-price"
                     placeholder="Price" value="<?= e($rcptLines[$__i]['price']) ?>">
            </div>
            <?php endfor; ?>
            <small class="inv-hint">Total is the sum of all item prices.</small>
          </div>

          <?php
          $mapMarkers = ['', '', '', '', '', ''];
          $mapKind    = 'street';
          if ($siteType === 'map') {
              if (isset($_POST['map_marker1'])) {
                  for ($__i = 0; $__i < 6; $__i++) $mapMarkers[$__i] = $_POST['map_marker' . ($__i + 1)] ?? '';
                  $mapKind = $_POST['map_kind'] ?? 'street';
              } elseif ($page) {
                  $__raw = json_decode($page['nav_json'] ?? '[]', true) ?: [];
                  if (isset($__raw['markers']) && is_array($__raw['markers'])) {
                      $mapKind = $__raw['type'] ?? 'street';
                      foreach ($__raw['markers'] as $__i => $__row) {
                          if ($__i < 6) $mapMarkers[$__i] = $__row['label'] ?? '';
                      }
                  } elseif (is_array($__raw)) {
                      foreach ($__raw as $__i => $__row) {
                          if ($__i < 6) $mapMarkers[$__i] = $__row['label'] ?? '';
                      }
                  }
              }
          }
          if (!in_array($mapKind, ['street', 'festival'], true)) { $mapKind = 'street'; }
          ?>
          <div class="form-group" id="grp-map" <?= $siteType !== 'map' ? 'style="display:none"' : '' ?>>
            <label>Map Type</label>
            <select name="map_kind" class="inv-field" style="margin-bottom:.75rem">
              <option value="street" <?= $mapKind === 'street' ? 'selected' : '' ?>>Street Map</option>
              <option value="festival" <?= $mapKind === 'festival' ? 'selected' : '' ?>>Festival</option>
            </select>
            <label>Map Markers</label>
            <?php for ($__i = 0; $__i < 6; $__i++): ?>
            <div class="map-marker-row">
              <span class="map-marker-num"><?= $__i + 1 ?></span>
              <input type="text" name="map_marker<?= $__i + 1 ?>" class="inv-field"
                     placeholder="Location <?= $__i + 1 ?> name (leave blank to hide pin)"
                     value="<?= e($mapMarkers[$__i]) ?>">
            </div>
            <?php endfor; ?>
            <small class="inv-hint">Pins are auto-placed on the chosen fake map. Empty markers are hidden.</small>
          </div>

          <?php
          $blogCmtName = '';
          $blogCmtText = '';
          if ($siteType === 'blog') {
              if (isset($_POST['blog_cmt_name']) || isset($_POST['blog_cmt_text'])) {
                  $blogCmtName = $_POST['blog_cmt_name'] ?? '';
                  $blogCmtText = $_POST['blog_cmt_text'] ?? '';
              } elseif ($page) {
                  $__bc = json_decode($page['nav_json'] ?? '[]', true);
                  if (is_array($__bc)) {
                      $blogCmtName = $__bc['cmt_name'] ?? '';
                      $blogCmtText = $__bc['cmt_text'] ?? '';
                  }
              }
          }
          ?>
          <div class="form-group" id="grp-blogcomment" <?= $siteType !== 'blog' ? 'style="display:none"' : '' ?>>
            <label>Reader Comment</label>
            <input type="text" name="blog_cmt_name" class="inv-field"
                   placeholder="Commenter name (e.g. Thomas Reyes)" value="<?= e($blogCmtName) ?>">
            <textarea name="blog_cmt_text" rows="3" class="sms-textarea"
                      placeholder="Comment text… (leave blank to use a default fake comment)"><?= e($blogCmtText) ?></textarea>
            <small class="inv-hint">Shown as the last comment on the blog post. The other comments are fake and fixed.</small>
          </div>

          <?php
          $corpCmtName = '';
          $corpCmtText = '';
          if ($siteType === 'corporate') {
              if (isset($_POST['corp_cmt_name']) || isset($_POST['corp_cmt_text'])) {
                  $corpCmtName = $_POST['corp_cmt_name'] ?? '';
                  $corpCmtText = $_POST['corp_cmt_text'] ?? '';
              } elseif ($page) {
                  $__cc = json_decode($page['nav_json'] ?? '[]', true);
                  if (is_array($__cc)) {
                      $corpCmtName = $__cc['cmt_name'] ?? '';
                      $corpCmtText = $__cc['cmt_text'] ?? '';
                  }
              }
          }
          ?>
          <div class="form-group" id="grp-corpcomment" <?= $siteType !== 'corporate' ? 'style="display:none"' : '' ?>>
            <label>First Comment (Pinned Announcement)</label>
            <input type="text" name="corp_cmt_name" class="inv-field"
                   placeholder="Commenter name (e.g. Priya Nair)" value="<?= e($corpCmtName) ?>">
            <textarea name="corp_cmt_text" rows="3" class="sms-textarea"
                      placeholder="Comment text… (leave blank to use a default fake comment)"><?= e($corpCmtText) ?></textarea>
            <small class="inv-hint">Shown as the first comment under the pinned announcement. Other posts and comments are fake and fixed.</small>
          </div>

          <?php
          $arcLog = ['op'=>'','action'=>''];
          if ($siteType === 'archive') {
              if (isset($_POST['arc_log_op']) || isset($_POST['arc_log_action'])) {
                  $arcLog = [
                      'op'     => $_POST['arc_log_op']     ?? '',
                      'action' => $_POST['arc_log_action'] ?? '',
                  ];
              } elseif ($page) {
                  $__al = json_decode($page['nav_json'] ?? '[]', true);
                  if (is_array($__al)) {
                      $arcLog = [
                          'op'     => $__al['log_op']     ?? '',
                          'action' => $__al['log_action'] ?? '',
                      ];
                  }
              }
          }
          ?>
          <div class="form-group" id="grp-archivelog" <?= $siteType !== 'archive' ? 'style="display:none"' : '' ?>>
            <label>Last Access-Log Entry</label>
            <input type="text" name="arc_log_op" class="inv-field"
                   placeholder="Terminal / Operator (e.g. TERM-02 / ADMIN-001)" value="<?= e($arcLog['op']) ?>">
            <input type="text" name="arc_log_action" class="inv-field"
                   placeholder="Action (e.g. VERIFY)" value="<?= e($arcLog['action']) ?>">
            <small class="inv-hint">Overrides the operator and action of the final access-log row. Leave a field blank to keep its default. Everything else in the log is fake and fixed.</small>
          </div>

          <?php
          $calEv = [
              ['name'=>'','day'=>0,'time'=>'','attendees'=>''],
              ['name'=>'','day'=>0,'time'=>'','attendees'=>''],
              ['name'=>'','day'=>0,'time'=>'','attendees'=>''],
          ];
          if ($siteType === 'calendar') {
              if (isset($_POST['cal_name1'])) {
                  for ($__i = 0; $__i < 3; $__i++) {
                      $calEv[$__i] = [
                          'name'      => $_POST['cal_name' . ($__i + 1)] ?? '',
                          'day'       => (int)($_POST['cal_day' . ($__i + 1)] ?? 0),
                          'time'      => $_POST['cal_time' . ($__i + 1)] ?? '',
                          'attendees' => $_POST['cal_attendees' . ($__i + 1)] ?? '',
                      ];
                  }
              } elseif ($page) {
                  $__cd = json_decode($page['nav_json'] ?? '[]', true) ?: [];
                  if (isset($__cd['events']) && is_array($__cd['events'])) {
                      foreach ($__cd['events'] as $__i => $__row) {
                          if ($__i < 3) $calEv[$__i] = [
                              'name'      => $__row['name']      ?? '',
                              'day'       => (int)($__row['day'] ?? 0),
                              'time'      => $__row['time']      ?? '',
                              'attendees' => $__row['attendees'] ?? '',
                          ];
                      }
                  } elseif (is_array($__cd)) {
                      foreach ($__cd as $__i => $__row) {
                          if ($__i >= 3) break;
                          $__dt = !empty($__row['date']) ? strtotime($__row['date']) : false;
                          $calEv[$__i] = [
                              'name'      => $__row['name']      ?? '',
                              'day'       => $__dt !== false ? (int)date('j', $__dt) : 0,
                              'time'      => $__row['time']      ?? '',
                              'attendees' => $__row['attendees'] ?? '',
                          ];
                      }
                  }
              }
          }
          ?>
          <div class="form-group" id="grp-calendar" <?= $siteType !== 'calendar' ? 'style="display:none"' : '' ?>>
            <label>Meetings / Events</label>
            <small class="inv-hint" style="margin-top:0;margin-bottom:.6rem">The calendar shows the month &amp; year of the <strong>Reference Date</strong> above. Set that first, then pick a day for each meeting.</small>
            <?php for ($__i = 0; $__i < 3; $__i++): ?>
            <div class="cal-ev">
              <small>Meeting <?= $__i + 1 ?></small>
              <input type="text" name="cal_name<?= $__i + 1 ?>" class="inv-field"
                     placeholder="Meeting name (e.g. Quarterly Strategy Review)" value="<?= e($calEv[$__i]['name']) ?>">
              <div class="cal-ev-row">
                <select name="cal_day<?= $__i + 1 ?>" class="inv-field cal-day" data-sel="<?= (int)$calEv[$__i]['day'] ?>">
                  <option value="0">— Day —</option>
                </select>
                <input type="time" name="cal_time<?= $__i + 1 ?>" class="inv-field" value="<?= e($calEv[$__i]['time']) ?>">
              </div>
              <input type="text" name="cal_attendees<?= $__i + 1 ?>" class="inv-field"
                     placeholder="Attendees, comma-separated (e.g. J. Marlowe, A. Reyes)" value="<?= e($calEv[$__i]['attendees']) ?>">
            </div>
            <?php endfor; ?>
            <small class="inv-hint">Pick the month/year, then a day within that month for each meeting. Up to 3 meetings; leave a name blank to hide it. The rest of the month is filled with fake events.</small>
          </div>
        </div>
      </div>

    </div>

    <!-- Right column: appearance -->
    <div class="form-col-sm">
      <div class="card sticky-top">
        <div class="card-header"><h2>Appearance</h2></div>
        <div class="card-body">

          <div class="form-group">
            <label>URL Slug <span class="req">*</span></label>
            <div class="input-prefix">
              <span class="prefix-text">/p/</span>
              <input type="text" name="slug" id="slug-field" required
                     value="<?= e($page['slug'] ?? ($_POST['slug'] ?? '')) ?>"
                     placeholder="the-daily-record-1987">
            </div>
            <small>Shareable link you paste into clue text.</small>
          </div>

          <div class="form-group">
            <label id="lbl-sitename"><span id="lbl-sitename-text">Site Name</span> <span class="req">*</span></label>
            <input type="text" name="site_name" id="fld-sitename" required
                   value="<?= e($page['site_name'] ?? ($_POST['site_name'] ?? 'The Daily Record')) ?>">
            <small id="hint-sitename">Displayed in the header/masthead of the fake site.</small>
          </div>

          <div class="form-group" id="grp-footer">
            <label id="lbl-footer">Footer Text</label>
            <input type="text" name="footer_text" id="fld-footer"
                   value="<?= e($page['footer_text'] ?? ($_POST['footer_text'] ?? '')) ?>"
                   placeholder="&copy; <?= date('Y') ?> The Daily Record">
          </div>

          <!-- Nav items — hidden for inbox type -->
          <div class="form-group" id="grp-nav">
            <label>Navigation Links</label>
            <div id="nav-items">
              <?php foreach ($navItems as $item): ?>
              <div class="nav-item-row">
                <input type="text" name="nav_label[]" placeholder="Label"
                       value="<?= e($item['label']) ?>">
                <input type="text" name="nav_href[]"  placeholder="# or URL"
                       value="<?= e($item['href']) ?>">
                <button type="button" class="btn btn-danger btn-xs" onclick="this.closest('.nav-item-row').remove()">&#10005;</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-ghost btn-sm mt-2" onclick="addNavRow()">&#43; Add link</button>
            <small>Use <code>#</code> as the href for non-functional links.</small>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="form-actions">
    <a href="<?= url('admin/sequences/' . $seqId . '/clues') ?>" class="btn btn-ghost">&#8592; Back to Clues</a>
    <?php if (!$isNew): ?>
    <a href="<?= url('p/' . $page['slug']) ?>" target="_blank" class="btn btn-secondary">Preview &#8599;</a>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Create Page' : 'Save Changes' ?></button>
  </div>
</form>

<?php if (!$isNew): ?>
<div class="card mt-4 card-danger">
  <div class="card-header"><h2>Danger Zone</h2></div>
  <div class="card-body">
    <form method="POST" action="<?= url('admin/clues/' . $clueId . '/page/delete') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger btn-full"
        onclick="return confirm('Delete this decoy page? This cannot be undone.')">
        &#10005; Delete Clue Page
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<style>
.site-type-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.75rem}
.site-type-card{display:flex;flex-direction:column;align-items:center;gap:.3rem;padding:.85rem .75rem;border:2px solid rgba(255,255,255,.08);border-radius:10px;cursor:pointer;text-align:center;transition:all .2s;background:rgba(255,255,255,.02)}
.site-type-card input{display:none}
.site-type-card .st-icon{font-size:1.6rem}
.site-type-card strong{font-size:.85rem;font-weight:600}
.site-type-card small{font-size:.72rem;color:rgba(255,255,255,.4);line-height:1.35}
.site-type-card:hover{border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.04)}
.site-type-card.selected{border-color:var(--accent,#6c63ff);background:rgba(108,99,255,.08)}
.nav-item-row{display:flex;gap:.5rem;margin-bottom:.5rem;align-items:center}
.nav-item-row input{flex:1;padding:.45rem .7rem;border-radius:7px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:inherit;font-size:.85rem;font-family:inherit}
.nav-item-row input:first-child{max-width:130px}
.sms-row{margin-bottom:.75rem}
.sms-row small{display:block;font-size:.71rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin-bottom:.3rem;padding:.2rem .5rem;border-radius:4px}
.sms-lbl-them{background:rgba(255,255,255,.04);color:rgba(255,255,255,.45)!important}
.sms-lbl-me{background:rgba(108,99,255,.1);color:var(--accent,#6c63ff)!important}
.sms-textarea{width:100%;padding:.55rem .75rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:7px;color:inherit;font-family:inherit;font-size:.88rem;resize:vertical;line-height:1.5;min-height:60px}
.sms-textarea:focus{outline:none;border-color:rgba(255,255,255,.3)}
.inv-line{margin-bottom:.85rem;padding:.75rem;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(255,255,255,.02)}
.inv-line small{display:block;font-size:.71rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--accent,#6c63ff);margin-bottom:.4rem}
.inv-line .inv-field{width:100%;padding:.5rem .7rem;margin-bottom:.4rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:7px;color:inherit;font-family:inherit;font-size:.85rem}
.inv-line .inv-field:focus{outline:none;border-color:rgba(255,255,255,.3)}
.inv-line-num{display:flex;gap:.5rem}
.inv-line-num .inv-field{margin-bottom:0}
.inv-hint{display:block;font-size:.75rem;color:rgba(255,255,255,.4);margin-top:.5rem;text-transform:none!important;letter-spacing:normal!important;font-weight:400!important}
.rcpt-line{display:flex;gap:.5rem;margin-bottom:.5rem}
.rcpt-line .inv-field{margin-bottom:0;flex:1}
.rcpt-line .rcpt-price{max-width:120px;flex:none}
.map-marker-row{display:flex;gap:.5rem;margin-bottom:.5rem;align-items:center}
.map-marker-num{width:24px;height:24px;flex:none;border-radius:50%;background:#ea4335;color:#fff;font-size:.78rem;font-weight:700;display:flex;align-items:center;justify-content:center}
.map-marker-row .inv-field{margin-bottom:0;flex:1}
#grp-map>select.inv-field{display:block;width:100%;padding:.55rem .75rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:7px;color:inherit;font-family:inherit;font-size:.88rem}
#grp-map>select.inv-field:focus{outline:none;border-color:rgba(255,255,255,.3)}
#grp-blogcomment .inv-field,#grp-corpcomment .inv-field,#grp-archivelog .inv-field{width:100%;padding:.55rem .75rem;margin-bottom:.5rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:7px;color:inherit;font-family:inherit;font-size:.88rem}
#grp-blogcomment .inv-field:focus,#grp-corpcomment .inv-field:focus,#grp-archivelog .inv-field:focus{outline:none;border-color:rgba(255,255,255,.3)}
.cal-ev{margin-bottom:.85rem;padding:.75rem;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(255,255,255,.02)}
.cal-ev small{display:block;font-size:.71rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--accent,#6c63ff);margin-bottom:.4rem}
.cal-ev .inv-field{width:100%;padding:.5rem .7rem;margin-bottom:.4rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:7px;color:inherit;font-family:inherit;font-size:.85rem}
.cal-ev .inv-field:focus{outline:none;border-color:rgba(255,255,255,.3)}
.cal-ev-row{display:flex;gap:.5rem}
.cal-ev-row .inv-field{flex:1;margin-bottom:.4rem}
</style>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const PAGE_CONTENT   = <?= json_encode($page['content'] ?? '') ?>;
const INITIAL_TYPE   = <?= json_encode($siteType) ?>;

const FIELD_CONFIG = {
  inbox: {
    cardTitle:    'Email',
    title:        'Email Subject',
    titlePh:      'e.g. URGENT: Project Halcyon — Access Request',
    author:       'From (sender address)',
    authorPh:     'e.g. director@company.com',
    date:         'Email Date',
    body:         'Email Body',
    sitename:     'Account Name',
    sitenamePh:   'Name shown in the inbox sidebar',
    footer:       'To (recipient address)',
    footerPh:     'e.g. j.marlowe@company.com',
    showNav:      false,
  },
  sms: {
    cardTitle:    'Text Messages',
    title:        'Contact Name',
    titlePh:      'e.g. Detective Marlowe',
    author:       'Contact Detail',
    authorPh:     'e.g. +1 (555) 234-5678',
    date:         'Message Date',
    body:         'Body',
    sitename:     'Your Display Name',
    sitenamePh:   'Shown as the sender for your replies',
    footer:       'Status Line',
    footerPh:     'e.g. Read 2:34 PM',
    showNav:      false,
  },
  invoice: {
    cardTitle:    'Invoice Details',
    title:        'Invoice Number',
    titlePh:      'e.g. INV-2026-0042',
    author:       'Bill To',
    authorPh:     'e.g. Halcyon Industries Ltd.',
    date:         'Invoice Date',
    body:         'Body',
    sitename:     'Business Name',
    sitenamePh:   'Your company name (shown in the invoice header)',
    footer:       'Notes / Payment Terms',
    footerPh:     'e.g. Payment due within 30 days',
    showNav:      false,
  },
  receipt: {
    cardTitle:    'Receipt Details',
    title:        'Store Address',
    titlePh:      'e.g. 1234 Market St, Springfield IL',
    author:       'Cashier / Register',
    authorPh:     'e.g. CASHIER ANGELA  REG 04',
    date:         'Transaction Date',
    body:         'Body',
    sitename:     'Store Name',
    sitenamePh:   'e.g. Walmart',
    footer:       'Footer Message',
    footerPh:     'e.g. Thank you for shopping with us!',
    showNav:      false,
  },
  map: {
    cardTitle:    'Map Details',
    title:        'Map Title',
    titlePh:      'e.g. Ashford District',
    author:       'Subtitle',
    authorPh:     'e.g. Investigation area',
    date:         'Date',
    body:         'Body',
    sitename:     'Area / Region Name',
    sitenamePh:   'Shown in the map header bar',
    footer:       'Caption',
    footerPh:     'e.g. Last updated this morning',
    showNav:      false,
  },
  calendar: {
    cardTitle:    'Calendar Details',
    title:        'Calendar Title',
    titlePh:      'e.g. October Schedule',
    author:       'Your Name (mailbox owner)',
    authorPh:     'e.g. J. Marlowe',
    date:         'Reference Date',
    body:         'Body',
    sitename:     'Account / Mailbox Name',
    sitenamePh:   'Shown in the Outlook header',
    footer:       'Status Bar Text',
    footerPh:     'e.g. All times shown in local time',
    showNav:      false,
  },
  blog: {
    cardTitle:    'Post Content',
    title:        'Post Title',
    titlePh:      'e.g. What the River Carried Away',
    author:       'Author',
    authorPh:     'e.g. J. Marlowe',
    date:         'Published Date',
    body:         'Post Body',
    sitename:     'Blog Name',
    sitenamePh:   'Shown as the blog title in the header.',
    footer:       'Footer Text',
    footerPh:     'About text shown in the footer',
    showNav:      false,
  },
  archive: {
    cardTitle:    'Document Content',
    title:        'Document Title',
    titlePh:      'e.g. Memorandum on Project Halcyon',
    author:       'Author',
    authorPh:     'e.g. R. Castellan',
    date:         'Date Filed',
    body:         'Document Body',
    sitename:     'Archive Name',
    sitenamePh:   'Shown in the archive system header.',
    footer:       'Footer Text',
    footerPh:     'e.g. National Records Office',
    showNav:      false,
  },
  corporate: {
    cardTitle:    'Post Content',
    title:        'Post Headline',
    titlePh:      'e.g. Q3 Results & What Comes Next',
    author:       'Author',
    authorPh:     'e.g. Jordan Avery',
    date:         'Posted Date',
    body:         'Post Body',
    sitename:     'Company Name',
    sitenamePh:   'Shown as the brand in the feed top bar.',
    footer:       'Author Role / Footer',
    footerPh:     'e.g. Head of Communications',
    showNav:      false,
  },
  _default: {
    cardTitle:    'Page Content',
    title:        'Page Title',
    titlePh:      'e.g. Mysterious Fire Destroys Archive Records',
    author:       'Author / By-line',
    authorPh:     'e.g. J. Marlowe',
    date:         'Published Date',
    body:         'Body Content',
    sitename:     'Site Name',
    sitenamePh:   'Displayed in the header/masthead of the fake site.',
    footer:       'Footer Text',
    footerPh:     '© ' + new Date().getFullYear() + ' The Daily Record',
    showNav:      true,
  },
};

function applyTypeConfig(type) {
  const cfg = FIELD_CONFIG[type] || FIELD_CONFIG._default;
  document.getElementById('content-card-title').textContent    = cfg.cardTitle;
  document.getElementById('lbl-title-text').textContent        = cfg.title;
  document.getElementById('fld-title').placeholder             = cfg.titlePh;
  document.getElementById('lbl-author').textContent            = cfg.author;
  document.getElementById('fld-author').placeholder            = cfg.authorPh;
  document.getElementById('lbl-date').textContent              = cfg.date;
  document.getElementById('lbl-body').textContent              = cfg.body;
  document.getElementById('lbl-sitename-text').textContent     = cfg.sitename;
  document.getElementById('hint-sitename').textContent         = cfg.sitenamePh;
  document.getElementById('lbl-footer').textContent            = cfg.footer;
  document.getElementById('fld-footer').placeholder            = cfg.footerPh;
  document.getElementById('grp-nav').style.display             = cfg.showNav ? '' : 'none';
  const noBody = (type === 'sms' || type === 'invoice' || type === 'receipt' || type === 'map' || type === 'calendar');
  document.getElementById('grp-body').style.display            = noBody ? 'none' : '';
  document.getElementById('grp-sms').style.display             = (type === 'sms') ? '' : 'none';
  document.getElementById('grp-invoice').style.display         = (type === 'invoice') ? '' : 'none';
  document.getElementById('grp-receipt').style.display         = (type === 'receipt') ? '' : 'none';
  document.getElementById('grp-map').style.display             = (type === 'map') ? '' : 'none';
  document.getElementById('grp-blogcomment').style.display     = (type === 'blog') ? '' : 'none';
  document.getElementById('grp-corpcomment').style.display      = (type === 'corporate') ? '' : 'none';
  document.getElementById('grp-archivelog').style.display       = (type === 'archive') ? '' : 'none';
  document.getElementById('grp-calendar').style.display        = (type === 'calendar') ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
  const pageQ = initQuill('#page-editor', PAGE_CONTENT);
  document.getElementById('page-form').addEventListener('submit', function () {
    document.getElementById('page-content-hidden').value = pageQ.root.innerHTML;
  });

  // Auto-generate slug from page title if slug is empty
  const titleInput = document.getElementById('fld-title');
  const slugField  = document.getElementById('slug-field');
  titleInput.addEventListener('input', function () {
    if (slugField.value === '') {
      slugField.value = this.value
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .substring(0, 80);
    }
  });

  applyTypeConfig(INITIAL_TYPE);
  calSyncDays();
});

function calSyncDays() {
  const dEl = document.getElementById('fld-date');
  if (!dEl) return;
  const ref = dEl.value ? new Date(dEl.value + 'T00:00:00') : new Date();
  const y = ref.getFullYear();
  const m = ref.getMonth() + 1;            // 1-12
  const days = new Date(y, m, 0).getDate(); // days in the reference month
  document.querySelectorAll('.cal-day').forEach(function (sel) {
    const cur = (sel.value && sel.value !== '0') ? sel.value : (sel.dataset.sel || '0');
    const want = parseInt(cur, 10);
    let html = '<option value="0">— Day —</option>';
    for (let d = 1; d <= days; d++) {
      html += '<option value="' + d + '">' + d + '</option>';
    }
    sel.innerHTML = html;
    sel.value = (want >= 1 && want <= days) ? String(want) : '0';
  });
}

function markSelected(radio) {
  document.querySelectorAll('.site-type-card').forEach(c => c.classList.remove('selected'));
  radio.closest('.site-type-card').classList.add('selected');
  applyTypeConfig(radio.value);
}

function addNavRow() {
  const row = document.createElement('div');
  row.className = 'nav-item-row';
  row.innerHTML = '<input type="text" name="nav_label[]" placeholder="Label">' +
                  '<input type="text" name="nav_href[]"  placeholder="# or URL" value="#">' +
                  '<button type="button" class="btn btn-danger btn-xs" onclick="this.closest(\'.nav-item-row\').remove()">&#10005;</button>';
  document.getElementById('nav-items').appendChild(row);
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
