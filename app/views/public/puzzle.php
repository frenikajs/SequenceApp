<?php
/** @var array $puzzle  @var array $items  @var array $clues  @var bool $solved  @var bool $wrong */
$pzSlug   = (string)$puzzle['slug'];
$pzTitle  = (string)($puzzle['title'] ?: 'Puzzle');
$pzPrompt = trim((string)($puzzle['prompt'] ?? ''));
$pzReward = (string)($puzzle['reward_content'] ?? '');
$pzRType  = (string)($puzzle['reward_file_type'] ?? '');
$pzRPath  = (string)($puzzle['reward_file_path'] ?? '');
$pzRName  = (string)($puzzle['reward_original_filename'] ?? '');
$pzRCap   = (string)($puzzle['reward_file_caption'] ?? '');
$pzRUrl   = $pzRPath !== '' ? UPLOAD_URL . '/' . $pzRPath : '';

function pzMedia(string $type, string $url): string
{
    $u = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    return match ($type) {
        'image' => "<img src=\"$u\" alt=\"\" class=\"pz-img\" loading=\"lazy\">",
        'audio' => "<audio controls preload=\"metadata\" style=\"width:100%\"><source src=\"$u\"></audio>",
        'video' => "<video controls preload=\"metadata\" playsinline class=\"pz-vid\"><source src=\"$u\"></video>",
        'pdf'   => "<a href=\"$u\" target=\"_blank\" class=\"pz-file\">&#128196; Open PDF</a>",
        default => "<a href=\"$u\" class=\"pz-file\" download>&#128206; Download file</a>",
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pzTitle, ENT_QUOTES, 'UTF-8') ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:#0f0f1a;color:#e6e6ef;line-height:1.6;padding:2rem 1rem 4rem}
.pz-wrap{max-width:920px;margin:0 auto}
.pz-title{font-size:1.9rem;font-weight:700;letter-spacing:-.02em;margin-bottom:.4rem}
.pz-prompt{color:rgba(255,255,255,.6);font-size:.98rem;margin-bottom:1.75rem}
.pz-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;align-items:start}
.pz-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:1.4rem}
.pz-card h2{font-size:.74rem;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.4);font-weight:700;margin-bottom:1rem}
.pz-clues{counter-reset:c;list-style:none}
.pz-clues li{counter-increment:c;position:relative;padding:.55rem 0 .55rem 2.2rem;border-bottom:1px solid rgba(255,255,255,.06);font-size:.95rem}
.pz-clues li:last-child{border-bottom:none}
.pz-clues li::before{content:counter(c);position:absolute;left:0;top:.5rem;width:1.5rem;height:1.5rem;border-radius:50%;background:rgba(108,99,255,.18);color:#b3acff;font-size:.78rem;font-weight:700;display:flex;align-items:center;justify-content:center}
.pz-sortable{list-style:none;display:flex;flex-direction:column;gap:.55rem}
.pz-item{display:flex;align-items:center;gap:.75rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:.85rem 1rem;cursor:grab;font-size:.98rem;transition:border-color .15s,background .15s;user-select:none;touch-action:none}
.pz-item:hover{border-color:rgba(108,99,255,.5)}
.pz-item.dragging{opacity:.45;cursor:grabbing}
.pz-item .grip{color:rgba(255,255,255,.35);font-size:1.05rem;flex-shrink:0}
.pz-cipher{font-family:'Courier New',monospace;font-size:1.4rem;letter-spacing:.18em;word-break:break-word;background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:1.1rem 1.25rem;line-height:1.7}
.pz-lbl{display:block;margin:1.25rem 0 .4rem;font-size:.85rem;color:rgba(255,255,255,.55)}
.pz-input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:10px;padding:.85rem 1rem;color:#e6e6ef;font-size:1.05rem;font-family:inherit}
.pz-input:focus{outline:none;border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.2)}
.pz-elim{list-style:none;display:flex;flex-direction:column;gap:.55rem;margin-bottom:.5rem}
.pz-eitem{display:flex;align-items:center;gap:.85rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:.85rem 1rem;cursor:pointer;font-size:.98rem;transition:all .15s;user-select:none}
.pz-eitem:hover{border-color:rgba(239,68,68,.5)}
.pz-eitem .pz-emark{display:inline-flex;align-items:center;justify-content:center;width:1.5rem;height:1.5rem;border-radius:50%;border:1.5px solid rgba(255,255,255,.25);color:transparent;font-weight:700;font-size:.85rem;flex-shrink:0;transition:all .15s}
.pz-eitem .pz-etext{flex:1}
.pz-eitem.struck{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.45);color:rgba(255,255,255,.45)}
.pz-eitem.struck .pz-etext{text-decoration:line-through}
.pz-eitem.struck .pz-emark{background:#ef4444;border-color:#ef4444;color:#fff}
.pz-eitem:focus{outline:none;border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.22)}
.pz-ws{display:grid;gap:3px;width:100%;max-width:480px;margin:.25rem auto 1rem;touch-action:none;-webkit-user-select:none;user-select:none}
.pz-cell{aspect-ratio:1;display:flex;align-items:center;justify-content:center;padding:0;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:6px;color:#e6e6ef;font-family:'Courier New',monospace;font-weight:700;font-size:clamp(.7rem,3.4vw,1.05rem);cursor:pointer;text-transform:uppercase;transition:background .1s,border-color .1s}
.pz-cell.sel{background:rgba(108,99,255,.5);border-color:#6c63ff;color:#fff}
.pz-cell.found{background:rgba(16,185,129,.4);border-color:#34d399;color:#fff}
.pz-wordlist{list-style:none;display:flex;flex-wrap:wrap;gap:.5rem}
.pz-wordlist li{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:.35rem .7rem;font-size:.9rem;letter-spacing:.05em;text-transform:uppercase;font-family:'Courier New',monospace}
.pz-wordlist li.found{background:rgba(16,185,129,.18);border-color:rgba(16,185,129,.4);color:#6ee7b7;text-decoration:line-through}
.pz-wsprog{margin-top:.9rem;font-size:.85rem;color:rgba(255,255,255,.55)}
.pz-hide{display:none}
.pz-btn-ghost{background:transparent;border:1px solid rgba(255,255,255,.18);color:rgba(255,255,255,.75)}
.pz-btn-ghost:hover{filter:none;border-color:rgba(255,255,255,.4)}
/* Fill-in-the-Blank puzzle */
.pz-fb-text{font-size:1.05rem;line-height:2.4;white-space:pre-wrap;word-spacing:.15em;padding:1.2rem 1.4rem;background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.1);border-radius:10px;color:#e6e6ef}
.pz-fb-blank{display:inline-block;min-width:120px;min-height:1.9em;padding:.1rem .55rem;margin:0 .15rem;border:1px solid rgba(179,172,255,.55);border-bottom:3px solid #b3acff;border-radius:4px 4px 0 0;background:rgba(108,99,255,.18);vertical-align:baseline;font-weight:700;color:#fff;text-align:center;cursor:pointer;transition:background .15s,border-bottom-color .15s,box-shadow .15s;box-shadow:inset 0 -1px 0 rgba(179,172,255,.2)}
.pz-fb-blank:hover,.pz-fb-blank:focus-visible{background:rgba(108,99,255,.32);border-color:rgba(179,172,255,.8);border-bottom-color:#d0caff}
.pz-fb-blank.target{background:rgba(108,99,255,.42);border-color:#ffd166;border-bottom-color:#ffd166;box-shadow:0 0 0 2px rgba(255,209,102,.4)}
.pz-fb-blank.filled{background:rgba(108,99,255,.35);border-color:rgba(168,150,255,.7);border-bottom-color:#a896ff}
.pz-fb-input{display:inline-block;min-width:140px;padding:.2rem .55rem;margin:0 .15rem;background:rgba(108,99,255,.15);border:1px solid rgba(108,99,255,.6);border-bottom:2px solid #b3acff;border-radius:6px;color:#fff;font-family:inherit;font-size:1rem;font-weight:600;text-align:center;vertical-align:baseline;box-shadow:0 0 0 1px rgba(108,99,255,.15)}
.pz-fb-input:focus{outline:none;border-color:#6c63ff;border-bottom-color:#d0caff;background:rgba(108,99,255,.25);box-shadow:0 0 0 3px rgba(108,99,255,.3)}
.pz-fb-bank-wrap{margin:1.25rem 0 .5rem}
.pz-fb-bank-wrap h3{font-size:.74rem;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.45);font-weight:700;margin-bottom:.5rem}
.pz-fb-bank{list-style:none;display:flex;flex-wrap:wrap;gap:.55rem;padding:.85rem;background:rgba(255,255,255,.04);border:1px dashed rgba(255,255,255,.2);border-radius:10px;min-height:60px;align-items:flex-start}
.pz-fb-word{background:rgba(108,99,255,.2);border:1px solid rgba(108,99,255,.5);color:#fff;padding:.55rem 1rem;border-radius:8px;font-weight:600;cursor:grab;user-select:none;touch-action:manipulation;transition:transform .1s,background .15s,box-shadow .15s}
.pz-fb-word:hover,.pz-fb-word:focus-visible{background:rgba(108,99,255,.3);outline:none}
.pz-fb-word.selected{background:rgba(255,209,102,.25);border-color:#ffd166;box-shadow:0 0 0 3px rgba(255,209,102,.3)}
.pz-fb-word.dragging{opacity:.45;cursor:grabbing}
.pz-fb-word.hidden{display:none}
.pz-fb-clues{margin-top:1.5rem}
.pz-fb-clues h3{font-size:.74rem;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.45);font-weight:700;margin-bottom:.5rem}
.pz-match{position:relative;display:grid;grid-template-columns:1fr 1fr;gap:2.5rem;margin:.5rem 0 1.25rem}
.pz-mlines{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;overflow:visible;z-index:0}
.pz-mcol{display:flex;flex-direction:column;gap:.6rem;position:relative;z-index:1}
.pz-mitem{display:flex;align-items:center;gap:.6rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:10px;padding:.75rem .85rem;color:#e6e6ef;font-size:.95rem;cursor:pointer;font-family:inherit;text-align:left;transition:border-color .15s,background .15s,box-shadow .15s}
.pz-mcol-r .pz-mitem{flex-direction:row;text-align:right}
.pz-mcol-l .pz-mitem{justify-content:space-between}
.pz-mcol-r .pz-mitem{justify-content:flex-start}
.pz-mtext{flex:1;min-width:0}
.pz-mitem:hover{border-color:rgba(108,99,255,.5)}
.pz-mitem.active{border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.3)}
.pz-mitem.linked{background:rgba(255,255,255,.1)}
.pz-mdot{width:13px;height:13px;border-radius:50%;background:rgba(255,255,255,.28);border:2px solid rgba(255,255,255,.15);flex-shrink:0;transition:background .15s}
@media(max-width:680px){.pz-match{gap:1.5rem}.pz-mitem{padding:.6rem .55rem;font-size:.85rem;gap:.4rem}}
.pz-hotspot{position:relative;display:inline-block;max-width:100%;line-height:0;margin:.25rem 0 1rem}
.pz-hsimg{display:block;max-width:100%;height:auto;border-radius:10px;border:1px solid rgba(255,255,255,.12);-webkit-user-select:none;user-select:none}
.pz-hslayer{position:absolute;inset:0;cursor:crosshair}
.hs-marker{position:absolute;width:28px;height:28px;transform:translate(-50%,-50%);border-radius:50%;border:3px solid #6c63ff;background:rgba(108,99,255,.35);box-shadow:0 0 0 2px rgba(0,0,0,.35);cursor:pointer}
.hs-marker.hit{border-color:#34d399;background:rgba(16,185,129,.45)}
.hs-marker.miss{border-color:#f87171;background:rgba(239,68,68,.4)}
.pz-hsprog{font-size:.85rem;color:rgba(255,255,255,.55);margin-bottom:.4rem}
.pz-access{display:flex;justify-content:center;flex-wrap:wrap;gap:.55rem;margin:.5rem 0 1.1rem}
.pz-box{width:54px;height:64px;text-align:center;font-family:'Courier New',monospace;font-size:1.7rem;font-weight:700;background:rgba(0,0,0,.3);border:1px solid rgba(108,99,255,.3);border-radius:10px;color:#e6e6ef;caret-color:#6c63ff}
.pz-box:focus{outline:none;border-color:#6c63ff;box-shadow:0 0 0 3px rgba(108,99,255,.22)}
.pz-box.filled{background:rgba(108,99,255,.18)}
@media(max-width:480px){.pz-box{width:42px;height:54px;font-size:1.35rem}}
.pz-readout{margin:1.1rem 0;min-height:3.2rem;display:flex;align-items:center;justify-content:center;font-family:'Courier New',monospace;font-size:1.6rem;letter-spacing:.22em;text-transform:uppercase;background:rgba(0,0,0,.3);border:1px solid rgba(108,99,255,.25);border-radius:10px;padding:.8rem 1rem;word-break:break-word;text-align:center}
.pz-readout .pz-ro-empty{font-family:'Inter',system-ui,sans-serif;font-size:.92rem;letter-spacing:normal;text-transform:none;color:rgba(255,255,255,.35)}
.pz-readout .pz-pending{color:#b3acff;border-bottom:2px solid #6c63ff}
.pz-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;max-width:340px;margin:0 auto}
.pz-key{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.1rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:12px;color:#e6e6ef;padding:.7rem 0;cursor:pointer;font-family:inherit;transition:transform .08s,background .15s,border-color .15s}
.pz-key:hover{border-color:rgba(108,99,255,.6)}
.pz-key:active,.pz-key.tapped{transform:scale(.94);background:rgba(108,99,255,.28)}
.pz-key .d{font-size:1.35rem;font-weight:700;line-height:1}
.pz-key .l{font-size:.62rem;letter-spacing:.12em;color:rgba(255,255,255,.45);min-height:.8rem}
.pz-key-fn .d{font-size:1rem}
.pz-actions{margin-top:1.5rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
.pz-btn{background:#6c63ff;color:#fff;border:none;border-radius:10px;padding:.8rem 1.8rem;font-size:1rem;font-weight:600;cursor:pointer;font-family:inherit}
.pz-btn:hover{filter:brightness(1.12)}
.pz-msg{padding:.85rem 1.1rem;border-radius:10px;font-size:.92rem;margin-bottom:1.25rem}
.pz-msg.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.pz-msg.ok{background:rgba(16,185,129,.14);border:1px solid rgba(16,185,129,.35);color:#6ee7b7}
.pz-reward{margin-top:2rem;background:rgba(255,255,255,.05);border:1px solid rgba(108,99,255,.3);border-radius:16px;padding:2rem;position:relative;overflow:hidden}
.pz-reward .badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(16,185,129,.14);border:1px solid rgba(16,185,129,.35);color:#6ee7b7;font-size:.8rem;font-weight:600;padding:.4rem .85rem;border-radius:20px;margin-bottom:1rem}
.pz-rich{line-height:1.8}
.pz-rich p{margin-bottom:.85rem}
.pz-rich h1,.pz-rich h2,.pz-rich h3{margin:1.1rem 0 .5rem}
.pz-rich ul,.pz-rich ol{padding-left:1.5rem;margin-bottom:.85rem}
.pz-rich a{color:#b3acff}
.pz-media{margin-top:1.25rem}
.pz-img,.pz-vid{width:100%;max-height:60vh;object-fit:contain;border-radius:10px;display:block}
.pz-file{display:inline-block;padding:.7rem 1.1rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#e6e6ef;text-decoration:none}
.pz-cap{font-size:.82rem;color:rgba(255,255,255,.4);margin-top:.5rem;text-align:center}
.pz-foot{text-align:center;margin-top:3rem;font-size:.78rem;color:rgba(255,255,255,.22)}
/* "Back to Puzzle" link: mobile only (desktop opens puzzles in their own tab). */
.pz-back{display:none}
@media(max-width:680px){.pz-grid{grid-template-columns:1fr}.pz-itemsfirst > .pz-card:last-child{order:-1}
.pz-back{display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem;color:#b3acff;text-decoration:none;font-size:.92rem;font-weight:600;background:rgba(108,99,255,.12);border:1px solid rgba(108,99,255,.3);border-radius:8px;padding:.55rem .9rem}}
/* Accessibility */
.sr-only{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.skip-link{position:absolute;left:8px;top:-52px;z-index:100;background:#6c63ff;color:#fff;padding:.65rem 1.1rem;border-radius:0 0 10px 10px;text-decoration:none;font-weight:600;transition:top .15s}
.skip-link:focus{top:0}
a:focus-visible,button:focus-visible,input:focus-visible,[tabindex]:focus-visible,[role=button]:focus-visible{outline:3px solid #ffd166;outline-offset:2px}
.pz-help{font-size:.82rem;color:rgba(255,255,255,.5);margin-bottom:.75rem}
.pz-item:focus-visible{outline:3px solid #ffd166;outline-offset:2px;border-color:#6c63ff}
#pz-main:focus,#pz-main:focus-visible{outline:none}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.001ms!important;animation-iteration-count:1!important;transition-duration:.001ms!important}}
.pt-panel{border:1px dashed rgba(255,209,102,.6);background:rgba(255,209,102,.08);border-radius:10px;margin-bottom:1.5rem;font-size:.9rem;text-align:left}
.pt-panel>summary{cursor:pointer;padding:.65rem 1rem;color:#ffd166;font-weight:600;list-style:none}
.pt-panel>summary::-webkit-details-marker{display:none}
.pt-body{padding:0 1rem 1rem}
.pt-note{color:rgba(255,255,255,.6);margin-bottom:.6rem;font-size:.82rem}
.pt-codes{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.35rem}
.pt-codes code{background:rgba(0,0,0,.35);padding:.15rem .5rem;border-radius:5px;font-family:'Courier New',monospace;color:#ffe9a8;letter-spacing:.05em}
</style>
</head>
<body>
<a href="#pz-main" class="skip-link">Skip to puzzle</a>
<div class="sr-only" id="pz-live" aria-live="polite" aria-atomic="true"></div>
<main class="pz-wrap" id="pz-main" tabindex="-1">
  <?php if (!empty($backUrl)): ?>
  <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="pz-back">&#8592; Back to Mystery</a>
  <?php endif; ?>
  <h1 class="pz-title"><?= htmlspecialchars($pzTitle, ENT_QUOTES, 'UTF-8') ?></h1>

  <?php if (isAdmin()): ?>
  <!-- Admin play-test panel (only visible to logged-in admins) -->
  <details class="pt-panel">
    <summary>🛠️ Play-test mode — tap to reveal the answer</summary>
    <div class="pt-body">
      <p class="pt-note">You're logged in as admin. The solution to this puzzle:</p>
      <ul class="pt-codes">
        <?php
        $ptEsc = static fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        switch ($puzzleType) {
            case 'access':
            case 'phone':
                echo '<li><strong>Code:</strong> <code>' . $ptEsc($data['code'] ?? '(none set)') . '</code></li>';
                break;
            case 'caesar':
                echo '<li><strong>Decoded phrase:</strong> <code>' . $ptEsc($data['phrase'] ?? '(none set)') . '</code>'
                   . (isset($data['shift']) ? ' <span class="pt-note">(shift ' . (int)$data['shift'] . ')</span>' : '') . '</li>';
                break;
            case 'wordsearch':
                $ptWords = array_values(array_filter(array_map('trim', (array)($data['words'] ?? []))));
                echo '<li><strong>Words to find:</strong> <code>' . $ptEsc(implode(', ', $ptWords)) . '</code></li>';
                echo '<li><strong>Hidden phrase:</strong> <code>' . $ptEsc($data['phrase'] ?? '(none set)') . '</code></li>';
                break;
            case 'elim':
                $ptElim = array_map('strval', (array)($data['eliminate'] ?? []));
                echo '<li><strong>Eliminate:</strong> <code>' . $ptEsc(implode(', ', $ptElim) ?: '(none set)') . '</code></li>';
                break;
            case 'match':
                echo '<li><strong>Correct pairs:</strong></li>';
                foreach ((array)($data['pairs'] ?? []) as $p) {
                    echo '<li><code>' . $ptEsc($p['l'] ?? '') . ' → ' . $ptEsc($p['r'] ?? '') . '</code></li>';
                }
                break;
            case 'hotspot':
                $ptSpots = (array)($data['spots'] ?? []);
                echo '<li><strong>' . count($ptSpots) . ' spot' . (count($ptSpots) === 1 ? '' : 's') . ' to click:</strong></li>';
                foreach ($ptSpots as $si => $sp) {
                    echo '<li><code>#' . ($si + 1) . ' — ' . round((float)($sp['x'] ?? 0)) . '% across, ' . round((float)($sp['y'] ?? 0)) . '% down</code></li>';
                }
                break;
            case 'fillblank':
                preg_match_all('/\{([^{}]+)\}/', (string)($data['text'] ?? ''), $ptFbMatch);
                $ptFb = array_map('trim', $ptFbMatch[1] ?? []);
                echo '<li><strong>Difficulty:</strong> <code>' . $ptEsc(($data['difficulty'] ?? 'easy') === 'hard' ? 'Hard (typed)' : 'Easy (word bank)') . '</code></li>';
                echo '<li><strong>Blanks (in order):</strong></li>';
                foreach ($ptFb as $bi => $bw) {
                    echo '<li><code>#' . ($bi + 1) . ' — ' . $ptEsc($bw) . '</code></li>';
                }
                break;
            default: // order
                echo '<li><strong>Correct order:</strong></li>';
                foreach ((array)($data['items'] ?? []) as $idx => $it) {
                    echo '<li><code>' . ($idx + 1) . '. ' . $ptEsc($it) . '</code></li>';
                }
        }
        ?>
      </ul>
    </div>
  </details>
  <?php endif; ?>
  <?php if ($pzPrompt !== ''): ?>
  <p class="pz-prompt"><?= htmlspecialchars($pzPrompt, ENT_QUOTES, 'UTF-8') ?></p>
  <?php elseif ($puzzleType === 'phone'): ?>
  <p class="pz-prompt">Use the keypad to translate the code below, then press Submit.</p>
  <?php elseif ($puzzleType === 'access'): ?>
  <p class="pz-prompt">Use the clues to figure out the access code, then enter it below and press Submit.</p>
  <?php elseif ($puzzleType === 'elim'): ?>
  <p class="pz-prompt">Use the clues to eliminate the wrong items. Click an item to cross it off &mdash; click again to bring it back. When you&rsquo;re sure, press Submit.</p>
  <?php elseif ($puzzleType === 'wordsearch'): ?>
  <p class="pz-prompt">Find every word from the list by dragging across the letters (across, down, and diagonally). Once they&rsquo;re all found, read the leftover letters top to bottom &mdash; they spell a hidden phrase that ends at the <strong>ZZ</strong>. Type the phrase in to finish.</p>
  <?php elseif ($puzzleType === 'match'): ?>
  <p class="pz-prompt">Use the clues to connect each item on the left to its match on the right. Tap one item, then tap its partner. When every pair is connected, press Submit.</p>
  <?php elseif ($puzzleType === 'hotspot'): ?>
  <p class="pz-prompt">Click the image to mark each hidden spot. Place all <?= (int)$hsCount ?> marker<?= $hsCount === 1 ? '' : 's' ?>, then press Submit. Click a marker to remove it.</p>
  <?php elseif ($puzzleType === 'caesar'): ?>
  <p class="pz-prompt">Decode the message below, then press Submit.</p>
  <?php else: ?>
  <p class="pz-prompt">Drag the items into the correct order, then press Submit.</p>
  <?php endif; ?>

  <?php if ($wrong): ?>
  <div class="pz-msg err" role="alert">&#9888; <?php
      if ($puzzleType === 'caesar') {
          echo "That's not the right answer &mdash; try decoding it again.";
      } elseif ($puzzleType === 'phone') {
          echo "That's not the right code &mdash; check the keypad and try again.";
      } elseif ($puzzleType === 'access') {
          echo "That's not the right access code &mdash; check the clues and try again.";
      } elseif ($puzzleType === 'elim') {
          echo "Those aren't the right items to eliminate &mdash; re-read the clues and try again.";
      } elseif ($puzzleType === 'wordsearch') {
          echo "That's not the hidden phrase &mdash; read the leftover letters again (the phrase ends at the ZZ) and try once more.";
      } elseif ($puzzleType === 'match') {
          echo "Those connections aren't all correct &mdash; re-read the clues and try again.";
      } elseif ($puzzleType === 'hotspot') {
          echo "Not every spot is marked correctly &mdash; the green markers were close, the red ones missed.";
      } else {
          echo "That's not correct. Try again.";
      } ?></div>
  <?php endif; ?>

  <?php if (!$solved && $puzzleType === 'hotspot'): ?>
  <?php $hsUrl = $hsImage !== '' ? UPLOAD_URL . '/' . $hsImage : ''; ?>
  <?php if (!empty($clues)): ?>
  <div class="pz-card">
    <h2>Clues</h2>
    <ol class="pz-clues">
      <?php foreach ($clues as $c): ?>
      <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ol>
  </div>
  <?php endif; ?>
  <div class="pz-card" style="grid-column:1/-1">
    <h2>Mark the Spots</h2>
    <div class="pz-hsprog" id="pz-hsprog">0 of <?= (int)$hsCount ?> marked</div>
    <div class="pz-hotspot" id="pz-hotspot" data-count="<?= (int)$hsCount ?>">
      <?php if ($hsUrl !== ''): ?>
      <img src="<?= htmlspecialchars($hsUrl, ENT_QUOTES, 'UTF-8') ?>" class="pz-hsimg" alt="Find the hidden spots" draggable="false">
      <?php endif; ?>
      <div class="pz-hslayer" id="pz-hslayer">
        <?php foreach ($hsClicks as $cl): ?>
        <span class="hs-marker <?= !empty($cl['hit']) ? 'hit' : 'miss' ?>"
              data-x="<?= (float)$cl['x'] ?>" data-y="<?= (float)$cl['y'] ?>"
              style="left:<?= (float)$cl['x'] ?>%;top:<?= (float)$cl['y'] ?>%"></span>
        <?php endforeach; ?>
      </div>
    </div>
    <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
      <?= csrf_field() ?>
      <input type="hidden" name="hotspot_clicks" id="pz-hsclicks">
      <div class="pz-actions">
        <button type="button" class="pz-btn pz-btn-ghost" id="pz-hsreset">Clear</button>
        <button type="submit" class="pz-btn">Submit</button>
      </div>
    </form>
  </div>
  <?php elseif (!$solved && $puzzleType === 'match'): ?>
  <div class="pz-card">
    <h2>Make the Connections</h2>
    <div class="pz-match" id="pz-match">
      <svg class="pz-mlines" id="pz-mlines" aria-hidden="true"></svg>
      <div class="pz-mcol pz-mcol-l">
        <?php foreach ($matchLeft as $i => $l): ?>
        <button type="button" class="pz-mitem" data-side="l" data-li="<?= $i ?>">
          <span class="pz-mtext"><?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="pz-mdot" aria-hidden="true"></span>
        </button>
        <?php endforeach; ?>
      </div>
      <div class="pz-mcol pz-mcol-r">
        <?php foreach ($matchRight as $rr): ?>
        <button type="button" class="pz-mitem" data-side="r" data-rid="<?= (int)$rr['i'] ?>">
          <span class="pz-mdot" aria-hidden="true"></span>
          <span class="pz-mtext"><?= htmlspecialchars($rr['v'], ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
      <?= csrf_field() ?>
      <input type="hidden" name="connections" id="pz-connections">
      <div class="pz-actions">
        <button type="button" class="pz-btn pz-btn-ghost" id="pz-mreset">Reset</button>
        <button type="submit" class="pz-btn">Submit</button>
      </div>
    </form>
  </div>
  <?php if (!empty($clues)): ?>
  <div class="pz-card">
    <h2>Clues</h2>
    <ol class="pz-clues">
      <?php foreach ($clues as $c): ?>
      <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ol>
  </div>
  <?php endif; ?>
  <?php elseif (!$solved && $puzzleType === 'wordsearch'): ?>
  <div class="pz-grid">
    <div class="pz-card">
      <h2>Words to Find</h2>
      <ul class="pz-wordlist" id="pz-wordlist">
        <?php foreach ($wsList as $w): ?>
        <li data-word="<?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?>" class="<?= $wsWordsFound ? 'found' : '' ?>"><?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="pz-wsprog" id="pz-wsprog"><?= $wsWordsFound ? 'All words found &mdash; read the leftover letters!' : '0 of ' . count($wsList) . ' found' ?></div>
    </div>
    <div class="pz-card">
      <h2>Word Search</h2>
      <?php
        // The word list is shown to the player anyway, so exposing it for
        // client-side match-detection leaks nothing. The hidden phrase (the real
        // answer) is never sent — only its letters appear in the grid to be read.
        $wsJson = htmlspecialchars(json_encode(array_values($wsList)), ENT_QUOTES, 'UTF-8');
      ?>
      <div class="pz-ws" id="pz-ws" data-count="<?= count($wsList) ?>" data-words="<?= $wsJson ?>"
           style="grid-template-columns:repeat(<?= (int)$wsSize ?>,1fr)">
        <?php foreach ($wsGrid as $r => $row): ?>
          <?php foreach ($row as $c => $ch): ?>
          <button type="button" class="pz-cell" data-r="<?= $r ?>" data-c="<?= $c ?>"><?= htmlspecialchars((string)$ch, ENT_QUOTES, 'UTF-8') ?></button>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
      <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
        <?= csrf_field() ?>
        <input type="hidden" name="wordsfound" id="pz-wordsfound" value="<?= $wsWordsFound ? '1' : '' ?>">
        <div class="pz-wsfinal <?= $wsWordsFound ? '' : 'pz-hide' ?>" id="pz-wsfinal">
          <label class="pz-lbl" for="pz-ws-answer">All words found! Read the leftover letters top to bottom (the phrase ends at the ZZ) and type the hidden phrase.</label>
          <input type="text" name="answer" id="pz-ws-answer" class="pz-input" autocomplete="off"
                 autocorrect="off" spellcheck="false" placeholder="Type the hidden phrase…">
          <div class="pz-actions">
            <button type="submit" class="pz-btn">Submit Phrase</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <?php elseif (!$solved && $puzzleType === 'elim'): ?>
  <div class="pz-grid pz-itemsfirst">
    <?php if (!empty($clues)): ?>
    <div class="pz-card">
      <h2>Clues</h2>
      <ol class="pz-clues">
        <?php foreach ($clues as $c): ?>
        <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
    <div class="pz-card" <?= empty($clues) ? 'style="grid-column:1/-1"' : '' ?>>
      <h2 id="pz-elim-label"><?= htmlspecialchars($elimHeading, ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="pz-help">Activate an item with Enter or Space to cross it off; activate again to bring it back.</p>
      <ul class="pz-elim" id="pz-elim" aria-labelledby="pz-elim-label">
        <?php foreach ($items as $it): ?>
        <li class="pz-eitem" data-val="<?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?>" role="button" tabindex="0" aria-pressed="false"
            aria-label="Eliminate <?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?>">
          <span class="pz-emark" aria-hidden="true">&#10005;</span>
          <span class="pz-etext"><?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
        <?= csrf_field() ?>
        <input type="hidden" name="eliminated" id="pz-eliminated">
        <div class="pz-actions">
          <button type="submit" class="pz-btn">Submit</button>
        </div>
      </form>
    </div>
  </div>
  <?php elseif (!$solved && $puzzleType === 'access'): ?>
  <div class="pz-grid">
    <?php if (!empty($clues)): ?>
    <div class="pz-card">
      <h2>Clues</h2>
      <ol class="pz-clues">
        <?php foreach ($clues as $c): ?>
        <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
    <div class="pz-card" <?= empty($clues) ? 'style="grid-column:1/-1"' : '' ?>>
      <h2>Access Code</h2>
      <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
        <?= csrf_field() ?>
        <div class="pz-access" id="pz-access" data-len="<?= (int)$accessLen ?>"
             role="group" aria-label="Access code, <?= (int)$accessLen ?> digits">
          <?php for ($i = 0; $i < $accessLen; $i++): ?>
          <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1"
                 class="pz-box" autocomplete="off" aria-label="Digit <?= $i + 1 ?> of <?= (int)$accessLen ?>">
          <?php endfor; ?>
        </div>
        <input type="hidden" name="answer" id="pz-answer-h">
        <div class="pz-actions">
          <button type="submit" class="pz-btn">Submit</button>
        </div>
      </form>
    </div>
  </div>
  <?php elseif (!$solved && $puzzleType === 'phone'): ?>
  <div class="pz-card" style="grid-column:1/-1">
    <h2>Coded Message</h2>
    <div class="pz-cipher" style="text-align:center" aria-label="Coded message: <?= htmlspecialchars($phoneClue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($phoneClue, ENT_QUOTES, 'UTF-8') ?></div>
    <span class="sr-only" id="pz-readout-label">Your answer so far</span>
    <div class="pz-readout" id="pz-readout" role="status" aria-live="polite" aria-labelledby="pz-readout-label"><span class="pz-ro-empty">Tap the keys to spell it out…</span></div>
    <div class="pz-pad" id="pz-pad" role="group" aria-label="Phone keypad">
      <button type="button" class="pz-key" data-d="1"><span class="d">1</span><span class="l">&nbsp;</span></button>
      <button type="button" class="pz-key" data-d="2" data-l="ABC"><span class="d">2</span><span class="l">ABC</span></button>
      <button type="button" class="pz-key" data-d="3" data-l="DEF"><span class="d">3</span><span class="l">DEF</span></button>
      <button type="button" class="pz-key" data-d="4" data-l="GHI"><span class="d">4</span><span class="l">GHI</span></button>
      <button type="button" class="pz-key" data-d="5" data-l="JKL"><span class="d">5</span><span class="l">JKL</span></button>
      <button type="button" class="pz-key" data-d="6" data-l="MNO"><span class="d">6</span><span class="l">MNO</span></button>
      <button type="button" class="pz-key" data-d="7" data-l="PQRS"><span class="d">7</span><span class="l">PQRS</span></button>
      <button type="button" class="pz-key" data-d="8" data-l="TUV"><span class="d">8</span><span class="l">TUV</span></button>
      <button type="button" class="pz-key" data-d="9" data-l="WXYZ"><span class="d">9</span><span class="l">WXYZ</span></button>
      <button type="button" class="pz-key pz-key-fn" id="pz-clear"><span class="d">&#9003;</span><span class="l">CLEAR</span></button>
      <button type="button" class="pz-key" data-d="0" data-l="space"><span class="d">0</span><span class="l">&#9251;</span></button>
      <button type="button" class="pz-key pz-key-fn" id="pz-back"><span class="d">&#11013;</span><span class="l">DELETE</span></button>
    </div>
    <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
      <?= csrf_field() ?>
      <input type="hidden" name="answer" id="pz-answer-h">
      <div class="pz-actions">
        <button type="submit" class="pz-btn">Submit</button>
      </div>
    </form>
  </div>
  <?php elseif (!$solved && $puzzleType === 'caesar'): ?>
  <div class="pz-card" style="grid-column:1/-1">
    <h2>Encoded Message</h2>
    <div class="pz-cipher"><?= htmlspecialchars($cipher, ENT_QUOTES, 'UTF-8') ?></div>
    <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
      <?= csrf_field() ?>
      <label class="pz-lbl" for="pz-answer">Your decoded answer</label>
      <input type="text" name="answer" id="pz-answer" class="pz-input" autocomplete="off"
             autocorrect="off" spellcheck="false" placeholder="Type the decoded phrase…">
      <div class="pz-actions">
        <button type="submit" class="pz-btn">Submit</button>
      </div>
    </form>
  </div>
  <?php elseif (!$solved && $puzzleType === 'fillblank'): ?>
  <div class="pz-card" style="grid-column:1/-1">
    <h2>Fill in the Blanks</h2>
    <p class="pz-help" id="pz-fb-help">
      <?php if ($fbDifficulty === 'easy'): ?>
        Tap a word from the bank, then tap a blank to place it (or drag &amp; drop on desktop). Tap a filled blank to return its word.
      <?php else: ?>
        Type the missing word in each blank. Matching is case-insensitive.
      <?php endif; ?>
    </p>
    <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
      <?= csrf_field() ?>
      <div class="pz-fb-text" id="pz-fb-text">
        <?php foreach ($fbPieces as $piece):
          if (isset($piece['text'])):
            echo htmlspecialchars($piece['text'], ENT_QUOTES, 'UTF-8');
          else:
            $bi = (int)$piece['blank'];
            if ($fbDifficulty === 'easy'): ?>
              <span class="pz-fb-blank" data-blank="<?= $bi ?>" tabindex="0" role="button"
                    aria-label="Blank <?= $bi + 1 ?>"></span><input type="hidden" name="blanks[<?= $bi ?>]" value="">
            <?php else: ?>
              <input type="text" name="blanks[<?= $bi ?>]" class="pz-fb-input"
                     autocomplete="off" autocorrect="off" spellcheck="false"
                     aria-label="Blank <?= $bi + 1 ?>" maxlength="60">
            <?php endif;
          endif;
        endforeach; ?>
      </div>

      <?php if ($fbDifficulty === 'easy'): ?>
      <div class="pz-fb-bank-wrap">
        <h3>Word Bank</h3>
        <ul class="pz-fb-bank" id="pz-fb-bank" aria-label="Words available to place">
          <?php foreach ($fbBank as $w): ?>
          <li class="pz-fb-word" draggable="true" tabindex="0" role="button"
              data-word="<?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?>"
              aria-label="<?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?> — tap a blank to place"><?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="pz-actions">
        <button type="submit" class="pz-btn">Submit</button>
        <?php if ($fbDifficulty === 'easy'): ?>
        <button type="button" class="pz-btn pz-btn-ghost" id="pz-fb-reset">Reset</button>
        <?php endif; ?>
      </div>
    </form>
    <?php if (!empty($clues)): ?>
    <div class="pz-fb-clues">
      <h3>Clues</h3>
      <ol class="pz-clues">
        <?php foreach ($clues as $c): ?>
        <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
  </div>
  <?php elseif (!$solved): ?>
  <div class="pz-grid pz-itemsfirst">
    <?php if (!empty($clues)): ?>
    <div class="pz-card">
      <h2>Order Clues</h2>
      <ol class="pz-clues">
        <?php foreach ($clues as $c): ?>
        <li><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
    <div class="pz-card" <?= empty($clues) ? 'style="grid-column:1/-1"' : '' ?>>
      <h2>Arrange in Order</h2>
      <p class="pz-help" id="pz-order-help">Drag items to reorder, or focus an item and use the Up and Down arrow keys to move it.</p>
      <ul class="pz-sortable" id="pz-sortable" aria-label="Items to arrange in order" aria-describedby="pz-order-help">
        <?php foreach ($items as $i => $it): ?>
        <li class="pz-item" draggable="true" tabindex="0" data-val="<?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?>, position <?= $i + 1 ?> of <?= count($items) ?>">
          <span class="grip" aria-hidden="true">&#9776;</span>
          <span><?= htmlspecialchars($it, ENT_QUOTES, 'UTF-8') ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <form method="POST" action="<?= htmlspecialchars(url('z/' . $pzSlug), ENT_QUOTES, 'UTF-8') ?>" id="pz-form">
        <?= csrf_field() ?>
        <input type="hidden" name="order" id="pz-order">
        <div class="pz-actions">
          <button type="submit" class="pz-btn">Submit</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($solved): ?>
  <div class="pz-msg ok" role="status">&#9989; Correct! You solved the puzzle.</div>
  <div class="pz-reward">
    <div class="badge">&#129513; Puzzle Solved</div>
    <?php if (trim(strip_tags(str_replace('&nbsp;', ' ', $pzReward))) !== ''): ?>
    <div class="pz-rich"><?= $pzReward ?></div>
    <?php endif; ?>
    <?php if ($pzRUrl !== ''): ?>
    <div class="pz-media">
      <?= pzMedia($pzRType, $pzRUrl) ?>
      <?php if ($pzRCap !== ''): ?><div class="pz-cap"><?= htmlspecialchars($pzRCap, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (trim(strip_tags(str_replace('&nbsp;', ' ', $pzReward))) === '' && $pzRUrl === ''): ?>
    <p class="pz-rich">Well done — you cracked it!</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="pz-foot">Powered by <a href="https://simplycreativegames.etsy.com" target="_blank" rel="noopener" style="color:#b3acff;text-decoration:none">Simply Creative Games</a></div>
</main>

<?php if (!$solved): ?>
<script>
// Announce a message to assistive tech via the page's live region.
function pzAnnounce(msg) {
  var live = document.getElementById('pz-live');
  if (!live) return;
  live.textContent = '';
  setTimeout(function () { live.textContent = msg; }, 60);
}

// ── Fill-in-the-Blank: Easy mode tap-to-place + HTML5 drag-and-drop ────────
(function () {
  var bank = document.getElementById('pz-fb-bank');
  var form = document.getElementById('pz-form');
  if (!bank || !form) return; // Hard mode or non-fillblank puzzle: no bank, no JS needed.
  var blanks = [].slice.call(document.querySelectorAll('#pz-fb-text .pz-fb-blank'));
  var hiddens = {};
  form.querySelectorAll('input[type=hidden][name^="blanks["]').forEach(function (h) {
    var m = h.name.match(/\[(\d+)\]/);
    if (m) hiddens[m[1]] = h;
  });

  var selectedWord = null;
  function clearSelection() {
    if (selectedWord) { selectedWord.classList.remove('selected'); }
    selectedWord = null;
  }
  function findHidden(word) {
    return [].slice.call(bank.querySelectorAll('.pz-fb-word.hidden')).find(function (el) {
      return el.dataset.word === word;
    });
  }
  function findVisible(word) {
    return [].slice.call(bank.querySelectorAll('.pz-fb-word:not(.hidden)')).find(function (el) {
      return el.dataset.word === word;
    });
  }
  function unplace(blankEl) {
    if (!blankEl.dataset.word) { return; }
    var word = blankEl.dataset.word;
    blankEl.textContent = '';
    delete blankEl.dataset.word;
    blankEl.classList.remove('filled');
    hiddens[blankEl.dataset.blank].value = '';
    var w = findHidden(word);
    if (w) { w.classList.remove('hidden'); }
  }
  function placeAt(blankEl, wordEl) {
    if (!blankEl || !wordEl) { return; }
    if (blankEl.dataset.word) { unplace(blankEl); }
    var word = wordEl.dataset.word;
    blankEl.textContent = word;
    blankEl.dataset.word = word;
    blankEl.classList.add('filled');
    hiddens[blankEl.dataset.blank].value = word;
    wordEl.classList.remove('selected');
    wordEl.classList.add('hidden');
    if (selectedWord === wordEl) { selectedWord = null; }
  }

  // Tap a word in the bank to select it.
  bank.addEventListener('click', function (e) {
    var w = e.target.closest('.pz-fb-word');
    if (!w || w.classList.contains('hidden')) { return; }
    if (selectedWord === w) { clearSelection(); return; }
    clearSelection();
    selectedWord = w;
    w.classList.add('selected');
  });
  bank.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') { return; }
    var w = e.target.closest('.pz-fb-word');
    if (!w) { return; }
    e.preventDefault(); w.click();
  });

  // Tap a blank to place the selected word, or unplace what's already there.
  blanks.forEach(function (b) {
    b.addEventListener('click', function () {
      if (selectedWord) { placeAt(b, selectedWord); }
      else if (b.dataset.word) { unplace(b); }
    });
    b.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') { return; }
      e.preventDefault(); b.click();
    });
    // Drag-and-drop target.
    b.addEventListener('dragover', function (e) {
      if (e.dataTransfer && e.dataTransfer.types.indexOf('text/plain') === -1) { return; }
      e.preventDefault();
      b.classList.add('target');
    });
    b.addEventListener('dragleave', function () { b.classList.remove('target'); });
    b.addEventListener('drop', function (e) {
      e.preventDefault();
      b.classList.remove('target');
      var word = e.dataTransfer.getData('text/plain');
      var src = findVisible(word);
      if (src) { placeAt(b, src); }
    });
  });

  // Drag the bank words.
  bank.addEventListener('dragstart', function (e) {
    var w = e.target.closest('.pz-fb-word');
    if (!w) { return; }
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', w.dataset.word);
    w.classList.add('dragging');
  });
  bank.addEventListener('dragend', function (e) {
    var w = e.target.closest('.pz-fb-word');
    if (w) { w.classList.remove('dragging'); }
  });

  // Reset button.
  var reset = document.getElementById('pz-fb-reset');
  if (reset) {
    reset.addEventListener('click', function () {
      blanks.forEach(function (b) { if (b.dataset.word) { unplace(b); } });
      clearSelection();
    });
  }
})();

(function () {
  var list = document.getElementById('pz-sortable');
  var form = document.getElementById('pz-form');
  if (!list || !form) return;
  var dragged = null;

  // Keep each item's aria-label position in sync after a reorder.
  function refreshPositions() {
    var items = [].slice.call(list.querySelectorAll('.pz-item'));
    items.forEach(function (li, i) {
      var text = li.getAttribute('data-val') || '';
      li.setAttribute('aria-label', text + ', position ' + (i + 1) + ' of ' + items.length);
    });
  }

  // Keyboard reordering: Up/Down move the focused item one slot.
  list.addEventListener('keydown', function (e) {
    if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
    var li = e.target.closest('.pz-item');
    if (!li) return;
    e.preventDefault();
    var items = [].slice.call(list.querySelectorAll('.pz-item'));
    var idx = items.indexOf(li);
    if (e.key === 'ArrowUp' && idx > 0) {
      list.insertBefore(li, items[idx - 1]);
    } else if (e.key === 'ArrowDown' && idx < items.length - 1) {
      list.insertBefore(items[idx + 1], li);
    } else {
      return;
    }
    li.focus();
    refreshPositions();
    var newIdx = [].slice.call(list.querySelectorAll('.pz-item')).indexOf(li);
    pzAnnounce((li.getAttribute('data-val') || 'Item') + ' moved to position ' + (newIdx + 1) + ' of ' + items.length);
  });

  list.addEventListener('dragstart', function (e) {
    var li = e.target.closest('.pz-item');
    if (!li) return;
    dragged = li;
    li.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  list.addEventListener('dragend', function () {
    if (dragged) dragged.classList.remove('dragging');
    dragged = null;
  });
  list.addEventListener('dragover', function (e) {
    e.preventDefault();
    if (!dragged) return;
    var after = getAfter(list, e.clientY);
    if (after == null) list.appendChild(dragged);
    else list.insertBefore(dragged, after);
  });

  // Touch reordering for phones/tablets (HTML5 drag-and-drop doesn't fire on touch).
  var touchItem = null;
  list.addEventListener('touchstart', function (e) {
    var li = e.target.closest('.pz-item');
    if (!li) return;
    touchItem = li;
    li.classList.add('dragging');
  }, { passive: true });
  list.addEventListener('touchmove', function (e) {
    if (!touchItem || !e.touches.length) return;
    e.preventDefault(); // keep the page from scrolling while dragging an item
    var after = getAfter(list, e.touches[0].clientY);
    if (after == null) list.appendChild(touchItem);
    else if (after !== touchItem) list.insertBefore(touchItem, after);
  }, { passive: false });
  function endTouch() {
    if (!touchItem) return;
    var moved = touchItem;
    moved.classList.remove('dragging');
    touchItem = null;
    refreshPositions();
    var idx = [].slice.call(list.querySelectorAll('.pz-item')).indexOf(moved);
    var total = list.querySelectorAll('.pz-item').length;
    pzAnnounce((moved.getAttribute('data-val') || 'Item') + ' moved to position ' + (idx + 1) + ' of ' + total);
  }
  list.addEventListener('touchend', endTouch);
  list.addEventListener('touchcancel', endTouch);

  function getAfter(container, y) {
    var els = [].slice.call(container.querySelectorAll('.pz-item:not(.dragging)'));
    var closest = { offset: -Infinity, el: null };
    els.forEach(function (child) {
      var box = child.getBoundingClientRect();
      var offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) closest = { offset: offset, el: child };
    });
    return closest.el;
  }

  form.addEventListener('submit', function () {
    var order = [].slice.call(list.querySelectorAll('.pz-item'))
      .map(function (li) { return li.getAttribute('data-val'); });
    document.getElementById('pz-order').value = JSON.stringify(order);
  });
})();

(function () {
  var pad = document.getElementById('pz-pad');
  var readout = document.getElementById('pz-readout');
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-answer-h');
  if (!pad || !readout || !form || !hidden) return;

  var TAP_MS = 900;
  var committed = '';
  var pendingKey = null;   // letters string of the key being cycled
  var pendingChar = '';    // currently shown letter
  var pendingIdx = 0;
  var timer = null;

  // Give every key a clear spoken label.
  [].slice.call(pad.querySelectorAll('.pz-key')).forEach(function (k) {
    if (k.id === 'pz-clear') { k.setAttribute('aria-label', 'Clear all'); return; }
    if (k.id === 'pz-back')  { k.setAttribute('aria-label', 'Delete last character'); return; }
    var d = k.getAttribute('data-d') || '';
    var letters = k.getAttribute('data-l');
    if (d === '0') { k.setAttribute('aria-label', '0, space'); return; }
    if (letters && letters !== 'space') {
      k.setAttribute('aria-label', d + ', ' + letters.split('').join(' '));
    } else {
      k.setAttribute('aria-label', d);
    }
  });

  function render() {
    if (committed === '' && pendingChar === '') {
      readout.innerHTML = '<span class="pz-ro-empty">Tap the keys to spell it out…</span>';
      return;
    }
    var safe = committed.replace(/&/g, '&amp;').replace(/</g, '&lt;');
    var html = safe.replace(/ /g, '&middot;');
    if (pendingChar !== '') {
      html += '<span class="pz-pending">' + pendingChar + '</span>';
    }
    readout.innerHTML = html;
  }

  function commitPending() {
    if (pendingChar !== '') { committed += pendingChar; }
    pendingKey = null; pendingChar = ''; pendingIdx = 0;
    if (timer) { clearTimeout(timer); timer = null; }
  }

  pad.addEventListener('click', function (e) {
    var btn = e.target.closest('.pz-key');
    if (!btn) return;
    btn.classList.add('tapped');
    setTimeout(function () { btn.classList.remove('tapped'); }, 120);

    if (btn.id === 'pz-clear') {
      commitPending(); committed = ''; pendingChar = ''; render(); return;
    }
    if (btn.id === 'pz-back') {
      if (pendingChar !== '') { pendingKey = null; pendingChar = ''; pendingIdx = 0; if (timer) { clearTimeout(timer); timer = null; } }
      else { committed = committed.slice(0, -1); }
      render(); return;
    }

    var d = btn.getAttribute('data-d');
    if (d === '0') { commitPending(); committed += ' '; render(); return; }
    var letters = btn.getAttribute('data-l');
    if (!letters || letters === 'space') { commitPending(); render(); return; }
    letters = letters.toUpperCase();

    if (pendingKey === letters) {
      pendingIdx = (pendingIdx + 1) % letters.length;
      pendingChar = letters.charAt(pendingIdx);
    } else {
      commitPending();
      pendingKey = letters; pendingIdx = 0; pendingChar = letters.charAt(0);
    }
    if (timer) { clearTimeout(timer); }
    timer = setTimeout(commitPending, TAP_MS);
    render();
  });

  form.addEventListener('submit', function () {
    commitPending();
    hidden.value = committed;
  });
})();

(function () {
  var wrap = document.getElementById('pz-access');
  if (!wrap) return;
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-answer-h');
  var boxes = [].slice.call(wrap.querySelectorAll('.pz-box'));
  if (!boxes.length || !form || !hidden) return;

  function focusBox(i) {
    if (i >= 0 && i < boxes.length) { boxes[i].focus(); boxes[i].select(); }
  }

  boxes.forEach(function (b, i) {
    b.addEventListener('input', function () {
      var v = b.value.replace(/\D/g, '');
      b.value = v ? v.charAt(v.length - 1) : '';
      b.classList.toggle('filled', b.value !== '');
      if (b.value) focusBox(i + 1);
    });
    b.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && b.value === '' && i > 0) {
        e.preventDefault();
        boxes[i - 1].value = '';
        boxes[i - 1].classList.remove('filled');
        focusBox(i - 1);
      } else if (e.key === 'ArrowLeft' && i > 0) { e.preventDefault(); focusBox(i - 1); }
      else if (e.key === 'ArrowRight' && i < boxes.length - 1) { e.preventDefault(); focusBox(i + 1); }
    });
    b.addEventListener('paste', function (e) {
      var txt = (e.clipboardData || window.clipboardData).getData('text') || '';
      var digits = txt.replace(/\D/g, '');
      if (!digits) return;
      e.preventDefault();
      for (var k = 0; k < digits.length && (i + k) < boxes.length; k++) {
        boxes[i + k].value = digits.charAt(k);
        boxes[i + k].classList.add('filled');
      }
      focusBox(Math.min(i + digits.length, boxes.length - 1));
    });
  });

  focusBox(0);

  form.addEventListener('submit', function () {
    hidden.value = boxes.map(function (b) { return b.value || ''; }).join('');
  });
})();

(function () {
  var list = document.getElementById('pz-elim');
  if (!list) return;
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-eliminated');
  if (!form || !hidden) return;

  function toggle(li) {
    var on = li.classList.toggle('struck');
    li.setAttribute('aria-pressed', on ? 'true' : 'false');
  }

  list.addEventListener('click', function (e) {
    var li = e.target.closest('.pz-eitem');
    if (li) toggle(li);
  });
  list.addEventListener('keydown', function (e) {
    if (e.key !== ' ' && e.key !== 'Enter') return;
    var li = e.target.closest('.pz-eitem');
    if (!li) return;
    e.preventDefault();
    toggle(li);
  });

  form.addEventListener('submit', function () {
    var picks = [].slice.call(list.querySelectorAll('.pz-eitem.struck'))
      .map(function (li) { return li.getAttribute('data-val'); });
    hidden.value = JSON.stringify(picks);
  });
})();

(function () {
  var ws = document.getElementById('pz-ws');
  var form = document.getElementById('pz-form');
  if (!ws || !form) return;
  var prog = document.getElementById('pz-wsprog');
  var listEl = document.getElementById('pz-wordlist');
  var finalBlock = document.getElementById('pz-wsfinal');
  var wordsFoundFlag = document.getElementById('pz-wordsfound');
  var answerInput = document.getElementById('pz-ws-answer');

  var targets = [];
  try { targets = JSON.parse(ws.getAttribute('data-words') || '[]'); } catch (e) { targets = []; }
  targets = targets.map(function (w) { return String(w).toUpperCase(); });
  var total = targets.length;
  var found = {};
  var foundCount = 0;

  var cellMap = {};
  [].slice.call(ws.querySelectorAll('.pz-cell')).forEach(function (el) {
    cellMap[el.getAttribute('data-r') + ',' + el.getAttribute('data-c')] = el;
  });
  function cellAt(r, c) { return cellMap[r + ',' + c] || null; }
  function coords(el) {
    return { r: parseInt(el.getAttribute('data-r'), 10), c: parseInt(el.getAttribute('data-c'), 10) };
  }
  function sign(n) { return n > 0 ? 1 : (n < 0 ? -1 : 0); }

  var selecting = false, start = null, curPath = [];

  function clearSel() {
    curPath.forEach(function (el) { el.classList.remove('sel'); });
    curPath = [];
  }
  function pathBetween(a, b) {
    var dr = b.r - a.r, dc = b.c - a.c;
    if (!(dr === 0 || dc === 0 || Math.abs(dr) === Math.abs(dc))) return null;
    var len = Math.max(Math.abs(dr), Math.abs(dc)) + 1;
    var sr = sign(dr), sc = sign(dc), out = [];
    for (var i = 0; i < len; i++) {
      var el = cellAt(a.r + sr * i, a.c + sc * i);
      if (!el) return null;
      out.push(el);
    }
    return out;
  }
  function cellFromPoint(x, y) {
    var el = document.elementFromPoint(x, y);
    return (el && el.classList && el.classList.contains('pz-cell')) ? el : null;
  }
  function markWord(word) {
    if (!listEl) return;
    var li = listEl.querySelector('li[data-word="' + word + '"]');
    if (li) li.classList.add('found');
  }
  function updateProgress() {
    if (prog && foundCount < total) { prog.textContent = foundCount + ' of ' + total + ' found'; }
  }
  function revealFinal() {
    if (prog) { prog.innerHTML = 'All words found &mdash; read the leftover letters!'; }
    if (finalBlock) { finalBlock.classList.remove('pz-hide'); }
    if (wordsFoundFlag) { wordsFoundFlag.value = '1'; }
    if (answerInput) { setTimeout(function () { answerInput.focus(); }, 60); }
    if (typeof pzAnnounce === 'function') { pzAnnounce('All words found. Read the leftover letters and type the hidden phrase.'); }
  }

  function finalize() {
    if (!selecting) return;
    selecting = false;
    if (curPath.length >= 2) {
      var str = curPath.map(function (c) { return c.textContent; }).join('').toUpperCase();
      var rev = str.split('').reverse().join('');
      var hit = null;
      for (var i = 0; i < targets.length; i++) {
        if (!found[targets[i]] && (targets[i] === str || targets[i] === rev)) { hit = targets[i]; break; }
      }
      if (hit) {
        found[hit] = true; foundCount++;
        curPath.forEach(function (c) { c.classList.add('found'); });
        markWord(hit);
        updateProgress();
        if (typeof pzAnnounce === 'function') { pzAnnounce('Found ' + hit + '. ' + foundCount + ' of ' + total + ' found.'); }
        if (foundCount >= total) { revealFinal(); }
      }
    }
    clearSel();
    start = null;
  }

  ws.addEventListener('pointerdown', function (e) {
    var el = e.target.closest('.pz-cell');
    if (!el) return;
    e.preventDefault();
    selecting = true;
    start = coords(el);
    clearSel();
    el.classList.add('sel');
    curPath = [el];
  });
  ws.addEventListener('pointermove', function (e) {
    if (!selecting) return;
    e.preventDefault();
    var el = cellFromPoint(e.clientX, e.clientY);
    if (!el) return;
    var path = pathBetween(start, coords(el));
    if (!path) return;
    clearSel();
    path.forEach(function (c) { c.classList.add('sel'); });
    curPath = path;
  });
  document.addEventListener('pointerup', finalize);
})();

(function () {
  var wrap = document.getElementById('pz-match');
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-connections');
  if (!wrap || !form || !hidden) return;
  var svg = document.getElementById('pz-mlines');
  var resetBtn = document.getElementById('pz-mreset');
  var COLORS = ['#ff6b9d', '#fbbf24', '#5bc0ff', '#4cc9a0', '#c084fc', '#fb923c'];
  var SVGNS = 'http://www.w3.org/2000/svg';

  var lefts  = [].slice.call(wrap.querySelectorAll('.pz-mcol-l .pz-mitem'));
  var rights = [].slice.call(wrap.querySelectorAll('.pz-mcol-r .pz-mitem'));
  var connections = {};   // leftIndex -> rightId
  var rightOwner  = {};   // rightId  -> leftIndex
  var active = null;      // { side, el }

  function leftByIndex(i) { return lefts[i]; }
  function rightById(id)  { return wrap.querySelector('.pz-mcol-r .pz-mitem[data-rid="' + id + '"]'); }
  function dotCenter(el) {
    var dot = el.querySelector('.pz-mdot');
    var box = (dot || el).getBoundingClientRect();
    var ref = wrap.getBoundingClientRect();
    return { x: box.left + box.width / 2 - ref.left, y: box.top + box.height / 2 - ref.top };
  }

  function clearActive() {
    if (active) active.el.classList.remove('active');
    active = null;
  }

  function redraw() {
    while (svg.firstChild) svg.removeChild(svg.firstChild);
    lefts.forEach(function (el) { el.classList.remove('linked'); el.querySelector('.pz-mdot').style.background = ''; });
    rights.forEach(function (el) { el.classList.remove('linked'); el.querySelector('.pz-mdot').style.background = ''; });
    Object.keys(connections).forEach(function (li) {
      li = parseInt(li, 10);
      var rid = connections[li];
      var lEl = leftByIndex(li), rEl = rightById(rid);
      if (!lEl || !rEl) return;
      var color = COLORS[li % COLORS.length];
      lEl.classList.add('linked'); rEl.classList.add('linked');
      lEl.querySelector('.pz-mdot').style.background = color;
      rEl.querySelector('.pz-mdot').style.background = color;
      var a = dotCenter(lEl), b = dotCenter(rEl);
      var line = document.createElementNS(SVGNS, 'line');
      line.setAttribute('x1', a.x); line.setAttribute('y1', a.y);
      line.setAttribute('x2', b.x); line.setAttribute('y2', b.y);
      line.setAttribute('stroke', color);
      line.setAttribute('stroke-width', '3');
      line.setAttribute('stroke-linecap', 'round');
      svg.appendChild(line);
    });
  }

  function connect(li, rid) {
    if (connections[li] !== undefined) { delete rightOwner[connections[li]]; }
    if (rightOwner[rid] !== undefined) { delete connections[rightOwner[rid]]; }
    connections[li] = rid;
    rightOwner[rid] = li;
    redraw();
    if (typeof pzAnnounce === 'function') {
      pzAnnounce('Connected ' + leftByIndex(li).textContent.trim() + ' to ' + rightById(rid).textContent.trim() + '.');
    }
  }

  function onItem(el) {
    var side = el.getAttribute('data-side');
    if (!active) { active = { side: side, el: el }; el.classList.add('active'); return; }
    if (active.side === side) { // re-select on same side
      active.el.classList.remove('active');
      active = { side: side, el: el }; el.classList.add('active');
      return;
    }
    // opposite sides → connect
    var lEl = side === 'l' ? el : active.el;
    var rEl = side === 'r' ? el : active.el;
    connect(parseInt(lEl.getAttribute('data-li'), 10), parseInt(rEl.getAttribute('data-rid'), 10));
    clearActive();
  }

  wrap.addEventListener('click', function (e) {
    var el = e.target.closest('.pz-mitem');
    if (el) onItem(el);
  });
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      connections = {}; rightOwner = {}; clearActive(); redraw();
    });
  }
  window.addEventListener('resize', redraw);

  form.addEventListener('submit', function () {
    var out = [];
    for (var i = 0; i < lefts.length; i++) {
      out.push(connections[i] !== undefined ? connections[i] : -1);
    }
    hidden.value = JSON.stringify(out);
  });

  redraw();
})();

(function () {
  var host = document.getElementById('pz-hotspot');
  var form = document.getElementById('pz-form');
  var hidden = document.getElementById('pz-hsclicks');
  if (!host || !form || !hidden) return;
  var layer = document.getElementById('pz-hslayer');
  var prog = document.getElementById('pz-hsprog');
  var resetBtn = document.getElementById('pz-hsreset');
  var max = parseInt(host.getAttribute('data-count'), 10) || 1;
  var markers = [];

  function updateProg() { if (prog) { prog.textContent = markers.length + ' of ' + max + ' marked'; } }

  // Adopt any markers re-rendered after a wrong submission.
  [].slice.call(layer.querySelectorAll('.hs-marker')).forEach(function (el) {
    markers.push({ x: parseFloat(el.getAttribute('data-x')), y: parseFloat(el.getAttribute('data-y')), el: el });
  });
  updateProg();

  function addMarker(x, y) {
    var el = document.createElement('span');
    el.className = 'hs-marker';
    el.style.left = x + '%'; el.style.top = y + '%';
    el.setAttribute('data-x', x); el.setAttribute('data-y', y);
    layer.appendChild(el);
    markers.push({ x: x, y: y, el: el });
    updateProg();
  }
  function removeMarker(m) { m.el.remove(); markers = markers.filter(function (z) { return z !== m; }); updateProg(); }

  layer.addEventListener('click', function (e) {
    var mk = e.target.closest('.hs-marker');
    if (mk) {
      var hitM = null;
      markers.forEach(function (z) { if (z.el === mk) { hitM = z; } });
      if (hitM) { removeMarker(hitM); }
      return;
    }
    if (markers.length >= max) { return; }
    var rect = layer.getBoundingClientRect();
    var x = Math.max(0, Math.min(100, (e.clientX - rect.left) / rect.width * 100));
    var y = Math.max(0, Math.min(100, (e.clientY - rect.top) / rect.height * 100));
    addMarker(Math.round(x * 100) / 100, Math.round(y * 100) / 100);
    if (typeof pzAnnounce === 'function') { pzAnnounce(markers.length + ' of ' + max + ' marked.'); }
  });

  if (resetBtn) { resetBtn.addEventListener('click', function () { markers.slice().forEach(removeMarker); }); }

  form.addEventListener('submit', function () {
    hidden.value = JSON.stringify(markers.map(function (m) { return { x: m.x, y: m.y }; }));
  });
})();
</script>
<?php endif; ?>
</body>
</html>
