<?php
declare(strict_types=1);

class CluePageController
{
    private CluePageModel $pageModel;
    private ClueModel     $clueModel;

    public function __construct()
    {
        $this->pageModel = new CluePageModel();
        $this->clueModel = new ClueModel();
    }

    // ── Public: render fake website ───────────────────────────────────────────

    public function show(string $slug): void
    {
        $page     = $this->pageModel->findBySlug($slug) ?: notFound();
        $navItems = json_decode($page['nav_json'] ?? '[]', true) ?: [];
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        require APP_ROOT . '/views/public/page.php';
        exit;
    }

    // ── Admin: edit / create page linked to a clue ────────────────────────────

    public function editPage(int $clueId): void
    {
        requireAdmin();
        $clue = $this->clueModel->findById($clueId) ?: notFound();
        $page = $this->pageModel->findByClueId($clueId);

        // nav_json holds structured data (messages, line items, markers, blog comment)
        // for these page types, not nav links
        if ($page && in_array($page['site_type'] ?? '', ['sms', 'invoice', 'receipt', 'map', 'blog', 'calendar', 'corporate', 'archive', 'access_log'], true)) {
            $navItems = [];
        } elseif ($page) {
            $navItems = json_decode($page['nav_json'] ?? '[]', true) ?: [];
        } else {
            $navItems = [
                ['label' => 'Home',    'href' => '#'],
                ['label' => 'Archive', 'href' => '#'],
                ['label' => 'Contact', 'href' => '#'],
            ];
        }

        view('admin.pages.edit', [
            'pageTitle' => 'Clue Page',
            'activeNav' => 'sequences',
            'clue'      => $clue,
            'page'      => $page ?: null,
            'navItems'  => $navItems,
            'sequence'  => (new SequenceModel())->findById((int)$clue['sequence_id']) ?: null,
            'flash'     => getFlash(),
            'errors'    => [],
        ]);
    }

