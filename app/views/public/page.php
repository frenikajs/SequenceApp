<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page['page_title'], ENT_QUOTES, 'UTF-8') ?> &mdash; <?= htmlspecialchars($page['site_name'], ENT_QUOTES, 'UTF-8') ?></title>
<?php
$type      = $page['site_type'];
$siteName  = htmlspecialchars($page['site_name'],  ENT_QUOTES, 'UTF-8');
$pageTitle = htmlspecialchars($page['page_title'], ENT_QUOTES, 'UTF-8');
$author    = htmlspecialchars($page['author'] ?? 'Staff Reporter', ENT_QUOTES, 'UTF-8');
$pubDate   = $page['publish_date']
    ? date('F j, Y', strtotime($page['publish_date']))
    : date('F j, Y');
$footer    = $page['footer_text'] ?? ("\u{00A9} " . date('Y') . ' ' . htmlspecialchars($page['site_name'], ENT_QUOTES, 'UTF-8'));

// Inbox-specific variables
if ($type === 'inbox') {
    $emailFrom    = $page['author']       ?? 'sender@company.com';
    $emailTo      = $page['footer_text']  ?? 'me@company.com';
    $emailSubject = $page['page_title']   ?? '(No subject)';
    $emailDate    = $page['publish_date']
        ? date('l, F j, Y \a\t g:i A', strtotime($page['publish_date']))
        : date('l, F j, Y \a\t g:i A');
    $emailDateShort = $page['publish_date']
        ? date('g:i A', strtotime($page['publish_date']))
        : 'Just now';
    $accountName  = $page['site_name'] ?? 'My Account';
    // Derive domain for fake emails from the From address
    $domain = 'company.com';
    if (str_contains($emailFrom, '@')) {
        $domain = substr($emailFrom, strpos($emailFrom, '@') + 1);
    }
    $fromInitials = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', explode('@', $emailFrom)[0]), 0, 2)) ?: 'SN';
}
?>

