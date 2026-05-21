<?php
declare(strict_types=1);

class GuideController
{
    private SettingsModel $settings;

    public function __construct()
    {
        $this->settings = new SettingsModel();
    }

    // ── Public: render the How to Play guide ──────────────────────────────────

    public function show(): void
    {
        $guide = $this->settings->get('howto_guide', '');
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
        view('admin.guide', [
            'pageTitle' => 'How to Play Guide',
            'activeNav' => 'guide',
            'guide'     => $this->settings->get('howto_guide', ''),
            'flash'     => getFlash(),
        ]);
    }

    public function save(): void
    {
        requireAdmin();
        validate_csrf();
        $html = (string)($_POST['guide_content'] ?? '');
        $this->settings->set('howto_guide', $html);
        flash('success', 'How to Play guide updated.');
        redirect('/admin/guide');
    }
}