    public function savePage(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $clue = $this->clueModel->findById($clueId) ?: notFound();

        $errors  = [];
        // Default the slug to the site/area/business name (spaces → dashes) when blank.
        $slug    = slugify($_POST['slug'] ?? '') ?: slugify($_POST['site_name'] ?? '');
        if (empty(trim($_POST['page_title'] ?? ''))) {
            $errors[] = 'Page title is required.';
        }
        if (empty(trim($_POST['site_name'] ?? ''))) {
            $errors[] = 'Site name is required.';
        }
        if (empty($slug)) {
            $errors[] = 'Enter a site name so a URL slug can be generated.';
        }

        $siteType = $_POST['site_type'] ?? 'news';
        if (!in_array($siteType, ['news', 'corporate', 'blog', 'archive', 'calendar', 'inbox', 'sms', 'invoice', 'receipt', 'map', 'access_log'], true)) {
            $siteType = 'news';
        }

        // Build nav_json — sms stores message texts, invoice stores line items, others store nav links
        $navItems     = [];
        $navJsonValue = null;
        if ($siteType === 'sms') {
            $msgs = [];
            for ($i = 1; $i <= 4; $i++) {
                $msgs[] = ['text' => Security::sanitizeString($_POST['sms_msg' . $i] ?? '')];
            }
            $navJsonValue = json_encode($msgs, JSON_UNESCAPED_UNICODE);
        } elseif ($siteType === 'invoice') {
            $lines = [];
            for ($i = 1; $i <= 2; $i++) {
                $lines[] = [
                    'item'  => Security::sanitizeString($_POST['inv_item' . $i] ?? ''),
                    'desc'  => Security::sanitizeString($_POST['inv_desc' . $i] ?? ''),
                    'qty'   => Security::sanitizeString($_POST['inv_qty' . $i] ?? ''),
                    'price' => Security::sanitizeString($_POST['inv_price' . $i] ?? ''),
                ];
            }
            $navJsonValue = json_encode($lines, JSON_UNESCAPED_UNICODE);
        } elseif ($siteType === 'receipt') {
            $rcKind = in_array($_POST['rcpt_kind'] ?? 'store', ['store', 'delivery'], true)
                ? ($_POST['rcpt_kind'] ?? 'store') : 'store';
            if ($rcKind === 'delivery') {
                $dItems = [];
                for ($i = 1; $i <= 6; $i++) {
                    $dItems[] = [
                        'item' => Security::sanitizeString($_POST['rcpt_ditem' . $i] ?? ''),
                        'room' => Security::sanitizeString($_POST['rcpt_droom' . $i] ?? ''),
                    ];
                }
                // Delivery time is paired with publish_date when rendering.
                // Stored as the raw HH:MM the <input type="time"> gave us; empty
                // string means "no specific time — show only the date".
                $dTime = trim((string)($_POST['rcpt_time'] ?? ''));
                if ($dTime !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $dTime)) {
                    $dTime = '';
                }
                $navJsonValue = json_encode([
                    'type'    => 'delivery',
                    'address' => Security::sanitizeString($_POST['rcpt_address'] ?? ''),
                    'time'    => $dTime,
                    'items'   => $dItems,
                    'notes'   => Security::sanitizeString($_POST['rcpt_notes'] ?? ''),
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // Store receipt — keep the raw line-item array shape for backward compatibility.
                $lines = [];
                for ($i = 1; $i <= 4; $i++) {
                    $lines[] = [
                        'item'  => Security::sanitizeString($_POST['rcpt_item' . $i] ?? ''),
                        'price' => Security::sanitizeString($_POST['rcpt_price' . $i] ?? ''),
                    ];
                }
                $navJsonValue = json_encode($lines, JSON_UNESCAPED_UNICODE);
            }
        } elseif ($siteType === 'map') {
            $markers = [];
            for ($i = 1; $i <= 6; $i++) {
                $markers[] = ['label' => Security::sanitizeString($_POST['map_marker' . $i] ?? '')];
            }
            $mapKind = in_array($_POST['map_kind'] ?? 'street', ['street', 'festival', 'floorplan'], true)
                ? $_POST['map_kind'] : 'street';
            $navJsonValue = json_encode(['type' => $mapKind, 'markers' => $markers], JSON_UNESCAPED_UNICODE);
        } elseif ($siteType === 'access_log') {
            // Sign-in/sign-out style log. Up to 10 entries; the log title lives
            // in site_name and the header date in publish_date.
            $entries = [];
            for ($i = 1; $i <= 10; $i++) {
                $entries[] = [
                    'time'     => Security::sanitizeString($_POST['log_time'     . $i] ?? ''),
                    'initials' => Security::sanitizeString($_POST['log_initials' . $i] ?? ''),
                    'desc'     => Security::sanitizeString($_POST['log_desc'     . $i] ?? ''),
                ];
            }
            $navJsonValue = json_encode(['type' => 'access_log', 'entries' => $entries], JSON_UNESCAPED_UNICODE);
        } elseif ($siteType === 'blog') {
            $navJsonValue = json_encode([
                'cmt_name' => Security::sanitizeString($_POST['blog_cmt_name'] ?? ''),
                'cmt_text' => Security::sanitizeString($_POST['blog_cmt_text'] ?? ''),
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($siteType === 'corporate') {
            $navJsonValue = json_encode([
                'cmt_name' => Security::sanitizeString($_POST['corp_cmt_name'] ?? ''),
                'cmt_text' => Security::sanitizeString($_POST['corp_cmt_text'] ?? ''),
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($siteType === 'archive') {
            $navJsonValue = json_encode([
                'log_op'     => Security::sanitizeString($_POST['arc_log_op'] ?? ''),
                'log_action' => Security::sanitizeString($_POST['arc_log_action'] ?? ''),
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($siteType === 'calendar') {
            $events = [];
            for ($i = 1; $i <= 3; $i++) {
                $events[] = [
                    'name'      => Security::sanitizeString($_POST['cal_name' . $i] ?? ''),
                    'day'       => max(0, min(31, (int)($_POST['cal_day' . $i] ?? 0))),
                    'time'      => Security::sanitizeString($_POST['cal_time' . $i] ?? ''),
                    'attendees' => Security::sanitizeString($_POST['cal_attendees' . $i] ?? ''),
                ];
            }
            $refTs   = !empty($_POST['publish_date']) ? strtotime((string)$_POST['publish_date']) : false;
            if ($refTs === false) { $refTs = time(); }
            $navJsonValue = json_encode([
                'month'  => (int)date('n', $refTs),
                'year'   => (int)date('Y', $refTs),
                'events' => $events,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $navLabels = $_POST['nav_label'] ?? [];
            $navHrefs  = $_POST['nav_href']  ?? [];
            foreach ($navLabels as $i => $label) {
                $label = trim($label);
                if ($label !== '') {
                    $navItems[] = ['label' => $label, 'href' => trim($navHrefs[$i] ?? '#')];
                }
            }
            $navJsonValue = !empty($navItems) ? json_encode($navItems, JSON_UNESCAPED_UNICODE) : null;
        }

        $existing  = $this->pageModel->findByClueId($clueId);
        $excludeId = $existing ? (int)$existing['id'] : 0;

        if (empty($errors) && $this->pageModel->slugExists($slug, $excludeId)) {
            $errors[] = 'That URL slug is already in use by another page.';
        }

        $data = [
            'clue_id'      => $clueId,
            'slug'         => $slug,
            'site_type'    => $siteType,
            'site_name'    => Security::sanitizeString($_POST['site_name'] ?? ''),
            'page_title'   => Security::sanitizeString($_POST['page_title'] ?? ''),
            'author'       => Security::sanitizeString($_POST['author'] ?? '') ?: null,
            'publish_date' => !empty($_POST['publish_date']) ? $_POST['publish_date'] : null,
            'content'      => $_POST['content'] ?? null,
            'reward_content' => $_POST['reward_content'] ?? null,
            'nav_json'     => $navJsonValue,
            'footer_text'  => Security::sanitizeString($_POST['footer_text'] ?? '') ?: null,
        ];

        if (!empty($errors)) {
            view('admin.pages.edit', [
                'pageTitle' => 'Clue Page',
                'activeNav' => 'sequences',
                'clue'      => $clue,
                'page'      => $existing ?: null,
                'navItems'  => $navItems ?: [['label' => 'Home', 'href' => '#']],
                'sequence'  => (new SequenceModel())->findById((int)$clue['sequence_id']) ?: null,
                'flash'     => null,
                'errors'    => $errors,
                'input'     => $_POST,
            ]);
            return;
        }

        if ($existing) {
            $this->pageModel->update((int)$existing['id'], $data);
        } else {
            $this->pageModel->create($data);
        }

        flash('success', 'Decoy page saved.');
        redirect('/admin/sequences/' . (int)$clue['sequence_id'] . '/clues');
    }

    public function deletePage(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $page = $this->pageModel->findByClueId($clueId) ?: notFound();
        $this->pageModel->delete((int)$page['id']);

        $clue  = $this->clueModel->findById($clueId);
        $seqId = $clue ? (int)$clue['sequence_id'] : 0;
        flash('success', 'Decoy page deleted.');
        redirect('/admin/sequences/' . $seqId . '/clues');
    }
}