<?php if ($type === 'news'): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Old+Standard+TT:ital,wght@0,400;0,700;1,400&display=swap">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px}
body{font-family:'Old Standard TT','Times New Roman',Times,serif;background:#cfc6ad;color:#241d12;line-height:1.55;padding:1.5rem .75rem}
em,i{font-style:italic}strong,b{font-weight:700}
.vp{max-width:1040px;margin:0 auto;background:#f3ead2;background-image:radial-gradient(ellipse at 20% 10%,rgba(120,95,55,.06),transparent 60%),radial-gradient(ellipse at 85% 80%,rgba(120,95,55,.08),transparent 55%);box-shadow:0 10px 40px rgba(0,0,0,.35);position:relative}
.vp::after{content:"";position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,rgba(60,45,25,.025) 0 2px,transparent 2px 4px)}
.vp-frame{border:2px solid #241d12;margin:10px;padding:1.5rem 1.75rem 2rem;position:relative}
.vp-frame::before{content:"";position:absolute;inset:5px;border:1px solid #241d12;pointer-events:none}
.vp-topline{display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem;font-style:italic;font-size:.78rem;color:#5a4a30;border-bottom:1px solid #241d12;padding-bottom:.5rem;margin-bottom:.4rem}
.vp-masthead{display:flex;align-items:center;justify-content:center;gap:1.5rem;padding:.4rem 0 .2rem}
.vp-name{font-family:'UnifrakturMaguntia','Old English Text MT',Georgia,serif;font-size:clamp(2.6rem,7vw,5rem);font-weight:400;letter-spacing:.01em;text-align:center;line-height:1}
.vp-orn{font-size:2rem;color:#3a2e1a}
.vp-subbar{border-top:3px double #241d12;border-bottom:3px double #241d12;text-align:center;text-transform:uppercase;letter-spacing:.35em;font-size:.74rem;font-weight:700;padding:.4rem 0;margin:.3rem 0 0}
.vp-dateline{display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem;font-size:.76rem;text-transform:uppercase;letter-spacing:.08em;padding:.45rem .1rem;border-bottom:1px solid #241d12;margin-bottom:1rem}
.vp-grid{display:grid;grid-template-columns:1fr 1.85fr 1fr;gap:1.5rem}
.vp-col{min-width:0}
.vp-col-c{padding:0 1.5rem;border-left:1px solid #241d12;border-right:1px solid #241d12}
.vp-fh{font-family:'Playfair Display',Georgia,serif;font-weight:900;line-height:1.12;font-size:1.45rem;text-align:center;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.01em}
.vp-fh.sm{font-size:1.1rem;letter-spacing:.04em}
.vp-fh.box{font-size:1.15rem}
.vp-kicker{text-align:center;font-style:italic;color:#6a5638;font-size:.85rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:.35rem}
.vp-lead-title{font-family:'Playfair Display',Georgia,serif;font-weight:900;font-size:clamp(1.9rem,4vw,2.9rem);line-height:1.1;text-align:center;text-transform:uppercase;margin-bottom:.55rem}
.vp-byline{text-align:center;font-size:.84rem;letter-spacing:.05em;color:#4a3c24;border-top:1px solid #241d12;border-bottom:1px solid #241d12;padding:.4rem 0;margin-bottom:.9rem}
.vp-byline strong{font-variant:small-caps;letter-spacing:.08em}
.vp-rule{border:none;border-top:1px solid #241d12;margin:1rem 0}
.vp-rule.thick{border-top:3px double #241d12}
.vp-cut{background:repeating-linear-gradient(48deg,rgba(36,29,18,.10) 0 2px,transparent 2px 6px),radial-gradient(ellipse at 50% 40%,#e6d8b6,#c9b78d);border:1px solid #241d12;padding:.85rem;display:flex;flex-direction:column;align-items:center;margin:.65rem 0}
.vp-cut svg{width:100%;height:auto;max-height:230px;display:block;filter:sepia(.25)}
.vp-cut.wide svg{max-height:260px}
.vp-cut.tall svg{max-height:300px}
.vp-cap{font-style:italic;font-size:.78rem;color:#4a3c24;text-align:center;margin-top:.5rem;border-top:1px solid #8a7550;padding-top:.35rem;width:100%}
.vp-text{font-size:.92rem;text-align:justify;hyphens:auto}
.vp-text p{margin-bottom:.7rem}
.vp-text p:first-of-type::first-letter{font-family:'Playfair Display',Georgia,serif;font-size:3.1rem;font-weight:900;float:left;line-height:.8;padding:.05em .12em 0 0;color:#3a2e1a}
.vp-body{font-size:1rem;text-align:justify;hyphens:auto;columns:2;column-gap:1.6rem;column-rule:1px solid #b6a37c}
.vp-body p{margin-bottom:.75rem}
.vp-body p:first-of-type::first-letter{font-family:'Playfair Display',Georgia,serif;font-size:3.6rem;font-weight:900;float:left;line-height:.78;padding:.04em .12em 0 0;color:#3a2e1a}
.vp-body h2{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;text-align:center;text-transform:uppercase;letter-spacing:.04em;margin:1.1rem 0 .5rem;break-after:avoid}
.vp-body h3{font-size:1.05rem;font-weight:700;font-variant:small-caps;letter-spacing:.05em;margin:.9rem 0 .4rem}
.vp-body ul,.vp-body ol{padding-left:1.4rem;margin-bottom:.75rem}
.vp-body blockquote{border-left:3px solid #8a7550;padding:.5rem 1rem;margin:.9rem 0;font-style:italic;color:#5a4a30}
.vp-box{border:2px solid #241d12;padding:.85rem;background:rgba(60,45,25,.05)}
.vp-box p{font-size:.86rem;text-align:justify;margin-top:.5rem}
.vp-fake p{font-size:.88rem;text-align:justify;hyphens:auto;margin-bottom:.6rem}
.vp-fashion{margin-top:1.2rem}
.vp-fashion-in{display:grid;grid-template-columns:240px 1fr;gap:1.5rem;align-items:start}
.vp-fashion-in .vp-text{columns:2;column-gap:1.5rem}
.vp-foot{border-top:3px double #241d12;margin-top:1.4rem;padding-top:.7rem;text-align:center;font-style:italic;font-size:.8rem;color:#5a4a30;letter-spacing:.04em}
@media(max-width:820px){.vp-grid{grid-template-columns:1fr}.vp-col-c{border:none;padding:0;border-top:3px double #241d12;border-bottom:3px double #241d12;padding:1rem 0;margin:.5rem 0}.vp-body{columns:1}.vp-fashion-in{grid-template-columns:1fr}.vp-fashion-in .vp-text{columns:1}}
</style>

<?php elseif ($type === 'corporate'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px}
body{font-family:'Segoe UI',system-ui,Arial,sans-serif;background:#eef0f3;color:#1c2b33;line-height:1.55}
a{color:inherit;text-decoration:none}
img{max-width:100%}
.wv-top{background:#0e7c6b;color:#fff;position:sticky;top:0;z-index:20;box-shadow:0 1px 6px rgba(0,0,0,.12)}
.wv-top-in{max-width:1180px;margin:0 auto;display:flex;align-items:center;gap:18px;padding:0 18px;height:56px}
.wv-logo{display:flex;align-items:center;gap:9px;font-size:19px;font-weight:800;letter-spacing:-.01em}
.wv-logo-mark{width:30px;height:30px;border-radius:8px;background:#fff;color:#0e7c6b;display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:900}
.wv-search{flex:1;max-width:420px}
.wv-search input{width:100%;border:none;border-radius:20px;padding:8px 16px;font-size:13px;background:rgba(255,255,255,.18);color:#fff;outline:none;font-family:inherit}
.wv-search input::placeholder{color:rgba(255,255,255,.75)}
.wv-tnav{display:flex;gap:4px;margin-left:auto}
.wv-tnav a{display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 14px;font-size:11px;border-radius:7px;opacity:.92}
.wv-tnav a.act,.wv-tnav a:hover{background:rgba(255,255,255,.16)}
.wv-tnav .ic{font-size:17px}
.wv-me{width:34px;height:34px;border-radius:50%;background:#f4a01c;color:#3a2600;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;margin-left:6px}
.wv-wrap{max-width:1180px;margin:22px auto;padding:0 18px;display:grid;grid-template-columns:240px 1fr 300px;gap:20px;align-items:start}
.wv-card{background:#fff;border:1px solid #e3e6ea;border-radius:12px}
.wv-side,.wv-aside{position:sticky;top:78px;display:flex;flex-direction:column;gap:16px}
.wv-pcard{overflow:hidden}
.wv-pcover{height:64px;background:linear-gradient(120deg,#0e7c6b,#13b39a)}
.wv-pav{width:64px;height:64px;border-radius:50%;background:#f4a01c;color:#3a2600;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;border:4px solid #fff;margin:-32px auto 0}
.wv-pbody{text-align:center;padding:6px 14px 16px}
.wv-pname{font-weight:700;font-size:15px}
.wv-prole{font-size:12px;color:#6b7785;margin-top:1px}
.wv-pstats{display:flex;justify-content:center;gap:18px;margin-top:12px;font-size:12px;color:#6b7785}
.wv-pstats b{display:block;color:#1c2b33;font-size:15px}
.wv-menu{padding:8px}
.wv-menu a{display:flex;align-items:center;gap:11px;padding:9px 12px;border-radius:8px;font-size:14px;color:#3a4753}
.wv-menu a .ic{font-size:16px;width:20px;text-align:center}
.wv-menu a.act{background:#e6f4f1;color:#0e7c6b;font-weight:600}
.wv-menu a:hover{background:#f1f3f5}
.wv-feed{display:flex;flex-direction:column;gap:16px;min-width:0}
.wv-comp{display:flex;gap:12px;align-items:center;padding:16px}
.wv-comp .av{width:42px;height:42px;border-radius:50%;background:#f4a01c;color:#3a2600;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0}
.wv-comp .fld{flex:1;background:#f1f3f5;border-radius:22px;padding:11px 18px;color:#8a96a3;font-size:14px}
.wv-comp .go{background:#0e7c6b;color:#fff;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600}
.wv-post{padding:18px}
.wv-post.pinned{border:1px solid #0e7c6b;box-shadow:0 2px 10px rgba(14,124,107,.12)}
.wv-pin{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#0e7c6b;background:#e6f4f1;padding:4px 10px;border-radius:20px;margin-bottom:12px}
.wv-ph{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.wv-ph .av{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;flex-shrink:0}
.wv-ph .nm{font-weight:700;font-size:15px}
.wv-ph .mt{font-size:12px;color:#6b7785;margin-top:1px}
.wv-ph .more{margin-left:auto;color:#9aa6b2;font-size:18px}
.wv-ptitle{font-size:20px;font-weight:800;line-height:1.25;margin:2px 0 10px}
.wv-rich{font-size:15px;line-height:1.7;color:#28323a}
.wv-rich p{margin-bottom:.8rem}
.wv-rich h1,.wv-rich h2,.wv-rich h3{margin:1.1rem 0 .5rem;line-height:1.3}
.wv-rich h2{font-size:1.2rem}.wv-rich h3{font-size:1.05rem}
.wv-rich ul,.wv-rich ol{padding-left:1.4rem;margin-bottom:.8rem}
.wv-rich blockquote{border-left:4px solid #0e7c6b;background:#f1f7f6;padding:.6rem 1rem;margin:.9rem 0;border-radius:0 6px 6px 0;color:#4a5763}
.wv-rich strong{font-weight:700}.wv-rich em{font-style:italic}
.wv-rich a{color:#0e7c6b;text-decoration:underline}
.wv-ptext{font-size:15px;line-height:1.6;color:#28323a}
.wv-pimg{margin-top:12px;border-radius:10px;height:240px;background:linear-gradient(120deg,#cdd9e1,#9fb4c2);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.8);font-size:13px}
.wv-pimg.g2{background:linear-gradient(120deg,#d8cbe6,#a89bc8)}
.wv-poll{margin-top:12px;display:flex;flex-direction:column;gap:8px}
.wv-opt{position:relative;border:1px solid #dde2e6;border-radius:8px;padding:9px 12px;font-size:14px;overflow:hidden}
.wv-opt .bar{position:absolute;inset:0;background:#e6f4f1;z-index:0}
.wv-opt .lb{position:relative;z-index:1;display:flex;justify-content:space-between}
.wv-stat{display:flex;align-items:center;justify-content:space-between;font-size:12px;color:#6b7785;padding:10px 0 6px;margin-top:12px;border-top:1px solid #eef0f2}
.wv-rx{display:flex;align-items:center}
.wv-rx span{width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;border:2px solid #fff;margin-left:-6px}
.wv-rx span:first-child{margin-left:0}
.wv-act{display:flex;border-top:1px solid #eef0f2;padding-top:4px}
.wv-act button{flex:1;background:none;border:none;color:#5a6773;font-size:13px;font-weight:600;padding:9px;border-radius:7px;cursor:default;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:7px}
.wv-act button:hover{background:#f1f3f5}
.wv-cmt{display:flex;gap:10px;margin-top:12px}
.wv-cmt .av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:12px;flex-shrink:0}
.wv-cmt .bub{background:#f1f3f5;border-radius:14px;padding:8px 13px;font-size:13px}
.wv-cmt .bub b{display:block;font-size:12.5px}
.wv-w{padding:16px}
.wv-w h4{font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#6b7785;margin-bottom:12px}
.wv-ev{display:flex;gap:11px;padding:9px 0;border-bottom:1px solid #f0f2f4}
.wv-ev:last-child{border-bottom:none}
.wv-date{width:42px;text-align:center;background:#e6f4f1;border-radius:8px;padding:5px 0;flex-shrink:0}
.wv-date .d{font-size:16px;font-weight:800;color:#0e7c6b;line-height:1}
.wv-date .m{font-size:10px;text-transform:uppercase;color:#0e7c6b}
.wv-ev .et{font-size:13.5px;font-weight:600}
.wv-ev .es{font-size:12px;color:#6b7785}
.wv-bd{display:flex;align-items:center;gap:10px;padding:8px 0}
.wv-bd .av{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:12px;flex-shrink:0}
.wv-bd .bt{font-size:13px}.wv-bd .bs{font-size:11.5px;color:#6b7785}
.wv-bd .cake{margin-left:auto;font-size:15px}
.wv-foot{grid-column:1/-1;text-align:center;color:#9aa6b2;font-size:12px;padding:10px 0 4px}
@media(max-width:1000px){.wv-wrap{grid-template-columns:1fr 300px}.wv-side{display:none}}
@media(max-width:760px){.wv-wrap{grid-template-columns:1fr}.wv-aside{display:none}.wv-search{display:none}}
</style>

<?php elseif ($type === 'blog'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px}
body{font-family:Georgia,'Times New Roman',serif;background:#fefefe;color:#2c2c2c;line-height:1.7}
a{color:inherit;text-decoration:none}
img{max-width:100%}
.bl-topbar{background:#1a1a1a;color:#cfcfcf;font-size:.76rem;font-family:Arial,sans-serif}
.bl-topbar-in{max-width:1140px;margin:0 auto;padding:.45rem 1.5rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem}
.bl-topbar a{color:#cfcfcf}
.bl-hd{border-bottom:1px solid #e8e8e8;background:#fff}
.bl-hd-in{max-width:1140px;margin:0 auto;padding:2.1rem 1.5rem 1.4rem;text-align:center}
.bl-name{font-size:2.6rem;font-weight:700;font-style:italic;color:#1a1a1a;letter-spacing:-.025em}
.bl-tagline{font-size:.8rem;color:#999;margin-top:.35rem;font-family:Arial,sans-serif;text-transform:uppercase;letter-spacing:.22em}
.bl-nav{border-top:1px solid #eee;border-bottom:1px solid #eee;background:#fff;font-family:Arial,sans-serif}
.bl-nav-in{max-width:1140px;margin:0 auto;padding:0 1.5rem;display:flex;justify-content:center;flex-wrap:wrap}
.bl-nav a{display:block;padding:.85rem 1.1rem;font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;color:#555;border-bottom:2px solid transparent}
.bl-nav a:first-child{color:#c0392b;border-bottom-color:#c0392b}
.bl-nav a:hover{color:#c0392b}
.bl-wrap{max-width:1140px;margin:2.5rem auto;padding:0 1.5rem;display:grid;grid-template-columns:1fr 320px;gap:3rem;align-items:start}
.bl-bc{font-size:.76rem;color:#aaa;font-family:Arial,sans-serif;margin-bottom:1rem}
.bl-bc a{color:#c0392b}
.bl-cat{display:inline-block;font-size:.66rem;text-transform:uppercase;letter-spacing:.16em;color:#fff;background:#c0392b;font-family:Arial,sans-serif;font-weight:700;padding:.3rem .65rem;border-radius:3px;margin-bottom:.9rem}
.bl-title{font-size:2.4rem;font-weight:800;line-height:1.18;margin-bottom:.85rem;color:#1a1a1a}
.bl-meta{display:flex;align-items:center;flex-wrap:wrap;gap:.5rem 1.15rem;font-size:.82rem;color:#888;font-family:Arial,sans-serif;border-bottom:1px solid #eee;padding-bottom:1.2rem;margin-bottom:1.5rem}
.bl-meta .av{width:34px;height:34px;border-radius:50%;background:#c0392b;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem}
.bl-meta .by{color:#444;font-weight:700}
.bl-meta .mi{display:flex;align-items:center;gap:.35rem}
.bl-feat{height:300px;border-radius:6px;margin-bottom:1.75rem;background:linear-gradient(135deg,#d7c9b8,#a8b8c8 55%,#8fa0a8);position:relative;overflow:hidden}
.bl-feat::after{content:"";position:absolute;inset:0;background:repeating-linear-gradient(45deg,rgba(255,255,255,.05) 0 12px,transparent 12px 24px)}
.bl-content{font-size:1.1rem;line-height:1.9;color:#333}
.bl-content p{margin-bottom:1.15rem}
.bl-content h2{font-size:1.55rem;font-weight:700;margin:2rem 0 .7rem;color:#1a1a1a}
.bl-content h3{font-size:1.25rem;margin:1.5rem 0 .5rem;color:#1a1a1a}
.bl-content ul,.bl-content ol{padding-left:1.5rem;margin-bottom:1.15rem}
.bl-content blockquote{font-style:italic;color:#666;border-left:4px solid #c0392b;padding:.6rem 1.4rem;margin:1.5rem 0;font-size:1.15rem}
.bl-content strong{font-weight:700}.bl-content em{font-style:italic}
.bl-content a{color:#c0392b;text-decoration:underline}
.bl-tags{margin:2rem 0;padding-top:1.25rem;border-top:1px solid #eee;font-family:Arial,sans-serif}
.bl-tags span{font-size:.74rem;text-transform:uppercase;letter-spacing:.12em;color:#999;margin-right:.6rem}
.bl-tag{display:inline-block;font-size:.78rem;color:#555;background:#f1f1f1;padding:.32rem .7rem;border-radius:3px;margin:.2rem .35rem .2rem 0}
.bl-tag:hover{background:#c0392b;color:#fff}
.bl-share{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:2rem;font-family:Arial,sans-serif}
.bl-share a{font-size:.78rem;color:#fff;padding:.45rem .9rem;border-radius:3px}
.bl-share .s-fb{background:#3b5998}.bl-share .s-tw{background:#1da1f2}.bl-share .s-pin{background:#bd081c}.bl-share .s-em{background:#777}
.bl-authbox{display:flex;gap:1.1rem;background:#f8f8f6;border:1px solid #eee;border-radius:8px;padding:1.4rem;margin-bottom:2.5rem}
.bl-authbox .av{width:60px;height:60px;border-radius:50%;background:#c0392b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;flex-shrink:0}
.bl-authbox h4{font-size:.7rem;text-transform:uppercase;letter-spacing:.14em;color:#aaa;font-family:Arial,sans-serif;margin-bottom:.2rem}
.bl-authbox .an{font-size:1.15rem;font-weight:700;color:#1a1a1a;margin-bottom:.35rem}
.bl-authbox p{font-size:.9rem;color:#666;line-height:1.6}
.bl-postnav{display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;border-top:1px solid #eee;border-bottom:1px solid #eee;padding:1.25rem 0;margin-bottom:2.5rem;font-family:Arial,sans-serif}
.bl-postnav a{font-size:.85rem;color:#555;max-width:46%}
.bl-postnav .lbl{display:block;font-size:.68rem;text-transform:uppercase;letter-spacing:.12em;color:#bbb;margin-bottom:.25rem}
.bl-postnav a:hover{color:#c0392b}
.bl-sec-h{font-size:1.3rem;font-weight:800;color:#1a1a1a;margin-bottom:1.25rem;padding-bottom:.5rem;border-bottom:2px solid #1a1a1a;display:inline-block}
.bl-older{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2.5rem}
.bl-card{border:1px solid #eee;border-radius:8px;overflow:hidden;background:#fff}
.bl-card .thumb{height:130px;background:linear-gradient(135deg,#cdbcad,#9fb0bd)}
.bl-card .thumb.t2{background:linear-gradient(135deg,#c8b8c0,#9aa8b8)}
.bl-card .thumb.t3{background:linear-gradient(135deg,#bcccb4,#9bb0a0)}
.bl-card .thumb.t4{background:linear-gradient(135deg,#d2c4b0,#b0a890)}
.bl-card-in{padding:1.1rem 1.2rem 1.3rem}
.bl-card .cc{font-size:.64rem;text-transform:uppercase;letter-spacing:.14em;color:#c0392b;font-family:Arial,sans-serif;font-weight:700}
.bl-card h3{font-size:1.08rem;font-weight:700;line-height:1.3;margin:.4rem 0;color:#1a1a1a}
.bl-card p{font-size:.86rem;color:#777;line-height:1.55}
.bl-card .cd{font-size:.74rem;color:#aaa;font-family:Arial,sans-serif;margin-top:.6rem}
.bl-comments{border-top:2px solid #1a1a1a;padding-top:1.75rem}
.bl-cmt{display:flex;gap:1rem;margin-bottom:1.5rem}
.bl-cmt .av{width:46px;height:46px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0}
.bl-cmt .cb{flex:1;background:#f8f8f6;border-radius:8px;padding:.85rem 1.1rem}
.bl-cmt.reply{margin-left:3rem}
.bl-cmt .cn{font-weight:700;color:#1a1a1a;font-size:.92rem}
.bl-cmt .ct{font-size:.74rem;color:#aaa;font-family:Arial,sans-serif;margin-bottom:.45rem}
.bl-cmt p{font-size:.92rem;color:#555;line-height:1.6}
.bl-cmt .rl{font-size:.74rem;color:#c0392b;font-family:Arial,sans-serif;margin-top:.4rem;display:inline-block}
.bl-cform{background:#f8f8f6;border:1px solid #eee;border-radius:8px;padding:1.5rem;margin-top:1.5rem}
.bl-cform h4{font-size:1.05rem;color:#1a1a1a;margin-bottom:.9rem}
.bl-cform input,.bl-cform textarea{width:100%;border:1px solid #ddd;border-radius:5px;padding:.65rem .8rem;font-family:inherit;font-size:.9rem;margin-bottom:.7rem;background:#fff}
.bl-cform .row2{display:flex;gap:.7rem}
.bl-cform button{background:#c0392b;color:#fff;border:none;border-radius:5px;padding:.65rem 1.5rem;font-size:.88rem;font-weight:600;cursor:default;font-family:Arial,sans-serif}
.bl-side>div{margin-bottom:2rem}
.bl-w{border:1px solid #eee;border-radius:8px;overflow:hidden}
.bl-w-h{background:#1a1a1a;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.14em;font-family:Arial,sans-serif;font-weight:700;padding:.7rem 1rem}
.bl-w-b{padding:1.1rem}
.bl-search{display:flex}
.bl-search input{flex:1;border:1px solid #ddd;border-right:none;border-radius:5px 0 0 5px;padding:.6rem .75rem;font-family:inherit;font-size:.88rem}
.bl-search button{background:#c0392b;color:#fff;border:none;padding:0 1rem;border-radius:0 5px 5px 0;cursor:default}
.bl-about{font-size:.88rem;color:#666;line-height:1.65}
.bl-about .a-av{width:70px;height:70px;border-radius:50%;background:#c0392b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;margin:0 auto .8rem}
.bl-rp{display:flex;gap:.75rem;padding:.7rem 0;border-bottom:1px solid #f0f0f0}
.bl-rp:last-child{border-bottom:none}
.bl-rp .rt{width:54px;height:54px;border-radius:5px;flex-shrink:0;background:linear-gradient(135deg,#cdbcad,#9fb0bd)}
.bl-rp .rt.r2{background:linear-gradient(135deg,#c8b8c0,#9aa8b8)}
.bl-rp .rt.r3{background:linear-gradient(135deg,#bcccb4,#9bb0a0)}
.bl-rp .rt.r4{background:linear-gradient(135deg,#d2c4b0,#b0a890)}
.bl-rp h5{font-size:.88rem;font-weight:700;color:#1a1a1a;line-height:1.35}
.bl-rp .rd{font-size:.72rem;color:#aaa;font-family:Arial,sans-serif;margin-top:.25rem}
.bl-list{list-style:none;font-size:.9rem}
.bl-list li{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f0f0f0;color:#555}
.bl-list li:last-child{border-bottom:none}
.bl-list .ct{background:#f1f1f1;color:#888;font-size:.74rem;border-radius:10px;padding:0 .55rem;font-family:Arial,sans-serif}
.bl-cloud .bl-tag{font-size:.76rem}
.bl-foot{background:#1a1a1a;color:#999;margin-top:3.5rem;font-family:Arial,sans-serif}
.bl-foot-in{max-width:1140px;margin:0 auto;padding:2.5rem 1.5rem;display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;font-size:.85rem}
.bl-foot h4{color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.14em;margin-bottom:.9rem}
.bl-foot a{color:#999;display:block;padding:.22rem 0}
.bl-foot a:hover{color:#fff}
.bl-foot-bar{border-top:1px solid #333;text-align:center;padding:1.25rem;font-size:.78rem;color:#777}
@media(max-width:900px){.bl-wrap{grid-template-columns:1fr}.bl-foot-in{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.bl-older{grid-template-columns:1fr}.bl-title{font-size:1.8rem}.bl-feat{height:200px}.bl-cmt.reply{margin-left:1rem}.bl-foot-in{grid-template-columns:1fr}.bl-name{font-size:2rem}}
</style>

<?php elseif ($type === 'archive'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px}
body{font-family:'Courier New',Courier,monospace;background:#111;color:#b0b0a0;line-height:1.7}
a{color:#00cc66;text-decoration:none}
.hd{background:#0a0a0a;border-bottom:1px solid #2a2a2a;padding:.85rem 2rem}
.hd-top{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.4rem;margin-bottom:.4rem}
.hd-name{color:#00cc66;font-size:1.05rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase}
.hd-status{font-size:.7rem;color:#444}
.hd-nav{font-size:.72rem;color:#444}
.hd-nav a{color:#666;margin-right:.85rem}
.hd-nav a:hover{color:#00cc66}
.term{background:#0a0a0a;border-bottom:2px solid #1e1e1e;padding:.4rem 2rem;font-size:.72rem;color:#444}
.term span{color:#00cc66;margin-right:.4rem}
.wrap{max-width:860px;margin:1.5rem auto;padding:0 2rem}
.doc-hd{border:1px solid #2a2a2a;border-radius:2px;padding:1.1rem 1.35rem;margin-bottom:1.5rem;background:#0a0a0a}
.doc-rows{display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:.6rem;font-size:.78rem}
.doc-f{display:flex;flex-direction:column;gap:.1rem}
.doc-f label{color:#444;text-transform:uppercase;font-size:.65rem;letter-spacing:.12em}
.doc-f value{color:#00cc66}
.doc-title{font-size:1.25rem;color:#d0d0c0;margin-top:.6rem;font-weight:700}
.doc-cls{display:inline-block;border:1px solid #333;padding:.15rem .6rem;font-size:.65rem;letter-spacing:.15em;color:#555;margin-top:.4rem;text-transform:uppercase}
.doc-body{font-size:.9rem;line-height:1.88;color:#a0a090}
.doc-body p{margin-bottom:1rem}
.doc-body h2{color:#00cc66;font-size:.88rem;text-transform:uppercase;letter-spacing:.12em;margin:1.5rem 0 .45rem;font-weight:700}
.doc-body h3{color:#777;font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;margin:1.2rem 0 .4rem}
.doc-body ul,.doc-body ol{padding-left:1.4rem;margin-bottom:1rem;list-style:square}
.doc-body blockquote{border:1px dashed #2a2a2a;padding:.75rem 1rem;margin:1rem 0;color:#888;background:#0d0d0d}
.doc-body strong{color:#c0c0b0;font-weight:700}
.doc-body em{font-style:italic;color:#888}
.ft{border-top:1px solid #1a1a1a;margin-top:3rem;padding:1.25rem 2rem;font-size:.7rem;color:#333;text-align:center}
.doc-meta{display:grid;grid-template-columns:repeat(4,1fr);gap:.7rem 1.75rem;border-top:1px dashed #2a2a2a;margin-top:.9rem;padding-top:.8rem}
.doc-meta .doc-f value{color:#9a9a86}
.doc-sec{border:1px solid #2a2a2a;background:#0a0a0a;margin-top:1.75rem}
.doc-sec-h{background:#0d0d0d;border-bottom:1px solid #2a2a2a;padding:.5rem 1rem;color:#00cc66;font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;display:flex;justify-content:space-between}
.doc-sec-h span{color:#444}
.doc-sec-b{padding:.5rem 1rem .85rem;font-size:.78rem}
.doc-rel-row{display:flex;gap:1rem;padding:.45rem 0;border-bottom:1px dashed #1c1c1c;color:#8a8a7a}
.doc-rel-row:last-child{border-bottom:none}
.doc-rel-row .rid{color:#00cc66;white-space:nowrap}
.doc-rel-row .rt{flex:1}
.doc-rel-row .rx{color:#444;white-space:nowrap}
.doc-log{width:100%;border-collapse:collapse;font-size:.74rem;color:#7a7a6a}
.doc-log td,.doc-log th{padding:.34rem .55rem;text-align:left;border-bottom:1px dashed #1c1c1c}
.doc-log tr:last-child td{border-bottom:none}
.doc-log th{color:#444;text-transform:uppercase;font-size:.64rem;letter-spacing:.1em}
.doc-log td.ok{color:#00cc66}
.doc-log td.dn{color:#a55}
.ft-grid{display:flex;justify-content:center;gap:1.75rem;flex-wrap:wrap;color:#333}
.ft-grid b{color:#555}
</style>

<?php elseif ($type === 'calendar'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:'Segoe UI',system-ui,Arial,sans-serif;background:#faf9f8;color:#252423;font-size:14px;display:flex;flex-direction:column;height:100vh;overflow:hidden}
.cl-header{height:48px;background:#0f6cbd;display:flex;align-items:center;padding:0 14px;gap:14px;flex-shrink:0;color:#fff}
.cl-logo{display:flex;align-items:center;gap:8px;font-size:16px;font-weight:600;min-width:180px}
.cl-logo-icon{width:30px;height:30px;background:rgba(255,255,255,.15);border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:16px}
.cl-search{flex:1;max-width:520px}
.cl-search input{width:100%;padding:6px 12px;border-radius:4px;border:none;background:rgba(255,255,255,.18);color:#fff;font-size:13px;outline:none;font-family:inherit}
.cl-search input::placeholder{color:rgba(255,255,255,.75)}
.cl-acct{margin-left:auto;width:32px;height:32px;border-radius:50%;background:#fff;color:#0f6cbd;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px}
.cl-toolbar{height:46px;background:#fff;border-bottom:1px solid #edebe9;display:flex;align-items:center;gap:6px;padding:0 14px;flex-shrink:0}
.cl-newbtn{background:#0f6cbd;color:#fff;border:none;border-radius:4px;padding:7px 14px;font-size:13px;font-weight:600;font-family:inherit;cursor:default}
.cl-tbtn{background:none;border:1px solid transparent;color:#323130;font-size:13px;padding:6px 10px;border-radius:4px;cursor:default;font-family:inherit;display:flex;align-items:center;gap:5px}
.cl-tbtn:hover{background:#f3f2f1}
.cl-tbtn.arrow{font-size:15px;padding:6px 9px}
.cl-month-lbl{font-size:17px;font-weight:600;margin:0 8px}
.cl-view{margin-left:auto;border:1px solid #d2d0ce;border-radius:4px;font-size:13px;padding:6px 12px;color:#323130}
.cl-body{display:flex;flex:1;overflow:hidden}
.cl-side{width:230px;background:#faf9f8;border-right:1px solid #edebe9;padding:14px;flex-shrink:0;overflow-y:auto}
.cl-mini-h{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;margin-bottom:8px}
.cl-mini{width:100%;border-collapse:collapse;font-size:11px}
.cl-mini th{color:#a19f9d;font-weight:600;padding:3px 0;text-align:center}
.cl-mini td{text-align:center;padding:4px 0;color:#605e5c;border-radius:50%}
.cl-mini td.dim{color:#c8c6c4}
.cl-mini td.today{background:#0f6cbd;color:#fff;font-weight:700}
.cl-cals{margin-top:18px;font-size:13px}
.cl-cals-h{font-weight:600;margin-bottom:8px;color:#323130}
.cl-cal-item{display:flex;align-items:center;gap:8px;padding:5px 0;color:#605e5c}
.cl-chk{width:14px;height:14px;border-radius:3px;flex-shrink:0}
.cl-grid-wrap{flex:1;overflow:auto;background:#fff;display:flex;flex-direction:column}
.cl-weekhead{display:grid;grid-template-columns:repeat(7,1fr);background:#fff;border-bottom:1px solid #edebe9;flex-shrink:0}
.cl-weekhead div{text-align:center;padding:8px 2px;font-size:12px;color:#605e5c;text-transform:uppercase;letter-spacing:.06em;border-right:1px solid #edebe9}
.cl-month{display:grid;grid-template-columns:repeat(7,1fr);grid-auto-rows:1fr;flex:1;min-height:560px}
.cl-cell{border-right:1px solid #edebe9;border-bottom:1px solid #edebe9;padding:4px 5px;display:flex;flex-direction:column;gap:2px;overflow:hidden;min-height:96px}
.cl-cell.out{background:#faf9f8}
.cl-cell.wknd{background:#fcfbfa}
.cl-daynum{font-size:12px;color:#605e5c;font-weight:600;align-self:flex-start;width:22px;height:22px;line-height:22px;text-align:center;border-radius:50%;flex-shrink:0}
.cl-cell.is-today .cl-daynum{background:#0f6cbd;color:#fff}
.cl-cell.out .cl-daynum{color:#c8c6c4}
.cl-chip{font-size:11px;border-radius:3px;padding:2px 5px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;border-left:3px solid;line-height:1.3}
.cl-chip.real{background:#cfe4fa;border-left-color:#0f6cbd;color:#243f60;font-weight:600;white-space:normal}
.cl-chip.real .ca{display:block;font-weight:400;font-size:10px;opacity:.85}
.cl-chip.fake{background:#eef0f2;border-left-color:#a3aab2;color:#5a5f66}
.cl-chip .ct{font-weight:600}
.cl-more{font-size:10px;color:#0f6cbd;padding:1px 5px;cursor:default}
.cl-statusbar{height:26px;background:#f3f2f1;border-top:1px solid #edebe9;font-size:12px;color:#605e5c;display:flex;align-items:center;padding:0 14px;flex-shrink:0}
@media(max-width:760px){.cl-side{display:none}.cl-search{display:none}.cl-cell{min-height:78px}}
</style>
<?php elseif ($type === 'inbox'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{font-family:'Segoe UI',system-ui,Arial,sans-serif;background:#f3f2f1;color:#252423;font-size:14px}
/* ── Header ── */
.ol-header{height:48px;background:#0078d4;display:flex;align-items:center;padding:0 12px;gap:12px;flex-shrink:0}
.ol-logo{display:flex;align-items:center;gap:8px;color:#fff;font-size:16px;font-weight:600;min-width:200px}
.ol-logo-icon{width:32px;height:32px;background:#fff;border-radius:4px;display:flex;align-items:center;justify-content:center}
.ol-logo-icon svg{width:20px;height:20px}
.ol-search{flex:1;max-width:560px;margin:0 auto}
.ol-search input{width:100%;padding:6px 12px;border-radius:4px;border:none;background:rgba(255,255,255,.2);color:#fff;font-size:13px;outline:none;font-family:inherit}
.ol-search input::placeholder{color:rgba(255,255,255,.7)}
.ol-search input:focus{background:rgba(255,255,255,.3)}
.ol-avatar{width:32px;height:32px;border-radius:50%;background:#fff;color:#0078d4;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin-left:auto;cursor:pointer;flex-shrink:0}
/* ── Layout ── */
.ol-body{display:flex;height:calc(100vh - 48px);overflow:hidden}
/* ── Sidebar ── */
.ol-sidebar{width:220px;background:#f3f2f1;border-right:1px solid #e1dfdd;display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto}
.ol-new-btn{padding:12px}
.ol-new-btn button{width:100%;padding:8px 12px;background:#0078d4;color:#fff;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:default;font-family:inherit;text-align:left;display:flex;align-items:center;gap:6px}
.ol-folders{list-style:none;padding:4px 0}
.ol-folders li{display:flex;align-items:center;gap:10px;padding:8px 16px;font-size:13px;cursor:default;color:#323130;border-radius:0;position:relative}
.ol-folders li:hover{background:#edebe9}
.ol-folders li.active{background:#deecf9;color:#0078d4;font-weight:600}
.ol-folders li.active::before{content:'';position:absolute;left:0;top:4px;bottom:4px;width:3px;background:#0078d4;border-radius:0 2px 2px 0}
.ol-folder-icon{font-size:15px;width:18px;text-align:center}
.ol-badge{margin-left:auto;background:#0078d4;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:600}
/* ── Email list ── */
.ol-list{width:340px;background:#fff;border-right:1px solid #e1dfdd;display:flex;flex-direction:column;flex-shrink:0}
.ol-list-header{padding:12px 16px 8px;font-size:18px;font-weight:600;color:#252423;border-bottom:1px solid #f3f2f1;flex-shrink:0}
.ol-list-sub{font-size:12px;color:#605e5c;font-weight:400;margin-top:2px}
.ol-emails{overflow-y:auto;flex:1}
.ol-email{padding:12px 16px;border-bottom:1px solid #f3f2f1;cursor:pointer;position:relative;display:flex;gap:10px;align-items:flex-start}
.ol-email:hover{background:#f3f2f1}
.ol-email.selected{background:#deecf9}
.ol-email.unread .ol-email-sender{font-weight:700;color:#252423}
.ol-email.unread .ol-email-subject{font-weight:600;color:#252423}
.ol-unread-dot{width:8px;height:8px;background:#0078d4;border-radius:50%;flex-shrink:0;margin-top:5px}
.ol-email-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0}
.ol-email-body{flex:1;min-width:0}
.ol-email-row1{display:flex;justify-content:space-between;align-items:baseline;gap:4px}
.ol-email-sender{font-size:13px;color:#323130;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ol-email-time{font-size:11px;color:#605e5c;flex-shrink:0}
.ol-email-subject{font-size:12px;color:#323130;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
.ol-email-preview{font-size:12px;color:#8a8886;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
/* ── Reader ── */
.ol-reader{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#fff}
.ol-reader-empty{flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;color:#605e5c}
.ol-reader-empty .empty-icon{font-size:3rem;opacity:.3}
.ol-reader-empty p{font-size:14px}
.ol-reader-header{padding:20px 32px 16px;border-bottom:1px solid #e1dfdd;flex-shrink:0}
.ol-reader-subject{font-size:20px;font-weight:600;color:#252423;margin-bottom:12px;line-height:1.3}
.ol-reader-meta{display:flex;align-items:flex-start;gap:12px}
.ol-reader-avatar{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0}
.ol-reader-info{flex:1;min-width:0}
.ol-reader-from{font-size:14px;font-weight:600;color:#252423}
.ol-reader-addr{font-size:12px;color:#605e5c;margin-top:1px}
.ol-reader-date{font-size:12px;color:#605e5c;flex-shrink:0;margin-top:2px}
.ol-reader-to{font-size:12px;color:#605e5c;margin-top:4px}
.ol-reader-body{flex:1;overflow-y:auto;padding:24px 32px}
.ol-reader-body p{margin-bottom:.85rem;line-height:1.6;color:#323130}
.ol-reader-body h1,.ol-reader-body h2,.ol-reader-body h3{margin:1.2rem 0 .5rem;color:#252423}
.ol-reader-body ul,.ol-reader-body ol{padding-left:1.5rem;margin-bottom:.85rem}
.ol-reader-body blockquote{border-left:3px solid #e1dfdd;padding:.5rem 1rem;margin:1rem 0;color:#605e5c;font-style:italic}
.ol-reader-body strong{font-weight:600}
.ol-reader-body a{color:#0078d4}
.ol-toolbar{padding:10px 32px;border-bottom:1px solid #e1dfdd;display:flex;gap:6px;flex-shrink:0}
.ol-toolbar button{padding:5px 12px;border:1px solid #e1dfdd;background:#fff;border-radius:4px;font-size:13px;cursor:default;font-family:inherit;color:#323130;display:flex;align-items:center;gap:5px}
.ol-toolbar button:hover{background:#f3f2f1;border-color:#c8c6c4}
@media(max-width:700px){.ol-sidebar{display:none}.ol-list{width:260px}}
@media(max-width:500px){.ol-list{display:none}.ol-reader{display:flex}}
</style>
<?php elseif ($type === 'sms'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden;-webkit-text-size-adjust:100%}
body{font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Arial,sans-serif;background:#e5e5ea;color:#000}
.ios-screen{display:flex;flex-direction:column;height:100vh;max-width:430px;margin:0 auto;background:#fff;box-shadow:0 0 60px rgba(0,0,0,.2)}
.ios-status{background:#fff;display:flex;justify-content:space-between;align-items:center;padding:14px 20px 6px;font-size:15px;font-weight:600;flex-shrink:0}
.ios-status-icons{display:flex;align-items:center;gap:6px}
.ios-nav{background:#f2f2f7;border-bottom:1px solid rgba(0,0,0,.12);padding:8px 16px 10px;display:flex;align-items:center;position:relative;flex-shrink:0}
.ios-back{color:#007aff;font-size:17px;display:flex;align-items:center;gap:1px;cursor:default;z-index:1;user-select:none}
.ios-back-chev{font-size:24px;line-height:1;margin-top:-2px;font-weight:300}
.ios-contact-center{position:absolute;left:0;right:0;display:flex;flex-direction:column;align-items:center;pointer-events:none}
.ios-contact-avatar{width:38px;height:38px;border-radius:50%;background:#8e8e93;color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:600;margin-bottom:2px}
.ios-contact-name{font-size:13px;font-weight:600;color:#000;line-height:1.2}
.ios-contact-detail{font-size:11px;color:#8e8e93}
.ios-nav-info{margin-left:auto;color:#007aff;font-size:20px;z-index:1;cursor:default}
.ios-messages{flex:1;overflow-y:auto;padding:12px 16px 8px;display:flex;flex-direction:column;gap:3px;background:#fff}
.ios-date-label{text-align:center;font-size:11px;color:#8e8e93;margin:10px 0 6px;font-weight:500;letter-spacing:.01em}
.ios-bubble-row{display:flex;align-items:flex-end;gap:6px;margin-bottom:3px}
.ios-bubble-row.me{flex-direction:row-reverse}
.ios-sender-avatar{width:28px;height:28px;border-radius:50%;background:#8e8e93;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.ios-bubble{padding:10px 14px;border-radius:18px;font-size:16px;line-height:1.45;max-width:72%;word-break:break-word}
.ios-bubble.them{background:#e9e9eb;color:#000;border-bottom-left-radius:5px}
.ios-bubble.me{background:#007aff;color:#fff;border-bottom-right-radius:5px}
.ios-status-line{font-size:11px;color:#8e8e93;text-align:right;padding:2px 8px 8px}
.ios-input-bar{background:#f9f9f9;border-top:1px solid rgba(0,0,0,.1);padding:8px 12px;display:flex;align-items:center;gap:10px;flex-shrink:0}
.ios-input-add{color:#007aff;font-size:24px;line-height:1;cursor:default;user-select:none}
.ios-input-field{flex:1;background:#fff;border:1px solid #c7c7cc;border-radius:20px;padding:7px 14px;font-size:15px;color:#8e8e93;font-family:inherit;user-select:none}
.ios-send-btn{width:30px;height:30px;background:#007aff;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;cursor:default;user-select:none}
</style>
<?php elseif ($type === 'invoice'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px}
body{font-family:'Segoe UI',Arial,Helvetica,sans-serif;background:#e8eaed;color:#2d3748;line-height:1.6;padding:2.5rem 1rem}
.inv-sheet{max-width:760px;margin:0 auto;background:#fff;box-shadow:0 4px 24px rgba(0,0,0,.12);border-radius:6px;overflow:hidden}
.inv-top{display:flex;justify-content:space-between;align-items:flex-start;padding:2.5rem 3rem;border-bottom:4px solid #2b6cb0;flex-wrap:wrap;gap:1.5rem}
.inv-biz{font-size:1.6rem;font-weight:700;color:#2b6cb0;letter-spacing:-.01em}
.inv-biz-sub{font-size:.82rem;color:#718096;margin-top:.25rem}
.inv-label{text-align:right}
.inv-label h1{font-size:2.2rem;font-weight:800;color:#1a202c;letter-spacing:.06em;text-transform:uppercase}
.inv-label .inv-no{font-size:.88rem;color:#718096;margin-top:.35rem}
.inv-label .inv-date{font-size:.88rem;color:#718096}
.inv-billto{padding:1.75rem 3rem;background:#f7fafc}
.inv-billto .lbl{font-size:.7rem;text-transform:uppercase;letter-spacing:.16em;color:#a0aec0;font-weight:700;margin-bottom:.35rem}
.inv-billto .name{font-size:1.05rem;font-weight:600;color:#2d3748}
.inv-table{width:100%;border-collapse:collapse;margin:0}
.inv-table thead th{background:#2b6cb0;color:#fff;font-size:.74rem;text-transform:uppercase;letter-spacing:.08em;padding:.85rem 1rem;text-align:left}
.inv-table thead th.num{text-align:right}
.inv-table tbody td{padding:1rem;border-bottom:1px solid #e2e8f0;font-size:.92rem;vertical-align:top}
.inv-table tbody td.num{text-align:right;white-space:nowrap}
.inv-table .it-name{font-weight:600;color:#2d3748}
.inv-table .it-desc{font-size:.83rem;color:#718096;margin-top:.2rem}
.inv-totals{display:flex;justify-content:flex-end;padding:1.5rem 3rem 2.5rem}
.inv-totals-box{min-width:260px}
.inv-trow{display:flex;justify-content:space-between;padding:.55rem 0;font-size:.92rem;color:#4a5568}
.inv-trow.grand{border-top:2px solid #2d3748;margin-top:.5rem;padding-top:.9rem;font-size:1.25rem;font-weight:800;color:#1a202c}
.inv-foot{padding:1.5rem 3rem 2.25rem;border-top:1px solid #e2e8f0;font-size:.85rem;color:#718096;background:#f7fafc}
.inv-foot .lbl{font-size:.7rem;text-transform:uppercase;letter-spacing:.16em;color:#a0aec0;font-weight:700;margin-bottom:.3rem}
@media(max-width:560px){.inv-top,.inv-billto,.inv-totals,.inv-foot{padding-left:1.5rem;padding-right:1.5rem}.inv-label{text-align:left}}
</style>
<?php elseif ($type === 'receipt'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Courier New',Courier,monospace;background:#d9d9d9;color:#1a1a1a;padding:2rem 1rem;line-height:1.5}
.rcpt{max-width:340px;margin:0 auto;background:#fff;padding:1.75rem 1.5rem 2.25rem;box-shadow:0 3px 16px rgba(0,0,0,.18)}
.rcpt-head{text-align:center;margin-bottom:1rem}
.rcpt-store{font-size:1.7rem;font-weight:800;letter-spacing:.02em}
.rcpt-tag{font-size:.72rem;margin-top:.15rem}
.rcpt-addr{font-size:.74rem;margin-top:.65rem;line-height:1.5}
.rcpt-sep{border:none;border-top:1px dashed #999;margin:.9rem 0}
.rcpt-meta{font-size:.72rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:.25rem}
.rcpt-items{font-size:.82rem;margin:.5rem 0}
.rcpt-row{display:flex;justify-content:space-between;gap:1rem;padding:.18rem 0}
.rcpt-row .ri-name{flex:1;text-transform:uppercase;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rcpt-row .ri-price{white-space:nowrap}
.rcpt-tot{font-size:.86rem;margin-top:.3rem}
.rcpt-tot .rcpt-row{padding:.22rem 0}
.rcpt-tot .grand{font-weight:800;font-size:1.05rem;border-top:1px solid #1a1a1a;margin-top:.35rem;padding-top:.5rem}
.rcpt-foot{text-align:center;font-size:.74rem;margin-top:1.1rem;line-height:1.6}
.rcpt-barcode{margin-top:1rem;text-align:center}
.rcpt-barcode .bars{font-family:'Courier New',monospace;font-size:2.4rem;letter-spacing:-3px;line-height:1}
.rcpt-barcode .num{font-size:.74rem;letter-spacing:.28em;margin-top:.2rem}
</style>
<?php elseif ($type === 'map'): ?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:'Segoe UI',Roboto,Arial,sans-serif;background:#e8eaed;color:#202124;display:flex;flex-direction:column;height:100vh;overflow:hidden}
.gmap-header{background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.18);padding:.7rem 1.25rem;display:flex;align-items:center;gap:.85rem;z-index:5;flex-shrink:0}
.gmap-pin-mini{width:30px;height:30px;background:#ea4335;border-radius:50% 50% 50% 0;transform:rotate(-45deg);flex-shrink:0;position:relative}
.gmap-pin-mini::after{content:'';position:absolute;top:8px;left:8px;width:14px;height:14px;background:#fff;border-radius:50%}
.gmap-title{font-size:1.1rem;font-weight:600;line-height:1.2}
.gmap-sub{font-size:.78rem;color:#5f6368}
.gmap-frame{position:relative;flex:1;overflow:hidden}
.gmap-svg{position:absolute;inset:0;width:100%;height:100%;display:block}
.gmap-pin{position:absolute;transform:translate(-50%,-100%);z-index:3;display:flex;flex-direction:column;align-items:center;filter:drop-shadow(0 2px 3px rgba(0,0,0,.35))}
.gmap-pin svg{display:block}
.gmap-pin-label{margin-top:3px;background:#fff;color:#202124;font-size:.76rem;font-weight:600;padding:3px 8px;border-radius:5px;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,.3);max-width:170px;overflow:hidden;text-overflow:ellipsis}
.gmap-zoom{position:absolute;right:14px;bottom:26px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.3);z-index:4}
.gmap-zoom button{display:block;width:38px;height:38px;border:none;background:none;font-size:1.25rem;color:#5f6368;cursor:default}
.gmap-zoom button:first-child{border-bottom:1px solid #e0e0e0}
.gmap-footer{position:absolute;left:14px;bottom:14px;background:rgba(255,255,255,.92);font-size:.74rem;color:#5f6368;padding:5px 10px;border-radius:5px;box-shadow:0 1px 3px rgba(0,0,0,.2);z-index:4;max-width:60%}
.gmap-cred{position:absolute;right:14px;bottom:6px;font-size:.66rem;color:#80868b;z-index:4}
</style>
<?php endif; ?>
</head>
<body>

<?php if ($type === 'news'): ?>

<?php
$vpYear = $page['publish_date'] ? (int)date('Y', strtotime($page['publish_date'])) : (int)date('Y');
$vpNo   = 4000 + ((int)($page['id'] ?? 1) % 6000);
?>
<div class="vp">
  <div class="vp-frame">

    <div class="vp-topline">
      <span>This edition preserves the spirit and craftsmanship of a bygone age.</span>
      <span>No. <?= $vpNo ?> &mdash; Price: One Penny</span>
    </div>

    <div class="vp-masthead">
      <span class="vp-orn">&#10086;</span>
      <h1 class="vp-name"><?= $siteName ?></h1>
      <span class="vp-orn">&#10087;</span>
    </div>
    <div class="vp-subbar">World News &middot; Global Headlines</div>
    <div class="vp-dateline">
      <span><?= htmlspecialchars(strtoupper($pubDate), ENT_QUOTES, 'UTF-8') ?></span>
      <span>Morning Edition</span>
      <span>Established <?= $vpYear ?></span>
    </div>

    <div class="vp-grid">

      <!-- LEFT: fake column -->
      <div class="vp-col vp-col-l">
        <article class="vp-fake">
          <h2 class="vp-fh">Captain Jonathan Harrington</h2>
          <div class="vp-cut">
            <svg viewBox="0 0 200 230" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#241d12" stroke-width="2">
              <rect x="8" y="8" width="184" height="214" stroke-width="3"/>
              <ellipse cx="100" cy="108" rx="78" ry="96"/>
              <path d="M100 60c-17 0-30 14-30 33 0 14 6 25 14 31-4 3-7 8-9 13"/>
              <path d="M100 60c17 0 30 14 30 33 0 14-6 25-14 31 4 3 7 8 9 13"/>
              <path d="M70 137c-16 7-26 20-30 41 30 14 90 14 120 0-4-21-14-34-30-41"/>
              <path d="M85 95c4-3 9-3 13 0M102 95c4-3 9-3 13 0"/>
              <path d="M96 104v14c-3 4-1 7 4 7" stroke-width="1.5"/>
              <path d="M86 130c8 6 20 6 28 0" stroke-width="1.5"/>
              <path d="M64 70c10-12 24-18 36-18s26 6 36 18" stroke-width="1.5"/>
              <path d="M70 168l10 40M130 168l-10 40M100 162v46" stroke-width="1.5"/>
            </svg>
            <div class="vp-cap">Master of the ironclad <em>Defiance</em></div>
          </div>
          <p>Few names command such reverence upon the open water as that of Captain Harrington, whose three decades at the helm have become the stuff of dockside legend. Sailors speak of his unflinching calm amid the fiercest gales, and of a discipline that turned a green crew into the finest in the merchant fleet.</p>
          <p>Our correspondent, granted rare audience aboard the vessel at anchor, found the Captain unwilling to dwell upon his celebrated exploits, preferring instead to credit "a sound ship and steadier men."</p>
        </article>
        <hr class="vp-rule">
        <article class="vp-fake">
          <h3 class="vp-fh sm">The Typewriter</h3>
          <p>The curious writing-machine, lately arrived from the Continent, threatens to render the copyist's pen obsolete. Clerks marvel at its speed; traditionalists mourn the loss of a fine hand. Whether novelty or revolution, the device has set every counting-house abuzz.</p>
        </article>
      </div>

      <!-- CENTER: the editable main article -->
      <div class="vp-col vp-col-c">
        <div class="vp-kicker">&mdash; Our Leading Dispatch &mdash;</div>
        <h2 class="vp-lead-title"><?= $pageTitle ?></h2>
        <div class="vp-byline">By <strong><?= $author ?></strong> &nbsp;&bull;&nbsp; <?= htmlspecialchars($pubDate, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="vp-body"><?= $page['content'] ?></div>
      </div>

      <!-- RIGHT: fake column -->
      <div class="vp-col vp-col-r">
        <div class="vp-box">
          <h3 class="vp-fh box">Lights Stun the Capital</h3>
          <p>Crowds gathered at dusk upon the Embankment to witness the new electric lamps cast their unearthly glow. Some hailed a marvel of the age; others, shielding their eyes, declared the gaslight had served well enough and ought not be hurried into retirement.</p>
        </div>
        <hr class="vp-rule">
        <article class="vp-fake">
          <h3 class="vp-fh sm">Notices &amp; Telegrams</h3>
          <p>&mdash; The railway to the northern counties is reported open once more, the floodwaters having at last receded.</p>
          <p>&mdash; A reward is offered for the return of a gentleman's pocket-watch, lost near the old market square.</p>
          <p>&mdash; The Philharmonic Society announces a concert by subscription on the last Friday of the month.</p>
        </article>
        <hr class="vp-rule">
        <article class="vp-fake">
          <h3 class="vp-fh sm">The Subterranean Railway</h3>
          <p>Engineers have completed the latest stretch of the underground line, and the public is invited to descend into the gaslit tunnels for the first time this week. Sceptics warn of foul air and rattling nerves; the bolder sort speak of journeys across the city accomplished in mere minutes.</p>
          <p>Whatever the verdict, the enterprise stands as a monument to the restless ambition of the age, burrowing beneath the very streets it seeks to relieve.</p>
        </article>
      </div>

    </div>

    <hr class="vp-rule thick">

    <article class="vp-fashion">
      <h2 class="vp-fh">Fashion Feature: The Vintage Dress</h2>
      <div class="vp-fashion-in">
        <div class="vp-cut tall">
          <svg viewBox="0 0 200 280" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#241d12" stroke-width="2">
            <circle cx="100" cy="34" r="18"/>
            <path d="M70 30c0-8 14-16 30-16s30 8 30 16" stroke-width="1.5"/>
            <path d="M92 52c5 5 11 5 16 0"/>
            <path d="M100 52v10"/>
            <path d="M84 66c10-6 22-6 32 0l10 22-14 8h-24l-14-8z"/>
            <path d="M84 66c-14 6-22 18-26 36M116 66c14 6 22 18 26 36"/>
            <path d="M58 102c-2 8 4 14 12 14M142 102c2 8-4 14-12 14"/>
            <path d="M86 96h28M84 110h32M82 124h36"/>
            <path d="M82 130c-22 36-34 80-40 132h116c-6-52-18-96-40-132z"/>
            <path d="M70 180c20 8 40 8 60 0M64 214c24 10 48 10 72 0M58 250c28 12 56 12 84 0" stroke-width="1.5"/>
            <path d="M100 130v132" stroke-width="1"/>
          </svg>
          <div class="vp-cap">The season's silhouette</div>
        </div>
        <div class="vp-text">
          <p>This season the discerning lady favours a structured bodice paired with a full bell skirt, the whole executed in rich brocades and muted, dignified hues. The leg-of-mutton sleeve, lately the height of fashion, gives way to a closer, more economical cut, while the waist is drawn in by stays of whalebone and fine cording, lending the figure that admired hour-glass form so prized in the drawing rooms of the better streets.</p>
          <p>From the great houses of the Continent comes word that deep burgundy, forest green, and a sombre midnight blue shall reign supreme through the colder months. The bolder débutante may venture a trimming of jet beads or a collar of Honiton lace, though our correspondent counsels that such flourishes are best employed with a sparing hand, lest the effect tip from elegance into ostentation.</p>
          <p>Milliners report brisk trade in feathered bonnets, the plumes of the ostrich and the egret being much sought after, while the parasol &mdash; once a mere ornament &mdash; is now deemed indispensable to any respectable promenade. Gloves remain, as ever, the mark of refinement, and no toilette is complete without them; the lady of fashion is known to keep no fewer than a dozen pairs, each appointed to its proper occasion.</p>
          <p>For the evening, silk and watered taffeta hold sway, their rustle announcing the wearer before she is well into the room. The train, banished these several seasons past, returns in modest measure, sweeping but a hand's breadth behind the heel &mdash; enough to suggest grandeur without the inconvenience of a footman to attend it.</p>
          <p>The walking costume, by contrast, grows ever more practical: a shortened skirt that clears the pavement, stout buttoned boots, and a tailored jacket borrowed, it is whispered, from the gentleman's own wardrobe. Practicality and grace, it seems, need no longer be strangers.</p>
          <p>The young miss is dressed more simply still, in sprigged muslin and a sash of ribbon, that she might run and play without reproach &mdash; a small mercy, and a sensible one. Mothers of the present day, our correspondent is glad to report, increasingly favour comfort over the rigid finery once imposed upon the nursery.</p>
          <p>Our correspondent observes, in closing, that elegance &mdash; in this as in all things &mdash; lies not in extravagance but in restraint, and that the lady best remembered is seldom the most lavishly attired, but rather she who wore her garments with quiet and unstudied assurance. It is a principle the modern age would do well to remember.</p>
        </div>
      </div>
    </article>

    <footer class="vp-foot"><?= $footer ?> &mdash; Printed &amp; published at the Chronicle Press</footer>

  </div>
</div>

<?php elseif ($type === 'corporate'): ?>

<?php
$wvBrandIn = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', (string)($page['site_name'] ?? 'W')), 0, 1)) ?: 'W';
$wvAuthRaw = $page['author'] ?: 'Communications';
$wvAuthIn  = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $wvAuthRaw), 0, 2)) ?: 'CO';
$wvAvCol   = ['#0e7c6b','#3a6ea5','#b5532e','#7a59a8','#1f8a70','#c0392b','#2c7fb8'];
$wvPick    = function (string $s) use ($wvAvCol) { return $wvAvCol[abs(crc32($s)) % count($wvAvCol)]; };
$wvCmt     = json_decode($page['nav_json'] ?? '[]', true);
$wvCmtName = (is_array($wvCmt) && trim((string)($wvCmt['cmt_name'] ?? '')) !== '')
    ? $wvCmt['cmt_name'] : 'Priya Nair';
$wvCmtText = (is_array($wvCmt) && trim((string)($wvCmt['cmt_text'] ?? '')) !== '')
    ? $wvCmt['cmt_text'] : "Thanks for the clear update \u{2014} really appreciate the transparency on this.";
$wvCmtIn   = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', (string)$wvCmtName), 0, 2)) ?: 'PN';
?>
  <div class="wv-top">
    <div class="wv-top-in">
      <div class="wv-logo"><span class="wv-logo-mark"><?= htmlspecialchars($wvBrandIn, ENT_QUOTES, 'UTF-8') ?></span> <?= $siteName ?></div>
      <div class="wv-search"><input type="text" placeholder="Search people, posts and spaces"></div>
      <nav class="wv-tnav">
        <a href="#" class="act"><span class="ic">&#127968;</span><span>Home</span></a>
        <a href="#"><span class="ic">&#128101;</span><span>Spaces</span></a>
        <a href="#"><span class="ic">&#128197;</span><span>Events</span></a>
        <a href="#"><span class="ic">&#128218;</span><span>Docs</span></a>
        <a href="#"><span class="ic">&#128276;</span><span>Alerts</span></a>
      </nav>
      <div class="wv-me"><?= htmlspecialchars($wvAuthIn, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>

  <div class="wv-wrap">
    <!-- Left -->
    <aside class="wv-side">
      <div class="wv-card wv-pcard">
        <div class="wv-pcover"></div>
        <div class="wv-pav"><?= htmlspecialchars($wvAuthIn, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="wv-pbody">
          <div class="wv-pname"><?= $author ?></div>
          <div class="wv-prole"><?= htmlspecialchars($footer !== '' ? strip_tags((string)$footer) : 'Team Member', ENT_QUOTES, 'UTF-8') ?></div>
          <div class="wv-pstats">
            <div><b>128</b>Posts</div><div><b>1.2k</b>Kudos</div><div><b>34</b>Spaces</div>
          </div>
        </div>
      </div>
      <div class="wv-card wv-menu">
        <a href="#" class="act"><span class="ic">&#127968;</span> Home Feed</a>
        <a href="#"><span class="ic">&#11088;</span> My Spaces</a>
        <a href="#"><span class="ic">&#128101;</span> People</a>
        <a href="#"><span class="ic">&#128193;</span> Documents</a>
        <a href="#"><span class="ic">&#128197;</span> Events</a>
        <a href="#"><span class="ic">&#127942;</span> Awards</a>
        <a href="#"><span class="ic">&#128172;</span> Shoutouts</a>
      </div>
    </aside>

    <!-- Center feed -->
    <main class="wv-feed">
      <div class="wv-card wv-comp">
        <div class="av"><?= htmlspecialchars($wvAuthIn, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="fld">Share something with the team&hellip;</div>
        <div class="go">Post</div>
      </div>

      <!-- REAL post (admin content) -->
      <article class="wv-card wv-post pinned">
        <span class="wv-pin">&#128204; Pinned Announcement</span>
        <div class="wv-ph">
          <div class="av" style="background:#0e7c6b"><?= htmlspecialchars($wvAuthIn, ENT_QUOTES, 'UTF-8') ?></div>
          <div>
            <div class="nm"><?= $author ?></div>
            <div class="mt"><?= htmlspecialchars($pubDate, ENT_QUOTES, 'UTF-8') ?> &middot; &#127758; Company-wide</div>
          </div>
          <div class="more">&#8943;</div>
        </div>
        <?php if (trim((string)$pageTitle) !== ''): ?><div class="wv-ptitle"><?= $pageTitle ?></div><?php endif; ?>
        <div class="wv-rich"><?= $page['content'] ?></div>
        <div class="wv-stat">
          <div class="wv-rx"><span style="background:#f4a01c">&#128077;</span><span style="background:#e0566b">&#10084;</span><span style="background:#3a6ea5">&#127881;</span> &nbsp;214</div>
          <div>37 comments &middot; 12 shares</div>
        </div>
        <div class="wv-act">
          <button>&#128077; React</button><button>&#128172; Comment</button><button>&#10150; Share</button>
        </div>
        <div class="wv-cmt">
          <div class="av" style="background:<?= $wvPick($wvCmtName) ?>"><?= htmlspecialchars($wvCmtIn, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="bub"><b><?= htmlspecialchars($wvCmtName, ENT_QUOTES, 'UTF-8') ?></b><?= htmlspecialchars($wvCmtText, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </article>

      <!-- Fake posts -->
      <article class="wv-card wv-post">
        <div class="wv-ph">
          <div class="av" style="background:<?= $wvPick('People & Culture') ?>">PC</div>
          <div><div class="nm">People &amp; Culture</div><div class="mt">2 days ago &middot; &#128101; All Staff</div></div>
          <div class="more">&#8943;</div>
        </div>
        <div class="wv-ptext">Please join us in welcoming our newest team members starting this week! Say hello when you see them around &#128075;</div>
        <div class="wv-stat">
          <div class="wv-rx"><span style="background:#f4a01c">&#128075;</span><span style="background:#e0566b">&#10084;</span> &nbsp;96</div>
          <div>18 comments</div>
        </div>
        <div class="wv-act"><button>&#128077; React</button><button>&#128172; Comment</button><button>&#10150; Share</button></div>
      </article>

      <article class="wv-card wv-post">
        <div class="wv-ph">
          <div class="av" style="background:<?= $wvPick('Marcus Bell') ?>">MB</div>
          <div><div class="nm">Marcus Bell</div><div class="mt">3 days ago &middot; &#127942; Shoutouts</div></div>
          <div class="more">&#8943;</div>
        </div>
        <div class="wv-ptext">Huge shoutout to <strong>Dana Whitfield</strong> for stepping up on the migration over the weekend. Above and beyond &mdash; thank you! &#127881;</div>
        <div class="wv-stat">
          <div class="wv-rx"><span style="background:#3a6ea5">&#127881;</span><span style="background:#f4a01c">&#128077;</span> &nbsp;143</div>
          <div>26 comments</div>
        </div>
        <div class="wv-act"><button>&#128077; React</button><button>&#128172; Comment</button><button>&#10150; Share</button></div>
        <div class="wv-cmt">
          <div class="av" style="background:<?= $wvPick('Dana Whitfield') ?>">DW</div>
          <div class="bub"><b>Dana Whitfield</b>Appreciate it Marcus &mdash; great team effort all round! &#128591;</div>
        </div>
      </article>

      <article class="wv-card wv-post">
        <div class="wv-ph">
          <div class="av" style="background:<?= $wvPick('Workplace Team') ?>">WT</div>
          <div><div class="nm">Workplace Team</div><div class="mt">4 days ago &middot; &#127968; Office</div></div>
          <div class="more">&#8943;</div>
        </div>
        <div class="wv-ptext">Summer social this Friday from 4pm on the rooftop terrace &mdash; food, music and a few surprises. Bring your team! &#127865;</div>
        <div class="wv-pimg">Rooftop terrace &middot; Friday 4:00 PM</div>
        <div class="wv-stat">
          <div class="wv-rx"><span style="background:#f4a01c">&#128515;</span><span style="background:#e0566b">&#10084;</span><span style="background:#3a6ea5">&#127881;</span> &nbsp;188</div>
          <div>41 comments &middot; 9 shares</div>
        </div>
        <div class="wv-act"><button>&#128077; React</button><button>&#128172; Comment</button><button>&#10150; Share</button></div>
      </article>

      <article class="wv-card wv-post">
        <div class="wv-ph">
          <div class="av" style="background:<?= $wvPick('Facilities') ?>">FC</div>
          <div><div class="nm">Facilities</div><div class="mt">5 days ago &middot; &#128172; Poll</div></div>
          <div class="more">&#8943;</div>
        </div>
        <div class="wv-ptext">What should we name the new collaboration room on level 3?</div>
        <div class="wv-poll">
          <div class="wv-opt"><span class="bar" style="width:47%"></span><span class="lb"><span>The Hive</span><span>47%</span></span></div>
          <div class="wv-opt"><span class="bar" style="width:31%"></span><span class="lb"><span>Brainstorm Bay</span><span>31%</span></span></div>
          <div class="wv-opt"><span class="bar" style="width:22%"></span><span class="lb"><span>The Treehouse</span><span>22%</span></span></div>
        </div>
        <div class="wv-stat">
          <div class="wv-rx"><span style="background:#3a6ea5">&#128202;</span> &nbsp;73 votes</div>
          <div>14 comments</div>
        </div>
        <div class="wv-act"><button>&#128077; React</button><button>&#128172; Comment</button><button>&#10150; Share</button></div>
      </article>
    </main>

    <!-- Right -->
    <aside class="wv-aside">
      <div class="wv-card wv-w">
        <h4>Upcoming Events</h4>
        <div class="wv-ev"><div class="wv-date"><div class="d">12</div><div class="m">Jun</div></div><div><div class="et">All-Hands Town Hall</div><div class="es">10:00 AM &middot; Main Auditorium</div></div></div>
        <div class="wv-ev"><div class="wv-date"><div class="d">14</div><div class="m">Jun</div></div><div><div class="et">Summer Social</div><div class="es">4:00 PM &middot; Rooftop Terrace</div></div></div>
        <div class="wv-ev"><div class="wv-date"><div class="d">19</div><div class="m">Jun</div></div><div><div class="et">Lunch &amp; Learn: AI Tools</div><div class="es">12:30 PM &middot; Online</div></div></div>
      </div>
      <div class="wv-card wv-w">
        <h4>Birthdays &amp; Anniversaries</h4>
        <div class="wv-bd"><div class="av" style="background:<?= $wvPick('Sofia Reyes') ?>">SR</div><div><div class="bt">Sofia Reyes</div><div class="bs">Birthday today</div></div><span class="cake">&#127874;</span></div>
        <div class="wv-bd"><div class="av" style="background:<?= $wvPick('Tom Kavanagh') ?>">TK</div><div><div class="bt">Tom Kavanagh</div><div class="bs">5 years at <?= $siteName ?></div></div><span class="cake">&#127881;</span></div>
        <div class="wv-bd"><div class="av" style="background:<?= $wvPick('Aisha Khan') ?>">AK</div><div><div class="bt">Aisha Khan</div><div class="bs">Work anniversary</div></div><span class="cake">&#127880;</span></div>
      </div>
      <div class="wv-card wv-w">
        <h4>New Starters</h4>
        <div class="wv-bd"><div class="av" style="background:<?= $wvPick('Liam Foster') ?>">LF</div><div><div class="bt">Liam Foster</div><div class="bs">Product Designer</div></div></div>
        <div class="wv-bd"><div class="av" style="background:<?= $wvPick('Grace Lin') ?>">GL</div><div><div class="bt">Grace Lin</div><div class="bs">Data Analyst</div></div></div>
      </div>
    </aside>

    <div class="wv-foot"><?= $footer ?></div>
  </div>

<?php elseif ($type === 'blog'): ?>

<?php
$blWords   = str_word_count(strip_tags((string)($page['content'] ?? '')));
$blReadMin = max(1, (int)ceil($blWords / 200));
$blAuthor  = $page['author'] ?: 'Staff Writer';
$blInit    = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $blAuthor), 0, 1)) ?: 'A';
$blYear    = $page['publish_date'] ? (int)date('Y', strtotime($page['publish_date'])) : (int)date('Y');
$blCmt     = json_decode($page['nav_json'] ?? '[]', true);
$blCmtName = (is_array($blCmt) && trim((string)($blCmt['cmt_name'] ?? '')) !== '')
    ? $blCmt['cmt_name'] : 'Thomas Reyes';
$blCmtText = (is_array($blCmt) && trim((string)($blCmt['cmt_text'] ?? '')) !== '')
    ? $blCmt['cmt_text']
    : 'Found my way here from the newsletter and stayed far longer than I meant to. Subscribed. Looking forward to the next one.';
$blCmtInit = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', (string)$blCmtName), 0, 1)) ?: 'R';
?>
  <div class="bl-topbar"><div class="bl-topbar-in">
    <span><?= htmlspecialchars(strtoupper(date('l, F j, Y')), ENT_QUOTES, 'UTF-8') ?></span>
    <span><a href="#">Subscribe</a> &nbsp;&middot;&nbsp; <a href="#">Newsletter</a> &nbsp;&middot;&nbsp; <a href="#">Log In</a></span>
  </div></div>

  <header class="bl-hd"><div class="bl-hd-in">
    <div class="bl-name"><?= $siteName ?></div>
    <div class="bl-tagline">Stories, Journeys &amp; Quiet Observations</div>
  </div></header>

  <nav class="bl-nav"><div class="bl-nav-in">
    <a href="#">Home</a><a href="#">About</a><a href="#">Travel</a>
    <a href="#">Journal</a><a href="#">Reading</a><a href="#">Archive</a><a href="#">Contact</a>
  </div></nav>

  <div class="bl-wrap">
    <main class="bl-main">
      <div class="bl-bc"><a href="#">Home</a> &nbsp;&rsaquo;&nbsp; <a href="#">Journal</a> &nbsp;&rsaquo;&nbsp; <?= $pageTitle ?></div>
      <span class="bl-cat">Journal</span>
      <h1 class="bl-title"><?= $pageTitle ?></h1>
      <div class="bl-meta">
        <span class="mi"><span class="av"><?= htmlspecialchars($blInit, ENT_QUOTES, 'UTF-8') ?></span> By <span class="by"><?= $author ?></span></span>
        <span class="mi">&#128197; <?= htmlspecialchars($pubDate, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="mi">&#128172; 3 Comments</span>
        <span class="mi">&#9201; <?= $blReadMin ?> min read</span>
      </div>
      <div class="bl-feat"></div>
      <article class="bl-content"><?= $page['content'] ?></article>

      <div class="bl-tags">
        <span>Tagged:</span>
        <a href="#" class="bl-tag">memoir</a><a href="#" class="bl-tag">notes</a><a href="#" class="bl-tag">slow living</a><a href="#" class="bl-tag">field journal</a><a href="#" class="bl-tag">reflections</a>
      </div>
      <div class="bl-share">
        <a href="#" class="s-fb">Share on Facebook</a><a href="#" class="s-tw">Tweet</a><a href="#" class="s-pin">Pin</a><a href="#" class="s-em">Email</a>
      </div>

      <div class="bl-authbox">
        <div class="av"><?= htmlspecialchars($blInit, ENT_QUOTES, 'UTF-8') ?></div>
        <div>
          <h4>Written by</h4>
          <div class="an"><?= $author ?></div>
          <p>A keeper of notebooks and an incurable wanderer, <?= $author ?> writes about the small, easily missed details of a life lived attentively. New entries appear here whenever the road allows.</p>
        </div>
      </div>

      <div class="bl-postnav">
        <a href="#"><span class="lbl">&laquo; Previous Post</span>The Lighthouse at the End of the Lane</a>
        <a href="#" style="text-align:right"><span class="lbl">Next Post &raquo;</span>What the River Carried Away</a>
      </div>

      <h2 class="bl-sec-h">Older Posts</h2>
      <div class="bl-older">
        <article class="bl-card">
          <div class="thumb"></div>
          <div class="bl-card-in">
            <div class="cc">Travel</div>
            <h3>Three Days Without a Map</h3>
            <p>I left the guidebook in the hotel drawer and let the town decide where I would go. It knew better than I did.</p>
            <div class="cd"><?= $blYear ?> &middot; 6 min read</div>
          </div>
        </article>
        <article class="bl-card">
          <div class="thumb t2"></div>
          <div class="bl-card-in">
            <div class="cc">Journal</div>
            <h3>On Keeping a Quiet House</h3>
            <p>There is a particular kind of silence that only an old house can hold, and I have been learning to listen to it.</p>
            <div class="cd"><?= $blYear ?> &middot; 4 min read</div>
          </div>
        </article>
        <article class="bl-card">
          <div class="thumb t3"></div>
          <div class="bl-card-in">
            <div class="cc">Reading</div>
            <h3>The Books I Did Not Finish</h3>
            <p>An honest accounting of the volumes abandoned this season, and what each one taught me before I set it down.</p>
            <div class="cd"><?= $blYear - 1 ?> &middot; 5 min read</div>
          </div>
        </article>
        <article class="bl-card">
          <div class="thumb t4"></div>
          <div class="bl-card-in">
            <div class="cc">Notes</div>
            <h3>A Letter I Never Sent</h3>
            <p>It sat in the drawer for eleven years. I am finally ready to tell you what it said, and why it stayed there.</p>
            <div class="cd"><?= $blYear - 1 ?> &middot; 7 min read</div>
          </div>
        </article>
      </div>

      <section class="bl-comments">
        <h2 class="bl-sec-h">3 Comments</h2>
        <div class="bl-cmt">
          <div class="av" style="background:#2980b9">M</div>
          <div class="cb">
            <div class="cn">Margaret Holloway</div>
            <div class="ct">Posted <?= $blYear ?> at 9:14 AM</div>
            <p>This stopped me in my tracks this morning. I read it twice with my coffee going cold. Thank you for putting words to something I have felt for years.</p>
            <span class="rl">Reply</span>
          </div>
        </div>
        <div class="bl-cmt reply">
          <div class="av" style="background:#c0392b"><?= htmlspecialchars($blInit, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="cb">
            <div class="cn"><?= $author ?> <em style="font-weight:400;color:#999;font-size:.8rem">(Author)</em></div>
            <div class="ct">Posted <?= $blYear ?> at 11:02 AM</div>
            <p>That means a great deal, Margaret. Cold coffee is the truest compliment a writer can receive.</p>
          </div>
        </div>
        <div class="bl-cmt">
          <div class="av" style="background:#27ae60"><?= htmlspecialchars($blCmtInit, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="cb">
            <div class="cn"><?= htmlspecialchars($blCmtName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="ct">Posted <?= $blYear ?> at 4:38 PM</div>
            <p><?= htmlspecialchars($blCmtText, ENT_QUOTES, 'UTF-8') ?></p>
            <span class="rl">Reply</span>
          </div>
        </div>
        <div class="bl-cform">
          <h4>Leave a Reply</h4>
          <div class="row2">
            <input type="text" placeholder="Name *">
            <input type="email" placeholder="Email *">
          </div>
          <textarea rows="4" placeholder="Your comment…"></textarea>
          <button type="button">Post Comment</button>
        </div>
      </section>
    </main>

    <aside class="bl-side">
      <div class="bl-w">
        <div class="bl-w-h">Search</div>
        <div class="bl-w-b"><div class="bl-search"><input type="text" placeholder="Search the journal…"><button type="button">&#128269;</button></div></div>
      </div>
      <div class="bl-w">
        <div class="bl-w-h">About</div>
        <div class="bl-w-b">
          <div class="a-av"><?= htmlspecialchars($blInit, ENT_QUOTES, 'UTF-8') ?></div>
          <p class="bl-about">Welcome. This is a small corner of the internet kept by <?= $author ?> &mdash; a place for unhurried writing about places, books, and the things that linger after they are gone.</p>
        </div>
      </div>
      <div class="bl-w">
        <div class="bl-w-h">Recent Posts</div>
        <div class="bl-w-b">
          <a href="#" class="bl-rp"><span class="rt"></span><span><h5>Three Days Without a Map</h5><span class="rd"><?= $blYear ?></span></span></a>
          <a href="#" class="bl-rp"><span class="rt r2"></span><span><h5>On Keeping a Quiet House</h5><span class="rd"><?= $blYear ?></span></span></a>
          <a href="#" class="bl-rp"><span class="rt r3"></span><span><h5>The Books I Did Not Finish</h5><span class="rd"><?= $blYear - 1 ?></span></span></a>
          <a href="#" class="bl-rp"><span class="rt r4"></span><span><h5>A Letter I Never Sent</h5><span class="rd"><?= $blYear - 1 ?></span></span></a>
        </div>
      </div>
      <div class="bl-w">
        <div class="bl-w-h">Categories</div>
        <div class="bl-w-b">
          <ul class="bl-list">
            <li><span>Journal</span><span class="ct">24</span></li>
            <li><span>Travel</span><span class="ct">18</span></li>
            <li><span>Reading</span><span class="ct">15</span></li>
            <li><span>Notes</span><span class="ct">11</span></li>
            <li><span>Letters</span><span class="ct">7</span></li>
          </ul>
        </div>
      </div>
      <div class="bl-w">
        <div class="bl-w-h">Archives</div>
        <div class="bl-w-b">
          <ul class="bl-list">
            <li><span>January <?= $blYear ?></span><span class="ct">5</span></li>
            <li><span>December <?= $blYear - 1 ?></span><span class="ct">8</span></li>
            <li><span>November <?= $blYear - 1 ?></span><span class="ct">6</span></li>
            <li><span>October <?= $blYear - 1 ?></span><span class="ct">9</span></li>
          </ul>
        </div>
      </div>
      <div class="bl-w">
        <div class="bl-w-h">Tags</div>
        <div class="bl-w-b bl-cloud">
          <a href="#" class="bl-tag">memoir</a><a href="#" class="bl-tag">travel</a><a href="#" class="bl-tag">slow living</a><a href="#" class="bl-tag">notes</a><a href="#" class="bl-tag">books</a><a href="#" class="bl-tag">letters</a><a href="#" class="bl-tag">solitude</a><a href="#" class="bl-tag">field journal</a>
        </div>
      </div>
    </aside>
  </div>

  <footer class="bl-foot">
    <div class="bl-foot-in">
      <div>
        <h4><?= $siteName ?></h4>
        <p style="line-height:1.7"><?= $footer ?></p>
      </div>
      <div>
        <h4>Explore</h4>
        <a href="#">Home</a><a href="#">Archive</a><a href="#">Reading List</a><a href="#">Newsletter</a>
      </div>
      <div>
        <h4>Elsewhere</h4>
        <a href="#">Instagram</a><a href="#">Mastodon</a><a href="#">RSS Feed</a><a href="#">Contact</a>
      </div>
    </div>
    <div class="bl-foot-bar">&copy; <?= $blYear ?> <?= $siteName ?> &mdash; Built with care. All rights reserved.</div>
  </footer>

<?php elseif ($type === 'archive'): ?>

  <header class="hd">
    <div class="hd-top">
      <div class="hd-name"><?= $siteName ?></div>
      <div class="hd-status">SYSTEM ONLINE &mdash; <?= htmlspecialchars(strtoupper(date('Y-m-d H:i')), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <nav class="hd-nav">
      <a href="#">SEARCH</a><a href="#">BROWSE</a><a href="#">COLLECTIONS</a><a href="#">FINDING AIDS</a><a href="#">HELP</a>
    </nav>
  </header>
  <div class="term"><span>&gt;_</span>RECORD RETRIEVED &mdash; DOCUMENT ARCHIVE SYSTEM v2.4</div>

<?php
$arPid    = (int)($page['id'] ?? 1);
$arSlug   = (string)($page['slug'] ?? 'document');
$arId     = str_pad((string)$arPid, 5, '0', STR_PAD_LEFT);
$arHash   = strtoupper(substr(md5($arSlug), 0, 24));
$arSeries = 'S-' . strtoupper(substr(md5($arSlug . 'ser'), 0, 3)) . '/' . (12 + $arPid % 40);
$arPages  = 3 + ($arPid % 46);
$arBox    = 'BOX ' . (10 + $arPid % 90) . " \u{00B7} FOLDER " . (1 + $arPid % 38);
$arAcc    = 'AC-' . strtoupper(substr(md5($arSlug . 'acc'), 0, 6));
$arRefBase = strtoupper(substr(md5($arSlug . 'ref'), 0, 4));
$arRelated = [
    ['id' => 'DOC-' . str_pad((string)(($arPid * 7 + 113) % 99999), 5, '0', STR_PAD_LEFT), 'title' => 'Preliminary Field Notes & Correspondence',        'yr' => 1971],
    ['id' => 'DOC-' . str_pad((string)(($arPid * 13 + 401) % 99999), 5, '0', STR_PAD_LEFT), 'title' => 'Internal Memorandum re: Disclosure Request',      'yr' => 1973],
    ['id' => 'DOC-' . str_pad((string)(($arPid * 19 + 877) % 99999), 5, '0', STR_PAD_LEFT), 'title' => "Appendix C \u{2014} Supporting Photographic Plates", 'yr' => 1974],
    ['id' => 'DOC-' . str_pad((string)(($arPid * 23 + 559) % 99999), 5, '0', STR_PAD_LEFT), 'title' => 'Transcript of Recorded Interview (Partial)',      'yr' => 1976],
    ['id' => 'DOC-' . str_pad((string)(($arPid * 29 + 233) % 99999), 5, '0', STR_PAD_LEFT), 'title' => 'Amendment & Final Disposition Sheet',             'yr' => 1981],
];
$arLog = [
    ['t' => '08:14:02', 'd' => '2024-11-03', 'op' => 'TERM-04 / ARCHIVIST-217', 'ac' => 'RETRIEVE',  'ok' => true],
    ['t' => '13:47:55', 'd' => '2025-02-19', 'op' => 'TERM-11 / RESEARCH-088',  'ac' => 'VIEW',      'ok' => true],
    ['t' => '09:02:31', 'd' => '2025-06-30', 'op' => 'REMOTE / GUEST-AUTH',     'ac' => 'EXPORT',    'ok' => false],
    ['t' => '16:20:09', 'd' => '2025-09-12', 'op' => 'TERM-04 / ARCHIVIST-217', 'ac' => 'RE-INDEX',  'ok' => true],
    ['t' => '11:38:44', 'd' => '2025-12-01', 'op' => 'TERM-02 / ADMIN-001',     'ac' => 'VERIFY',    'ok' => true],
];
// Operator & action of the last access-log entry are editable from the admin
$arEdit = json_decode($page['nav_json'] ?? '[]', true);
if (is_array($arEdit)) {
    $arLast =& $arLog[count($arLog) - 1];
    if (trim((string)($arEdit['log_op']     ?? '')) !== '') { $arLast['op'] = $arEdit['log_op']; }
    if (trim((string)($arEdit['log_action'] ?? '')) !== '') { $arLast['ac'] = $arEdit['log_action']; }
    unset($arLast);
}
?>
  <div class="wrap">
    <div class="doc-hd">
      <div class="doc-rows">
        <div class="doc-f"><label>Doc ID</label><value>DOC-<?= $arId ?></value></div>
        <div class="doc-f"><label>Date Filed</label><value><?= htmlspecialchars($pubDate, ENT_QUOTES, 'UTF-8') ?></value></div>
        <div class="doc-f"><label>Author</label><value><?= $author ?></value></div>
      </div>
      <div class="doc-title"><?= $pageTitle ?></div>
      <div class="doc-cls">Unclassified</div>
      <div class="doc-meta">
        <div class="doc-f"><label>Record Type</label><value>TEXTUAL RECORD</value></div>
        <div class="doc-f"><label>Series</label><value><?= htmlspecialchars($arSeries, ENT_QUOTES, 'UTF-8') ?></value></div>
        <div class="doc-f"><label>Location</label><value><?= htmlspecialchars($arBox, ENT_QUOTES, 'UTF-8') ?></value></div>
        <div class="doc-f"><label>Pages</label><value><?= $arPages ?> (digitised)</value></div>
        <div class="doc-f"><label>Medium</label><value>SCANNED MICROFILM</value></div>
        <div class="doc-f"><label>Language</label><value>ENGLISH</value></div>
        <div class="doc-f"><label>Retention</label><value>PERMANENT</value></div>
        <div class="doc-f"><label>Access</label><value>READING ROOM &mdash; OPEN</value></div>
        <div class="doc-f"><label>Accession</label><value><?= htmlspecialchars($arAcc, ENT_QUOTES, 'UTF-8') ?></value></div>
        <div class="doc-f"><label>Condition</label><value>FAIR &mdash; ARCHIVAL COPY</value></div>
        <div class="doc-f" style="grid-column:span 2"><label>SHA-256 (truncated)</label><value><?= htmlspecialchars($arHash, ENT_QUOTES, 'UTF-8') ?>&hellip;</value></div>
      </div>
    </div>

    <div class="doc-body"><?= $page['content'] ?></div>

    <div class="doc-sec">
      <div class="doc-sec-h">Related Records <span><?= count($arRelated) ?> cross-references</span></div>
      <div class="doc-sec-b">
        <?php foreach ($arRelated as $r): ?>
        <div class="doc-rel-row">
          <span class="rid"><?= htmlspecialchars($r['id'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="rt"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="rx">FILED <?= (int)$r['yr'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="doc-sec">
      <div class="doc-sec-h">Access Log <span>last <?= count($arLog) ?> entries</span></div>
      <div class="doc-sec-b">
        <table class="doc-log">
          <tr><th>Date</th><th>Time</th><th>Terminal / Operator</th><th>Action</th><th>Result</th></tr>
          <?php foreach ($arLog as $l): ?>
          <tr>
            <td><?= htmlspecialchars($l['d'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($l['t'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($l['op'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($l['ac'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="<?= $l['ok'] ? 'ok' : 'dn' ?>"><?= $l['ok'] ? 'GRANTED' : 'DENIED' ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>

  <footer class="ft">
    <div class="ft-grid">
      <span><?= $footer ?></span>
      <span>NODE <b>ARC-7</b></span>
      <span>INDEX <b>v2.4.1</b></span>
      <span>REF <b><?= htmlspecialchars($arRefBase, ENT_QUOTES, 'UTF-8') ?></b></span>
      <span>SESSION <b><?= htmlspecialchars(strtoupper(substr(md5($arSlug . 'sess'), 0, 8)), ENT_QUOTES, 'UTF-8') ?></b></span>
    </div>
  </footer>

<?php elseif ($type === 'calendar'): ?>

<?php
$calAcct   = $page['site_name'] ?? 'Calendar';
$calOwner  = $page['author'] ?: 'Me';
$calStatus = $page['footer_text'] ?? '';
$calAcctIn = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', (string)$calOwner), 0, 2)) ?: 'ME';

// nav_json: {month,year,events:[{name,day,time,attendees}]} (legacy: array with date)
$calData = json_decode($page['nav_json'] ?? '[]', true) ?: [];
$calMonth = 0; $calYear = 0; $calEventsRaw = [];
if (isset($calData['events']) && is_array($calData['events'])) {
    $calMonth     = (int)($calData['month'] ?? 0);
    $calYear      = (int)($calData['year'] ?? 0);
    $calEventsRaw = $calData['events'];
} elseif (is_array($calData)) {
    foreach ($calData as $ev) {
        if (!empty($ev['date']) && ($t = strtotime($ev['date'])) !== false) {
            if (!$calMonth) { $calMonth = (int)date('n', $t); $calYear = (int)date('Y', $t); }
            $ev['day'] = (int)date('j', $t);
        }
        $calEventsRaw[] = $ev;
    }
}
if (!$calMonth) { $calMonth = !empty($page['publish_date']) ? (int)date('n', strtotime($page['publish_date'])) : (int)date('n'); }
if (!$calYear)  { $calYear  = !empty($page['publish_date']) ? (int)date('Y', strtotime($page['publish_date'])) : (int)date('Y'); }
$calMonth = min(12, max(1, $calMonth));

$firstTs   = mktime(0, 0, 0, $calMonth, 1, $calYear);
$daysIn    = (int)date('t', $firstTs);
$lead      = (int)date('w', $firstTs);
$monthName = date('F', $firstTs);
$todayD    = ((int)date('n') === $calMonth && (int)date('Y') === $calYear) ? (int)date('j') : 0;

$byDay = [];
for ($d = 1; $d <= $daysIn; $d++) { $byDay[$d] = []; }
foreach ($calEventsRaw as $ev) {
    $nm = trim((string)($ev['name'] ?? ''));
    if ($nm === '') { continue; }
    $day = (int)($ev['day'] ?? 0);
    if ($day < 1 || $day > $daysIn) { continue; }
    $tm = trim((string)($ev['time'] ?? ''));
    $tl = ''; $sort = 1440;
    if ($tm !== '' && ($tt = strtotime('2000-01-01 ' . $tm)) !== false) { $tl = date('g:i A', $tt); $sort = (int)date('Gi', $tt); }
    $byDay[$day][] = ['real' => true, 'name' => $nm, 'time' => $tl, 'att' => trim((string)($ev['attendees'] ?? '')), 'sort' => $sort];
}
for ($d = 1; $d <= $daysIn; $d++) {
    $w = (int)date('w', mktime(0, 0, 0, $calMonth, $d, $calYear));
    // Light weekly rhythm — mostly life, one small work touch
    if ($w === 1)            { $byDay[$d][] = ['real'=>false,'name'=>'Team Sync','time'=>'9:30 AM','att'=>'','sort'=>930]; }
    if ($w === 2)            { $byDay[$d][] = ['real'=>false,'name'=>'Yoga Class','time'=>'6:00 PM','att'=>'','sort'=>1800]; }
    if ($w === 6)            { $byDay[$d][] = ['real'=>false,'name'=>'Farmers Market','time'=>'9:00 AM','att'=>'','sort'=>900]; }
    if ($w === 0)            { $byDay[$d][] = ['real'=>false,'name'=>'Family Dinner','time'=>'5:00 PM','att'=>'','sort'=>1700]; }
    // Scattered life events through the month
    if ($d === 3)            { $byDay[$d][] = ['real'=>false,'name'=>'Dentist Appointment','time'=>'8:30 AM','att'=>'','sort'=>830]; }
    if ($d === 5)            { $byDay[$d][] = ['real'=>false,'name'=>"Mom's Birthday",'time'=>'','att'=>'','sort'=>0]; }
    if ($d === 7)            { $byDay[$d][] = ['real'=>false,'name'=>'Lunch with Dana','time'=>'12:30 PM','att'=>'','sort'=>1230]; }
    if ($d === 10)           { $byDay[$d][] = ['real'=>false,'name'=>'Movie Night','time'=>'8:00 PM','att'=>'','sort'=>2000]; }
    if ($d === 12)           { $byDay[$d][] = ['real'=>false,'name'=>'Book Club','time'=>'7:30 PM','att'=>'','sort'=>1930]; }
    if ($d === 14)           { $byDay[$d][] = ['real'=>false,'name'=>'Date Night','time'=>'7:00 PM','att'=>'','sort'=>1900]; }
    if ($d === 17)           { $byDay[$d][] = ['real'=>false,'name'=>'Hair Appointment','time'=>'10:00 AM','att'=>'','sort'=>1000]; }
    if ($d === 18)           { $byDay[$d][] = ['real'=>false,'name'=>'Coffee with Alex','time'=>'3:00 PM','att'=>'','sort'=>1500]; }
    if ($d === 20)           { $byDay[$d][] = ['real'=>false,'name'=>"Sarah's Birthday",'time'=>'','att'=>'','sort'=>0]; }
    if ($d >= 22 && $d <= 26){ $byDay[$d][] = ['real'=>false,'name'=>"Vacation \u{2014} Coast",'time'=>'','att'=>'','sort'=>0]; }
    if ($d === 28)           { $byDay[$d][] = ['real'=>false,'name'=>'Brunch with Friends','time'=>'11:00 AM','att'=>'','sort'=>1100]; }
    if ($d === 29)           { $byDay[$d][] = ['real'=>false,'name'=>'Anniversary Dinner','time'=>'7:30 PM','att'=>'','sort'=>1930]; }
    usort($byDay[$d], function ($a, $b) { return ($b['real'] <=> $a['real']) ?: ($a['sort'] <=> $b['sort']); });
}
$totalReal = 0; foreach ($byDay as $L) { foreach ($L as $e) { if ($e['real']) { $totalReal++; } } }
?>
  <div class="cl-header">
    <div class="cl-logo"><span class="cl-logo-icon">&#128197;</span> Calendar</div>
    <div class="cl-search"><input type="text" placeholder="Search"></div>
    <div class="cl-acct" title="<?= htmlspecialchars($calAcct, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($calAcctIn, ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="cl-toolbar">
    <button class="cl-newbtn" type="button">&#43; New event</button>
    <button class="cl-tbtn" type="button">Today</button>
    <button class="cl-tbtn arrow" type="button">&#8249;</button>
    <button class="cl-tbtn arrow" type="button">&#8250;</button>
    <span class="cl-month-lbl"><?= htmlspecialchars($monthName . ' ' . $calYear, ENT_QUOTES, 'UTF-8') ?></span>
    <span class="cl-view">Month &#9662;</span>
  </div>

  <div class="cl-body">
    <aside class="cl-side">
      <div class="cl-mini-h">
        <span><?= htmlspecialchars($monthName . ' ' . $calYear, ENT_QUOTES, 'UTF-8') ?></span>
        <span style="color:#a19f9d">&#8249; &#8250;</span>
      </div>
      <table class="cl-mini">
        <tr><th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th></tr>
        <?php
          $mc = 0; $mr = '<tr>';
          for ($b = 0; $b < $lead; $b++) { $mr .= '<td class="dim"></td>'; $mc++; }
          for ($d = 1; $d <= $daysIn; $d++) {
              $cls = ($d === $todayD) ? ' class="today"' : '';
              $mr .= '<td' . $cls . '>' . $d . '</td>';
              $mc++;
              if ($mc % 7 === 0) { echo $mr . '</tr>'; $mr = '<tr>'; }
          }
          if ($mc % 7 !== 0) { while ($mc % 7 !== 0) { $mr .= '<td class="dim"></td>'; $mc++; } echo $mr . '</tr>'; }
        ?>
      </table>
      <div class="cl-cals">
        <div class="cl-cals-h">My calendars</div>
        <div class="cl-cal-item"><span class="cl-chk" style="background:#0f6cbd"></span> <?= htmlspecialchars($calOwner, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="cl-cal-item"><span class="cl-chk" style="background:#8a8886"></span> Birthdays</div>
        <div class="cl-cal-item"><span class="cl-chk" style="background:#107c10"></span> Holidays</div>
        <div class="cl-cal-item"><span class="cl-chk" style="background:#d83b01"></span> Team Project</div>
      </div>
    </aside>

    <div class="cl-grid-wrap">
      <div class="cl-weekhead">
        <div>Sunday</div><div>Monday</div><div>Tuesday</div><div>Wednesday</div><div>Thursday</div><div>Friday</div><div>Saturday</div>
      </div>
      <div class="cl-month">
        <?php
          $rows  = (int)ceil(($lead + $daysIn) / 7);
          $cells = $rows * 7;
          for ($i = 0; $i < $cells; $i++):
            $dayNum  = $i - $lead + 1;
            $inMonth = ($dayNum >= 1 && $dayNum <= $daysIn);
            $col     = $i % 7;
            $cellCls = 'cl-cell';
            if (!$inMonth)                       { $cellCls .= ' out'; }
            elseif ($col === 0 || $col === 6)    { $cellCls .= ' wknd'; }
            if ($inMonth && $dayNum === $todayD) { $cellCls .= ' is-today'; }
        ?>
        <div class="<?= $cellCls ?>">
          <span class="cl-daynum"><?php
            if ($inMonth) { echo $dayNum; }
            elseif ($dayNum < 1) { echo $dayNum + (int)date('t', mktime(0, 0, 0, $calMonth - 1, 1, $calYear)); }
            else { echo $dayNum - $daysIn; }
          ?></span>
          <?php if ($inMonth):
            $list = $byDay[$dayNum]; $cap = 3; $shown = 0;
            foreach ($list as $e):
              if ($shown >= $cap) { break; }
              $shown++;
          ?>
          <div class="cl-chip <?= $e['real'] ? 'real' : 'fake' ?>" title="<?= htmlspecialchars($e['name'] . ($e['att'] !== '' ? ' — ' . $e['att'] : ''), ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($e['time'] !== ''): ?><span class="ct"><?= htmlspecialchars($e['time'], ENT_QUOTES, 'UTF-8') ?></span> <?php endif; ?><?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?>
            <?php if ($e['real'] && $e['att'] !== ''): ?><span class="ca">&#128100; <?= htmlspecialchars($e['att'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php $extra = count($list) - $shown; if ($extra > 0): ?>
          <span class="cl-more">+<?= $extra ?> more</span>
          <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <div class="cl-statusbar"><?= $calStatus !== '' ? htmlspecialchars($calStatus, ENT_QUOTES, 'UTF-8') : ($totalReal . ' meeting' . ($totalReal === 1 ? '' : 's') . ' this month') ?></div>

<?php elseif ($type === 'inbox'):
$avatarColors = ['#0078d4','#107c10','#d83b01','#5c2d91','#b4009e','#c19c00','#008272','#0099bc'];
function olAvatar(string $name, array $colors): string {
    $initials = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', explode('@', $name)[0]), 0, 2)) ?: 'XX';
    $color = $colors[abs(crc32($name)) % count($colors)];
    return '<div class="ol-email-avatar" style="background:' . $color . '">' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</div>';
}
$fakeEmails = [
    ['from' => 'Sarah Mitchell',  'addr' => 's.mitchell@'.$domain,   'subject' => 'RE: Project update — timeline',           'preview' => 'Thanks for sending that over. I\'ll review and get back to you by end of week.',       'time' => 'Mon 15:42'],
    ['from' => 'IT Helpdesk',     'addr' => 'helpdesk@'.$domain,     'subject' => 'Your support ticket #48291 resolved',      'preview' => 'We have resolved your issue with remote access. Please let us know if the issue persists.', 'time' => 'Mon 09:07'],
    ['from' => 'Thomas Reyes',    'addr' => 't.reyes@'.$domain,      'subject' => 'Lunch Thursday?',                          'preview' => 'A few of us are heading to Caruso\'s on Thursday at noon — you in?',               'time' => 'Fri 17:45'],
    ['from' => 'HR Department',   'addr' => 'hr-noreply@'.$domain,   'subject' => 'Benefits enrollment closes Oct 31',        'preview' => 'This is a reminder that the annual benefits enrollment period ends on October 31st.',  'time' => 'Fri 11:00'],
    ['from' => 'Facilities',      'addr' => 'facilities@'.$domain,   'subject' => 'Planned maintenance — East Wing',          'preview' => 'Please be advised that scheduled maintenance will take place on the 18th between 22:00–02:00.', 'time' => 'Thu 14:22'],
    ['from' => 'Carla Whitmore',  'addr' => 'c.whitmore@'.$domain,   'subject' => 'RE: Quarterly review documents',           'preview' => 'I\'ve attached the updated figures. The discrepancy in column D has been corrected.',    'time' => 'Wed 10:07'],
    ['from' => 'Security Team',   'addr' => 'security@'.$domain,     'subject' => 'Action required: MFA setup by Friday',     'preview' => 'All staff must complete multi-factor authentication setup before Friday 5:00 PM.',    'time' => '18 Oct'],
    ['from' => 'LinkedIn',        'addr' => 'messages-noreply@linkedin.com', 'subject' => '5 new connections this week',      'preview' => 'People you may know are waiting to connect. Expand your professional network today.',   'time' => '15 Oct'],
];
$accountInitials = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $accountName), 0, 2)) ?: 'ME';
?>
<div class="ol-wrap" style="display:flex;flex-direction:column;height:100vh">
  <!-- Header -->
  <header class="ol-header">
    <div class="ol-logo">
      <div class="ol-logo-icon">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="24" height="24" rx="3" fill="#0078d4"/>
          <path d="M4 7h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" fill="#fff" opacity=".15"/>
          <path d="M4 7l8 6 8-6" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/>
          <rect x="4" y="7" width="16" height="10" rx="1" stroke="#fff" stroke-width="1.5" fill="none"/>
        </svg>
      </div>
      <span>Outlook</span>
    </div>
    <div class="ol-search"><input type="text" placeholder="Search"></div>
    <div class="ol-avatar" title="<?= htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($accountInitials, ENT_QUOTES, 'UTF-8') ?></div>
  </header>

  <div class="ol-body">
    <!-- Sidebar -->
    <nav class="ol-sidebar">
      <div class="ol-new-btn"><button>&#43; New message</button></div>
      <ul class="ol-folders">
        <li class="active"><span class="ol-folder-icon">&#128236;</span>Inbox<span class="ol-badge">1</span></li>
        <li><span class="ol-folder-icon">&#128196;</span>Drafts</li>
        <li><span class="ol-folder-icon">&#128228;</span>Sent Items</li>
        <li><span class="ol-folder-icon">&#128465;</span>Deleted Items</li>
        <li><span class="ol-folder-icon">&#9888;</span>Junk Email</li>
        <li><span class="ol-folder-icon">&#128230;</span>Archive</li>
      </ul>
    </nav>

    <!-- Email list -->
    <div class="ol-list">
      <div class="ol-list-header">
        Inbox
        <div class="ol-list-sub"><?= count($fakeEmails) + 1 ?> messages &middot; 1 unread</div>
      </div>
      <div class="ol-emails">
        <!-- The real unread email -->
        <div class="ol-email unread selected" onclick="selectEmail(this, true)">
          <div class="ol-unread-dot"></div>
          <?= olAvatar($emailFrom, $avatarColors) ?>
          <div class="ol-email-body">
            <div class="ol-email-row1">
              <span class="ol-email-sender"><?= htmlspecialchars($emailFrom, ENT_QUOTES, 'UTF-8') ?></span>
              <span class="ol-email-time"><?= htmlspecialchars($emailDateShort, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="ol-email-subject"><?= htmlspecialchars($emailSubject, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="ol-email-preview"><?= htmlspecialchars(strip_tags($page['content'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
        <!-- Fake read emails -->
        <?php foreach ($fakeEmails as $fe): ?>
        <div class="ol-email" onclick="selectEmail(this, false)">
          <?= olAvatar($fe['from'], $avatarColors) ?>
          <div class="ol-email-body">
            <div class="ol-email-row1">
              <span class="ol-email-sender"><?= htmlspecialchars($fe['from'], ENT_QUOTES, 'UTF-8') ?></span>
              <span class="ol-email-time"><?= htmlspecialchars($fe['time'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="ol-email-subject"><?= htmlspecialchars($fe['subject'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="ol-email-preview"><?= htmlspecialchars($fe['preview'], ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Reader pane -->
    <div class="ol-reader">
      <!-- Real email -->
      <div id="ol-real-email" style="display:flex;flex-direction:column;flex:1;overflow:hidden">
        <div class="ol-toolbar">
          <button>&#8592; Reply</button>
          <button>&#8594; Forward</button>
          <button>&#128465; Delete</button>
        </div>
        <div class="ol-reader-header">
          <div class="ol-reader-subject"><?= htmlspecialchars($emailSubject, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="ol-reader-meta">
            <div class="ol-reader-avatar" style="background:<?= $avatarColors[abs(crc32($emailFrom)) % count($avatarColors)] ?>"><?= htmlspecialchars($fromInitials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="ol-reader-info">
              <div class="ol-reader-from"><?= htmlspecialchars($emailFrom, ENT_QUOTES, 'UTF-8') ?></div>
              <div class="ol-reader-addr">&lt;<?= htmlspecialchars($emailFrom, ENT_QUOTES, 'UTF-8') ?>&gt;</div>
              <div class="ol-reader-to">To: <?= htmlspecialchars($emailTo, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="ol-reader-date"><?= htmlspecialchars($emailDate, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
        <div class="ol-reader-body"><?= $page['content'] ?></div>
      </div>
      <!-- Empty state for read emails -->
      <div id="ol-empty-state" class="ol-reader-empty" style="display:none">
        <div class="empty-icon">&#128228;</div>
        <p>This conversation has been archived.</p>
      </div>
    </div>
  </div>
</div>
<script>
function selectEmail(el, isReal) {
  document.querySelectorAll('.ol-email').forEach(function(e) { e.classList.remove('selected'); });
  el.classList.add('selected');
  document.getElementById('ol-real-email').style.display  = isReal ? 'flex' : 'none';
  document.getElementById('ol-empty-state').style.display = isReal ? 'none' : 'flex';
}
</script>

<?php elseif ($type === 'sms'):
$smsContact    = htmlspecialchars($page['page_title'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
$smsDetail     = htmlspecialchars($page['author'] ?? '', ENT_QUOTES, 'UTF-8');
$smsStatus     = htmlspecialchars($page['footer_text'] ?? '', ENT_QUOTES, 'UTF-8');
$rawContactName = $page['page_title'] ?? 'Unknown';
$smsInitials   = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $rawContactName), 0, 2)) ?: '??';
$smsDateLabel  = $page['publish_date']
    ? htmlspecialchars(date('g:i A', strtotime($page['publish_date'])), ENT_QUOTES, 'UTF-8')
    : htmlspecialchars(date('g:i A'), ENT_QUOTES, 'UTF-8');
$smsMessages   = json_decode($page['nav_json'] ?? '[]', true) ?: [];
$smsTime       = htmlspecialchars(date('g:i'), ENT_QUOTES, 'UTF-8');
?>
<div class="ios-screen">
  <!-- Status bar -->
  <div class="ios-status">
    <span><?= $smsTime ?></span>
    <div class="ios-status-icons">
      <svg width="17" height="12" viewBox="0 0 17 12" fill="currentColor"><rect x="0" y="3" width="3" height="9" rx="1"/><rect x="4.5" y="2" width="3" height="10" rx="1"/><rect x="9" y="0" width="3" height="12" rx="1"/><rect x="13.5" y="1" width="3" height="11" rx="1" opacity=".3"/></svg>
      <svg width="16" height="12" viewBox="0 0 16 12" fill="currentColor"><path d="M8 2.5C10.2 2.5 12.2 3.4 13.6 4.9L15 3.5C13.2 1.7 10.7.5 8 .5S2.8 1.7 1 3.5l1.4 1.4C3.8 3.4 5.8 2.5 8 2.5z" opacity=".4"/><path d="M8 5.5c1.4 0 2.7.6 3.6 1.5L13 5.6C11.7 4.3 9.9 3.5 8 3.5S4.3 4.3 3 5.6l1.4 1.4C5.3 6.1 6.6 5.5 8 5.5z" opacity=".7"/><path d="M8 8.5c.8 0 1.5.3 2 .8L11.4 8C10.5 7.1 9.3 6.5 8 6.5S5.5 7.1 4.6 8L6 9.3c.5-.5 1.2-.8 2-.8z"/><circle cx="8" cy="11" r="1.5"/></svg>
      <svg width="25" height="12" viewBox="0 0 25 12" fill="none"><rect x=".5" y=".5" width="22" height="11" rx="3.5" stroke="currentColor" stroke-opacity=".35"/><rect x="1.5" y="1.5" width="19" height="9" rx="2.5" fill="currentColor"/><path d="M23.5 4v4a2 2 0 000-4z" fill="currentColor" opacity=".4"/></svg>
    </div>
  </div>
  <!-- Nav bar -->
  <div class="ios-nav">
    <div class="ios-back"><span class="ios-back-chev">&#8249;</span> Messages</div>
    <div class="ios-contact-center">
      <div class="ios-contact-avatar"><?= htmlspecialchars($smsInitials, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="ios-contact-name"><?= $smsContact ?></div>
      <?php if ($smsDetail): ?><div class="ios-contact-detail"><?= $smsDetail ?></div><?php endif; ?>
    </div>
    <div class="ios-nav-info">&#9432;</div>
  </div>
  <!-- Message thread -->
  <div class="ios-messages">
    <div class="ios-date-label"><?= $smsDateLabel ?></div>
    <?php foreach ($smsMessages as $idx => $msg):
      $text = trim($msg['text'] ?? '');
      if ($text === '') continue;
      $isMe = ($idx % 2 === 1);
    ?>
    <div class="ios-bubble-row <?= $isMe ? 'me' : '' ?>">
      <?php if (!$isMe): ?>
      <div class="ios-sender-avatar"><?= htmlspecialchars($smsInitials, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <div class="ios-bubble <?= $isMe ? 'me' : 'them' ?>"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php endforeach; ?>
    <?php if ($smsStatus): ?><div class="ios-status-line"><?= $smsStatus ?></div><?php endif; ?>
  </div>
  <!-- Input bar -->
  <div class="ios-input-bar">
    <div class="ios-input-add">&#43;</div>
    <div class="ios-input-field">iMessage</div>
    <div class="ios-send-btn">&#8593;</div>
  </div>
</div>

<?php elseif ($type === 'invoice'):
$invBiz     = htmlspecialchars($page['site_name'] ?? 'Acme Co.', ENT_QUOTES, 'UTF-8');
$invNo      = htmlspecialchars($page['page_title'] ?? '', ENT_QUOTES, 'UTF-8');
$invBillTo  = htmlspecialchars($page['author'] ?? '', ENT_QUOTES, 'UTF-8');
$invNotes   = htmlspecialchars($page['footer_text'] ?? '', ENT_QUOTES, 'UTF-8');
$invDate    = $page['publish_date']
    ? htmlspecialchars(date('F j, Y', strtotime($page['publish_date'])), ENT_QUOTES, 'UTF-8')
    : htmlspecialchars(date('F j, Y'), ENT_QUOTES, 'UTF-8');
$invLines   = json_decode($page['nav_json'] ?? '[]', true) ?: [];
$invTotal   = 0.0;
foreach ($invLines as $li) {
    $invTotal += (float)($li['qty'] ?? 0) * (float)($li['price'] ?? 0);
}
?>
<div class="inv-sheet">
  <div class="inv-top">
    <div>
      <div class="inv-biz"><?= $invBiz ?></div>
      <div class="inv-biz-sub">Billing Department</div>
    </div>
    <div class="inv-label">
      <h1>Invoice</h1>
      <?php if ($invNo): ?><div class="inv-no">No. <?= $invNo ?></div><?php endif; ?>
      <div class="inv-date"><?= $invDate ?></div>
    </div>
  </div>

  <?php if ($invBillTo): ?>
  <div class="inv-billto">
    <div class="lbl">Bill To</div>
    <div class="name"><?= $invBillTo ?></div>
  </div>
  <?php endif; ?>

  <table class="inv-table">
    <thead>
      <tr>
        <th>Item</th>
        <th>Description</th>
        <th class="num">Qty</th>
        <th class="num">Unit Price</th>
        <th class="num">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($invLines as $li):
        $qty    = (float)($li['qty'] ?? 0);
        $price  = (float)($li['price'] ?? 0);
        $amount = $qty * $price;
        if (trim((string)($li['item'] ?? '')) === '' && $qty === 0.0 && $price === 0.0) continue;
      ?>
      <tr>
        <td class="it-name"><?= htmlspecialchars($li['item'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td><div class="it-desc"><?= htmlspecialchars($li['desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></div></td>
        <td class="num"><?= htmlspecialchars(rtrim(rtrim(number_format($qty, 2), '0'), '.'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="num">$<?= htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="num">$<?= htmlspecialchars(number_format($amount, 2), ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="inv-totals">
    <div class="inv-totals-box">
      <div class="inv-trow"><span>Subtotal</span><span>$<?= htmlspecialchars(number_format($invTotal, 2), ENT_QUOTES, 'UTF-8') ?></span></div>
      <div class="inv-trow grand"><span>Total</span><span>$<?= htmlspecialchars(number_format($invTotal, 2), ENT_QUOTES, 'UTF-8') ?></span></div>
    </div>
  </div>

  <?php if ($invNotes): ?>
  <div class="inv-foot">
    <div class="lbl">Notes</div>
    <?= $invNotes ?>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($type === 'receipt'):
$rcStore   = htmlspecialchars($page['site_name'] ?? 'Walmart', ENT_QUOTES, 'UTF-8');
$rcAddr    = htmlspecialchars($page['page_title'] ?? '', ENT_QUOTES, 'UTF-8');
$rcCashier = htmlspecialchars($page['author'] ?? '', ENT_QUOTES, 'UTF-8');
$rcFoot    = htmlspecialchars($page['footer_text'] ?? '', ENT_QUOTES, 'UTF-8');
$rcDate    = $page['publish_date']
    ? htmlspecialchars(date('m/d/y', strtotime($page['publish_date'])), ENT_QUOTES, 'UTF-8')
    : htmlspecialchars(date('m/d/y'), ENT_QUOTES, 'UTF-8');
$rcTime    = $page['publish_date']
    ? htmlspecialchars(date('h:i A', strtotime($page['publish_date'])), ENT_QUOTES, 'UTF-8')
    : htmlspecialchars(date('h:i A'), ENT_QUOTES, 'UTF-8');
$rcLines   = json_decode($page['nav_json'] ?? '[]', true) ?: [];
$rcTotal   = 0.0;
foreach ($rcLines as $rl) { $rcTotal += (float)($rl['price'] ?? 0); }
$rcCount   = 0;
?>
<div class="rcpt">
  <div class="rcpt-head">
    <div class="rcpt-store"><?= $rcStore ?></div>
    <div class="rcpt-tag">Save money. Live better.</div>
    <?php if ($rcAddr): ?><div class="rcpt-addr"><?= $rcAddr ?></div><?php endif; ?>
  </div>
  <hr class="rcpt-sep">
  <div class="rcpt-meta">
    <?php if ($rcCashier): ?><span><?= $rcCashier ?></span><?php endif; ?>
    <span><?= $rcDate ?> <?= $rcTime ?></span>
  </div>
  <hr class="rcpt-sep">
  <div class="rcpt-items">
    <?php foreach ($rcLines as $rl):
      $name  = trim((string)($rl['item'] ?? ''));
      $price = (float)($rl['price'] ?? 0);
      if ($name === '' && $price === 0.0) continue;
      $rcCount++;
    ?>
    <div class="rcpt-row">
      <span class="ri-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
      <span class="ri-price"><?= htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <hr class="rcpt-sep">
  <div class="rcpt-tot">
    <div class="rcpt-row"><span>SUBTOTAL</span><span><?= htmlspecialchars(number_format($rcTotal, 2), ENT_QUOTES, 'UTF-8') ?></span></div>
    <div class="rcpt-row grand"><span>TOTAL</span><span>$<?= htmlspecialchars(number_format($rcTotal, 2), ENT_QUOTES, 'UTF-8') ?></span></div>
  </div>
  <hr class="rcpt-sep">
  <div class="rcpt-meta">
    <span>ITEMS SOLD <?= $rcCount ?></span>
    <span>DEBIT TEND $<?= htmlspecialchars(number_format($rcTotal, 2), ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <div class="rcpt-foot">
    <?php if ($rcFoot): ?><?= $rcFoot ?><br><?php endif; ?>
    *** CUSTOMER COPY ***
  </div>
  <div class="rcpt-barcode">
    <div class="bars">||| |||| | ||| || |||| |||</div>
    <div class="num"><?= htmlspecialchars(date('Ymd') . str_pad((string)($page['id'] ?? 0), 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8') ?></div>
  </div>
</div>

<?php elseif ($type === 'map'):
$mapArea    = htmlspecialchars($page['site_name'] ?? 'Map', ENT_QUOTES, 'UTF-8');
$mapSub     = htmlspecialchars($page['author'] ?? '', ENT_QUOTES, 'UTF-8');
$mapCaption = htmlspecialchars($page['footer_text'] ?? '', ENT_QUOTES, 'UTF-8');
$mapDecoded = json_decode($page['nav_json'] ?? '[]', true) ?: [];
if (isset($mapDecoded['markers']) && is_array($mapDecoded['markers'])) {
    $mapKind    = ($mapDecoded['type'] ?? 'street') === 'festival' ? 'festival' : 'street';
    $mapMarkers = $mapDecoded['markers'];
} else {
    $mapKind    = 'street';
    $mapMarkers = is_array($mapDecoded) ? $mapDecoded : [];
}
$mapPos = $mapKind === 'festival'
    ? [[38, 50], [63, 22], [60, 52], [16, 44], [41, 80], [80, 38]]
    : [[20, 27], [71, 20], [47, 53], [29, 75], [79, 67], [50, 14]];
?>
<div class="gmap-header">
  <div class="gmap-pin-mini"></div>
  <div>
    <div class="gmap-title"><?= $mapArea ?></div>
    <?php if ($mapSub): ?><div class="gmap-sub"><?= $mapSub ?></div><?php endif; ?>
  </div>
</div>
<div class="gmap-frame">
  <?php if ($mapKind === 'festival'): ?>
  <svg class="gmap-svg" viewBox="0 0 1000 640" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" font-family="Arial,sans-serif">
    <rect width="1000" height="640" fill="#a6d785"/>
    <ellipse cx="510" cy="340" rx="430" ry="250" fill="#aedb8e"/>
    <!-- River -->
    <path d="M 870,-20 C 915,120 820,220 885,360 C 935,470 855,560 905,660 L 1020,660 L 1020,-20 Z" fill="#bcdff0"/>
    <path d="M 880,-20 C 925,120 830,220 895,360 C 945,470 865,560 915,660" stroke="#a6d2ea" stroke-width="3" fill="none"/>
    <!-- Parking lot -->
    <rect x="22" y="110" width="300" height="380" rx="6" fill="#9b9b9b"/>
    <g stroke="#e8e8e8" stroke-width="2">
      <line x1="22" y1="180" x2="322" y2="180"/><line x1="22" y1="250" x2="322" y2="250"/>
      <line x1="22" y1="320" x2="322" y2="320"/><line x1="22" y1="390" x2="322" y2="390"/>
      <line x1="172" y1="110" x2="172" y2="490"/>
      <?php for ($fx = 60; $fx < 322; $fx += 38): ?><line x1="<?= $fx ?>" y1="110" x2="<?= $fx ?>" y2="180"/><line x1="<?= $fx ?>" y1="320" x2="<?= $fx ?>" y2="390"/><?php endfor; ?>
    </g>
    <g>
      <rect x="34" y="124" width="24" height="42" rx="3" fill="#d9534f"/><rect x="72" y="124" width="24" height="42" rx="3" fill="#5b9bd5"/>
      <rect x="110" y="124" width="24" height="42" rx="3" fill="#f0ad4e"/><rect x="186" y="124" width="24" height="42" rx="3" fill="#777"/>
      <rect x="224" y="124" width="24" height="42" rx="3" fill="#5cb85c"/><rect x="262" y="124" width="24" height="42" rx="3" fill="#d9534f"/>
      <rect x="34" y="332" width="24" height="42" rx="3" fill="#5b9bd5"/><rect x="110" y="332" width="24" height="42" rx="3" fill="#999"/>
      <rect x="224" y="332" width="50" height="44" rx="4" fill="#e0e0e0"/>
      <rect x="186" y="404" width="58" height="22" rx="4" fill="#4a6fa5"/><rect x="252" y="404" width="58" height="22" rx="4" fill="#c0504d"/>
    </g>
    <!-- Paths -->
    <g stroke="#dccfa6" stroke-width="24" fill="none" stroke-linecap="round">
      <path d="M 322,300 L 410,300 L 600,330 L 760,260"/>
      <path d="M 410,300 L 410,512"/>
      <path d="M 600,330 L 630,150"/>
    </g>
    <!-- Buildings -->
    <g>
      <rect x="350" y="60" width="150" height="80" fill="#3a3f44"/><rect x="350" y="52" width="150" height="12" fill="#2a2e33"/>
      <rect x="520" y="78" width="120" height="64" fill="#f2f2f2" stroke="#cfcfcf"/><path d="M520,78 L580,52 L640,78 Z" fill="#dfe3e6"/>
    </g>
    <!-- Stage -->
    <g>
      <rect x="585" y="120" width="120" height="62" rx="3" fill="#6b7a8f"/>
      <path d="M585,120 L645,92 L705,120 Z" fill="#37506b"/>
      <rect x="600" y="132" width="90" height="38" fill="#26323f"/>
      <g fill="#cfd6dd"><circle cx="620" cy="200" r="4"/><circle cx="640" cy="205" r="4"/><circle cx="660" cy="200" r="4"/><circle cx="680" cy="206" r="4"/><circle cx="630" cy="214" r="4"/><circle cx="668" cy="214" r="4"/></g>
    </g>
    <!-- Ferris wheel -->
    <g transform="translate(380,320)" stroke="#c0392b" stroke-width="4" fill="none">
      <line x1="0" y1="0" x2="-46" y2="118" stroke="#888" stroke-width="7"/>
      <line x1="0" y1="0" x2="46" y2="118" stroke="#888" stroke-width="7"/>
      <circle cx="0" cy="0" r="92"/><circle cx="0" cy="0" r="62" stroke-width="3"/>
      <g stroke-width="3"><line x1="0" y1="-92" x2="0" y2="92"/><line x1="-92" y1="0" x2="92" y2="0"/><line x1="-65" y1="-65" x2="65" y2="65"/><line x1="65" y1="-65" x2="-65" y2="65"/></g>
      <g fill="#3a87c8" stroke="none"><circle cx="0" cy="-92" r="10"/><circle cx="65" cy="-65" r="10"/><circle cx="92" cy="0" r="10"/><circle cx="65" cy="65" r="10"/><circle cx="0" cy="92" r="10"/><circle cx="-65" cy="65" r="10"/><circle cx="-92" cy="0" r="10"/><circle cx="-65" cy="-65" r="10"/></g>
      <circle cx="0" cy="0" r="9" fill="#c0392b" stroke="none"/>
    </g>
    <!-- Red big-top tent -->
    <g transform="translate(600,330)">
      <ellipse cx="0" cy="58" rx="78" ry="14" fill="#000" opacity=".08"/>
      <path d="M-72,46 H72 V70 Q0,84 -72,70 Z" fill="#f3f3f3" stroke="#d24"/>
      <path d="M0,-66 L78,46 H-78 Z" fill="#fff"/>
      <path d="M0,-66 L14,46 H-14 Z" fill="#d9352b"/><path d="M0,-66 L44,46 H22 Z" fill="#d9352b"/><path d="M0,-66 L-22,46 H-44 Z" fill="#d9352b"/><path d="M0,-66 L78,46 H64 Z" fill="#d9352b"/><path d="M0,-66 L-64,46 H-78 Z" fill="#d9352b"/>
      <path d="M-78,46 q9,12 18,0 q9,12 18,0 q9,12 18,0 q9,12 18,0 q9,12 18,0 q9,12 18,0 q9,12 18,0 q9,12 18,0" fill="none" stroke="#d9352b" stroke-width="3"/>
      <line x1="0" y1="-66" x2="0" y2="-84" stroke="#b22" stroke-width="3"/><path d="M0,-84 L18,-78 L0,-72 Z" fill="#d9352b"/>
    </g>
    <!-- Blue striped tents -->
    <g transform="translate(720,250) scale(.8)">
      <path d="M-60,40 H60 V60 Q0,72 -60,60 Z" fill="#f3f3f3" stroke="#2a72b5"/>
      <path d="M0,-56 L66,40 H-66 Z" fill="#fff"/>
      <path d="M0,-56 L12,40 H-12 Z" fill="#2f86c9"/><path d="M0,-56 L40,40 H20 Z" fill="#2f86c9"/><path d="M0,-56 L-20,40 H-40 Z" fill="#2f86c9"/>
      <line x1="0" y1="-56" x2="0" y2="-72" stroke="#1f5e93" stroke-width="3"/><path d="M0,-72 L16,-66 L0,-60 Z" fill="#2f86c9"/>
    </g>
    <g transform="translate(800,330) scale(.7)">
      <path d="M-60,40 H60 V60 Q0,72 -60,60 Z" fill="#f3f3f3" stroke="#2a72b5"/>
      <path d="M0,-56 L66,40 H-66 Z" fill="#fff"/>
      <path d="M0,-56 L12,40 H-12 Z" fill="#2f86c9"/><path d="M0,-56 L40,40 H20 Z" fill="#2f86c9"/><path d="M0,-56 L-20,40 H-40 Z" fill="#2f86c9"/>
    </g>
    <!-- Carousel -->
    <g transform="translate(410,512)">
      <ellipse cx="0" cy="40" rx="56" ry="14" fill="#000" opacity=".08"/>
      <circle cx="0" cy="20" r="50" fill="#ede7da"/>
      <path d="M0,-54 L54,18 H-54 Z" fill="#fff"/>
      <path d="M0,-54 L12,18 H-12 Z" fill="#e8a33d"/><path d="M0,-54 L38,18 H18 Z" fill="#e8a33d"/><path d="M0,-54 L-18,18 H-38 Z" fill="#e8a33d"/>
      <line x1="0" y1="-54" x2="0" y2="-70" stroke="#c98a2a" stroke-width="3"/><circle cx="0" cy="-72" r="4" fill="#e8a33d"/>
      <g stroke="#c9b48a" stroke-width="3"><line x1="-40" y1="6" x2="-40" y2="44"/><line x1="0" y1="20" x2="0" y2="58"/><line x1="40" y1="6" x2="40" y2="44"/></g>
    </g>
    <!-- Trees -->
    <g>
      <?php
        $ftrees = [[60,540],[120,560],[180,540],[250,560],[330,580],[420,600],[40,60],[80,30],[150,40],[240,60],[470,30],[560,40],[670,60],[770,40],[900,90],[950,200],[960,360],[940,500],[860,560],[760,560],[660,580],[560,560],[480,560]];
        foreach ($ftrees as $t): [$tx,$ty]=$t; ?>
      <g transform="translate(<?= $tx ?>,<?= $ty ?>)"><rect x="-3" y="8" width="6" height="14" fill="#7a5230"/><circle cx="0" cy="0" r="15" fill="#4f9d4f"/><circle cx="-10" cy="7" r="12" fill="#5aa85a"/><circle cx="10" cy="7" r="12" fill="#5aa85a"/><circle cx="0" cy="-8" r="11" fill="#62b562"/></g>
      <?php endforeach; ?>
    </g>
  </svg>
  <?php else: ?>
  <svg class="gmap-svg" viewBox="0 0 1000 640" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <rect width="1000" height="640" fill="#e9eaed"/>
    <!-- Parks -->
    <rect x="55" y="55" width="205" height="150" rx="10" fill="#c6e8c9"/>
    <text x="157" y="135" font-size="15" fill="#3c763d" text-anchor="middle" font-style="italic">Linden Park</text>
    <rect x="735" y="425" width="230" height="180" rx="10" fill="#c6e8c9"/>
    <text x="850" y="505" font-size="15" fill="#3c763d" text-anchor="middle" font-style="italic">Wexford Gardens</text>
    <ellipse cx="850" cy="545" rx="36" ry="21" fill="#a9d3ec"/>
    <!-- River -->
    <path d="M -20,495 C 200,465 320,600 520,555 C 700,520 820,640 1020,595 L 1020,690 L -20,690 Z" fill="#a9d3ec"/>
    <text x="305" y="558" font-size="14" fill="#5a8fb0" font-style="italic" transform="rotate(-6 305 558)">River Wenn</text>
    <!-- Blocks -->
    <g fill="#dfe1e5">
      <rect x="335" y="78" width="125" height="92" rx="4"/>
      <rect x="525" y="88" width="150" height="100" rx="4"/>
      <rect x="360" y="252" width="115" height="112" rx="4"/>
      <rect x="560" y="262" width="140" height="120" rx="4"/>
      <rect x="120" y="300" width="120" height="120" rx="4"/>
      <rect x="785" y="118" width="130" height="118" rx="4"/>
    </g>
    <!-- Road casing -->
    <g stroke="#c8cbd0" stroke-linecap="round" fill="none">
      <line x1="0" y1="110" x2="1000" y2="110" stroke-width="16"/>
      <line x1="0" y1="230" x2="1000" y2="230" stroke-width="16"/>
      <line x1="0" y1="400" x2="1000" y2="400" stroke-width="16"/>
      <line x1="0" y1="540" x2="1000" y2="540" stroke-width="16"/>
      <line x1="170" y1="0" x2="170" y2="640" stroke-width="16"/>
      <line x1="330" y1="0" x2="330" y2="640" stroke-width="16"/>
      <line x1="500" y1="0" x2="500" y2="640" stroke-width="16"/>
      <line x1="700" y1="0" x2="700" y2="640" stroke-width="16"/>
      <line x1="860" y1="0" x2="860" y2="640" stroke-width="16"/>
      <line x1="-20" y1="650" x2="1020" y2="90" stroke-width="24"/>
    </g>
    <!-- Roads -->
    <g stroke="#ffffff" stroke-linecap="round" fill="none">
      <line x1="0" y1="110" x2="1000" y2="110" stroke-width="11"/>
      <line x1="0" y1="230" x2="1000" y2="230" stroke-width="11"/>
      <line x1="0" y1="400" x2="1000" y2="400" stroke-width="11"/>
      <line x1="0" y1="540" x2="1000" y2="540" stroke-width="11"/>
      <line x1="170" y1="0" x2="170" y2="640" stroke-width="11"/>
      <line x1="330" y1="0" x2="330" y2="640" stroke-width="11"/>
      <line x1="500" y1="0" x2="500" y2="640" stroke-width="11"/>
      <line x1="700" y1="0" x2="700" y2="640" stroke-width="11"/>
      <line x1="860" y1="0" x2="860" y2="640" stroke-width="11"/>
      <line x1="-20" y1="650" x2="1020" y2="90" stroke-width="17"/>
      <line x1="-20" y1="650" x2="1020" y2="90" stroke-width="2" stroke="#f4c14e" stroke-dasharray="14 12"/>
    </g>
    <!-- Street names -->
    <g font-size="12" fill="#9aa0a6" font-family="Arial,sans-serif">
      <text x="60" y="105">Harlow St</text>
      <text x="60" y="225">Bramble Rd</text>
      <text x="60" y="395">Kingsford Ave</text>
      <text x="60" y="535">Cedar Way</text>
      <text x="176" y="615" transform="rotate(-90 176 615)">Vine St</text>
      <text x="336" y="615" transform="rotate(-90 336 615)">Ashby Rd</text>
      <text x="506" y="615" transform="rotate(-90 506 615)">Marlow Blvd</text>
      <text x="706" y="615" transform="rotate(-90 706 615)">Pellic St</text>
      <text x="866" y="615" transform="rotate(-90 866 615)">Quarry Ln</text>
      <text x="540" y="350" fill="#b88a2e" transform="rotate(-28 540 350)">Old Mill Rd</text>
    </g>
    <!-- Points of interest -->
    <g font-size="12" font-family="Arial,sans-serif">
      <circle cx="250" cy="468" r="6" fill="#4285f4"/>
      <text x="262" y="472" fill="#3c4043">Ashgrove School</text>
      <circle cx="615" cy="430" r="6" fill="#f29900"/>
      <text x="627" y="434" fill="#3c4043">Corner Market</text>
      <circle cx="445" cy="178" r="6" fill="#a0522d"/>
      <text x="457" y="182" fill="#3c4043">Tilden Caf&#233;</text>
      <circle cx="760" cy="328" r="6" fill="#9334e6"/>
      <text x="772" y="332" fill="#3c4043">St. Brae's Church</text>
      <circle cx="140" cy="178" r="6" fill="#ea4335"/>
      <text x="152" y="182" fill="#3c4043">Mercy Clinic</text>
      <circle cx="905" cy="300" r="6" fill="#1a73e8"/>
      <text x="828" y="290" fill="#3c4043">Bishop &amp; Co.</text>
    </g>
  </svg>
  <?php endif; ?>

  <?php foreach ($mapMarkers as $i => $mk):
    if ($i > 5) break;
    $label = trim((string)($mk['label'] ?? ''));
    if ($label === '') continue;
    [$px, $py] = $mapPos[$i];
  ?>
  <div class="gmap-pin" style="left:<?= $px ?>%;top:<?= $py ?>%">
    <svg width="30" height="42" viewBox="0 0 30 42" xmlns="http://www.w3.org/2000/svg">
      <path d="M15 0C7 0 0 6.6 0 15c0 11 15 27 15 27s15-16 15-27C30 6.6 23 0 15 0z" fill="#ea4335"/>
      <circle cx="15" cy="15" r="9" fill="#b31412"/>
      <text x="15" y="19" text-anchor="middle" font-size="11" font-weight="700" fill="#fff"><?= $i + 1 ?></text>
    </svg>
    <span class="gmap-pin-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <?php endforeach; ?>

  <div class="gmap-zoom"><button>&#43;</button><button>&#8722;</button></div>
  <?php if ($mapCaption): ?><div class="gmap-footer"><?= $mapCaption ?></div><?php endif; ?>
  <div class="gmap-cred">Map data &copy;<?= date('Y') ?></div>
</div>

<?php endif; ?>
</body>
</html>
