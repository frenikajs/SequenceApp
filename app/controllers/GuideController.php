<?php
declare(strict_types=1);

class GuideController
{
    private SettingsModel $settings;

    /** Sequence types that each get their own How to Play guide. */
    private const TYPES = [
        'sequential' => 'Sequential',
        'open'       => 'Open',
        'gameboard'  => 'Game Board',
        'whodunit'   => 'Who-dun-it',
        'interactive' => 'Interactive',
    ];

    public function __construct()
    {
        $this->settings = new SettingsModel();
    }

    private function guideKey(string $type): string
    {
        return 'howto_guide_' . $type;
    }

    /** Per-type guide, falling back to the legacy single guide if a type has none. */
    private function guideFor(string $type): string
    {
        $guide = $this->settings->get($this->guideKey($type), '');
        if ($guide === '') {
            $guide = $this->settings->get('howto_guide', '');
        }
        return $guide;
    }

    // ── Public: render the How to Play guide ──────────────────────────────────

    public function show(): void
    {
        $type = (string)($_GET['type'] ?? '');
        $type = isset(self::TYPES[$type]) ? $type : '';
        $guide     = $type !== '' ? $this->guideFor($type) : $this->settings->get('howto_guide', '');
        $typeLabel = $type !== '' ? self::TYPES[$type] : '';
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        require APP_ROOT . '/views/public/how_to_play.php';
        exit;
    }

    // ── Admin: edit / save the How to Play guide ──────────────────────────────

    public function edit(): void
    {
        requireAdmin();
        $type = (string)($_GET['type'] ?? 'sequential');
        if (!isset(self::TYPES[$type])) {
            $type = 'sequential';
        }
        view('admin.guide', [
            'pageTitle'  => 'How to Play Guide',
            'activeNav'  => 'guide',
            'guide'      => $this->settings->get($this->guideKey($type), ''),
            'guideType'  => $type,
            'guideTypes' => self::TYPES,
            'flash'      => getFlash(),
        ]);
    }

    public function save(): void
    {
        requireAdmin();
        validate_csrf();
        $type = (string)($_POST['guide_type'] ?? 'sequential');
        if (!isset(self::TYPES[$type])) {
            $type = 'sequential';
        }
        $html = (string)($_POST['guide_content'] ?? '');
        $this->settings->set($this->guideKey($type), $html);
        flash('success', 'How to Play guide updated for ' . self::TYPES[$type] . '.');
        redirect('/admin/guide?type=' . $type);
    }
}
