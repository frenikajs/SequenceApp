<?php
declare(strict_types=1);

class AdminController
{
    private SequenceModel   $seqModel;
    private ClueModel       $clueModel;
    private CluePageModel   $pageModel;

    public function __construct()
    {
        $this->seqModel  = new SequenceModel();
        $this->clueModel = new ClueModel();
        $this->pageModel = new CluePageModel();
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        requireAdmin();
        $stats    = $this->seqModel->getStats();
        $recent   = $this->seqModel->getAll(5, 0);
        view('admin.dashboard', [
            'stats'  => $stats,
            'recent' => $recent,
            'flash'  => getFlash(),
        ]);
    }

    // ── Sequence list ─────────────────────────────────────────────────────────

    public function sequences(): void
    {
        requireAdmin();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = Security::sanitizeString($_GET['search'] ?? '');
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $total  = $this->seqModel->count($search);
        $items  = $this->seqModel->getAll(ITEMS_PER_PAGE, $offset, $search);

        view('admin.sequences.index', [
            'sequences'   => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => ITEMS_PER_PAGE,
            'search'      => $search,
            'flash'       => getFlash(),
        ]);
    }

    // ── Create sequence ───────────────────────────────────────────────────────

    public function createSequence(): void
    {
        requireAdmin();
        view('admin.sequences.create', ['flash' => getFlash(), 'errors' => []]);
    }

    public function storeSequence(): void
    {
        requireAdmin();
        validate_csrf();

        [$data, $errors] = $this->validateSequenceInput($_POST);
        if (!empty($errors)) {
            view('admin.sequences.create', ['flash' => null, 'errors' => $errors, 'input' => $_POST]);
            return;
        }

        // Unique slug
        $baseSlug = slugify($data['title']);
        $data['slug'] = $this->uniqueSlug($baseSlug);

        $id = $this->seqModel->create($data);
        $this->seqModel->saveTheme($id, $this->extractTheme($_POST));
        $this->processSequenceFiles($id);

        flash('success', 'Sequence created successfully.');
        redirect('/admin/sequences/' . $id . '/clues');
    }

    // ── Edit sequence ─────────────────────────────────────────────────────────

    public function editSequence(int $id): void
    {
        requireAdmin();
        $seq   = $this->seqModel->findById($id) ?: notFound();
        $theme = $this->seqModel->getTheme($id);
        $analytics = (new ProgressModel())->getAnalytics($id);

        view('admin.sequences.edit', [
            'sequence'  => $seq,
            'theme'     => $theme,
            'analytics' => $analytics,
            'flash'     => getFlash(),
            'errors'    => [],
        ]);
    }

    public function updateSequence(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $seq = $this->seqModel->findById($id) ?: notFound();

        [$data, $errors] = $this->validateSequenceInput($_POST, $id);
        if (!empty($errors)) {
            $theme     = $this->seqModel->getTheme($id);
            $analytics = (new ProgressModel())->getAnalytics($id);
            view('admin.sequences.edit', [
                'sequence'  => array_merge($seq, $_POST),
                'theme'     => $theme,
                'analytics' => $analytics,
                'flash'     => null,
                'errors'    => $errors,
            ]);
            return;
        }

        // Keep existing slug if user didn't change it or provide new one
        if (empty($data['slug'])) {
            $data['slug'] = $seq['slug'];
        }

        $this->seqModel->update($id, $data);
        $this->seqModel->saveTheme($id, $this->extractTheme($_POST));
        $this->processSequenceFiles($id, $seq);

        flash('success', 'Sequence updated successfully.');
        redirect('/admin/sequences/' . $id . '/edit');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function deleteSequence(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $seq = $this->seqModel->findById($id) ?: notFound();

        // Remove media files
        foreach (['intro_file_path', 'finale_file_path'] as $col) {
            if ($seq[$col]) {
                FileUpload::delete($seq[$col]);
            }
        }
        $clues = $this->clueModel->getBySequenceId($id);
        foreach ($clues as $c) {
            FileUpload::delete($c['file_path'] ?? '');
            FileUpload::delete($c['hint_file_path'] ?? '');
        }

        $this->seqModel->delete($id);
        flash('success', 'Sequence "' . e($seq['title']) . '" deleted.');
        redirect('/admin/sequences');
    }

    // ── Publish toggle ────────────────────────────────────────────────────────

    public function togglePublish(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $this->seqModel->findById($id) ?: notFound();
        $this->seqModel->togglePublish($id);
        redirect('/admin/sequences');
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicateSequence(int $id): void
    {
        requireAdmin();
        validate_csrf();
        $newId = $this->seqModel->duplicate($id);
        if ($newId) {
            flash('success', 'Sequence duplicated successfully.');
            redirect('/admin/sequences/' . $newId . '/edit');
        } else {
            flash('error', 'Failed to duplicate sequence.');
            redirect('/admin/sequences');
        }
    }

    // ── Clue management ───────────────────────────────────────────────────────

    public function manageClues(int $seqId): void
    {
        requireAdmin();
        $seq    = $this->seqModel->findById($seqId) ?: notFound();
        $clues  = $this->clueModel->getBySequenceId($seqId);
        $clueIds = array_column($clues, 'id');
        $pages  = $this->pageModel->getMapForClues(array_map('intval', $clueIds));

        view('admin.sequences.clues', [
            'sequence' => $seq,
            'clues'    => $clues,
            'pages'    => $pages,
            'flash'    => getFlash(),
        ]);
    }

    public function addClue(int $seqId): void
    {
        requireAdmin();
        validate_csrf();
        $this->seqModel->findById($seqId) ?: notFound();

        $errors = [];
        if (empty($_POST['access_code'])) {
            $errors[] = 'Access code is required.';
        }
        if (!empty($errors)) {
            flash('error', implode(' ', $errors));
            redirect('/admin/sequences/' . $seqId . '/clues');
        }

        $data = [
            'sequence_id' => $seqId,
            'title'       => Security::sanitizeString($_POST['title'] ?? ''),
            'content'     => $_POST['content'] ?? null,
            'access_code' => Security::sanitizeString($_POST['access_code']),
            'hint_text'   => $_POST['hint_text'] ?? null,
        ];

        $clueId = $this->clueModel->create($data);

        // Handle file upload if provided
        $this->processClueFiles($clueId, (string)$seqId);

        flash('success', 'Clue added.');
        redirect('/admin/sequences/' . $seqId . '/clues');
    }

    public function editClue(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $clue = $this->clueModel->findById($clueId) ?: notFound();

        if (empty($_POST['access_code'])) {
            flash('error', 'Access code is required.');
            redirect('/admin/sequences/' . $clue['sequence_id'] . '/clues');
        }

        $data = [
            'title'       => Security::sanitizeString($_POST['title'] ?? ''),
            'content'     => $_POST['content'] ?? null,
            'access_code' => Security::sanitizeString($_POST['access_code']),
            'hint_text'   => $_POST['hint_text'] ?? null,
            'file_caption'      => Security::sanitizeString($_POST['file_caption'] ?? ''),
            'hint_caption'      => Security::sanitizeString($_POST['hint_caption'] ?? ''),
        ];

        $this->clueModel->update($clueId, $data);
        $this->processClueFiles($clueId, (string)$clue['sequence_id']);

        flash('success', 'Clue updated.');
        redirect('/admin/sequences/' . $clue['sequence_id'] . '/clues');
    }

    public function deleteClue(int $clueId): void
    {
        requireAdmin();
        validate_csrf();
        $clue = $this->clueModel->findById($clueId) ?: notFound();

        FileUpload::delete($clue['file_path'] ?? '');
        FileUpload::delete($clue['hint_file_path'] ?? '');
        $this->clueModel->delete($clueId);

        flash('success', 'Clue deleted.');
        redirect('/admin/sequences/' . $clue['sequence_id'] . '/clues');
    }

    public function reorderClues(): never
    {
        requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['csrf_token']) || !Security::validateCsrfToken($data['csrf_token'])) {
            jsonResponse(['success' => false, 'error' => 'CSRF mismatch.'], 403);
        }
        $ids = array_map('intval', $data['order'] ?? []);
        if (empty($ids)) {
            jsonResponse(['success' => false, 'error' => 'No order provided.'], 400);
        }
        $ok = $this->clueModel->updateOrder($ids);
        jsonResponse(['success' => $ok]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateSequenceInput(array $post, int $excludeId = 0): array
    {
        $errors = [];
        $data   = [];

        $data['title'] = Security::sanitizeString($post['title'] ?? '');
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }

        $data['description']          = $post['description'] ?? null;
        $data['type']                 = in_array($post['type'] ?? '', ['sequential', 'open'])
                                        ? $post['type'] : 'sequential';
        $data['start_code']           = Security::sanitizeString($post['start_code'] ?? '');
        $data['finale_code']          = Security::sanitizeString($post['finale_code'] ?? '') ?: null;
        $data['finale_requires_code'] = !empty($post['finale_requires_code']) ? 1 : 0;
        $data['introduction_content'] = $post['introduction_content'] ?? null;
        $data['finale_content']       = $post['finale_content'] ?? null;
        $data['published']            = !empty($post['published']) ? 1 : 0;
        $data['expires_at']           = !empty($post['expires_at']) ? $post['expires_at'] : null;

        if (empty($data['start_code'])) {
            $errors[] = 'Start code is required.';
        }

        // Slug handling
        if (!empty($post['slug'])) {
            $slug = slugify($post['slug']);
            $existing = $this->seqModel->findBySlug($slug);
            if ($existing && (int)$existing['id'] !== $excludeId) {
                $errors[] = 'That slug is already in use.';
            }
            $data['slug'] = $slug;
        }

        return [$data, $errors];
    }

    private function extractTheme(array $post): array
    {
        return [
            'bg_color'        => $post['bg_color']        ?? '#0f0f1a',
            'text_color'      => $post['text_color']      ?? '#e0e0e0',
            'button_color'    => $post['button_color']    ?? '#6c63ff',
            'btn_text_color'  => $post['btn_text_color']  ?? '#ffffff',
            'accent_color'    => $post['accent_color']    ?? '#ff6b6b',
            'font_family'     => $post['font_family']     ?? 'Inter, sans-serif',
            'container_width' => $post['container_width'] ?? '800px',
            'custom_css'      => $post['custom_css']      ?? null,
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i    = 1;
        while ($this->seqModel->findBySlug($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function processSequenceFiles(int $seqId, array $existing = []): void
    {
        $uploader = new FileUpload();

        foreach (['intro' => 'intro_file', 'finale' => 'finale_file'] as $slot => $fileKey) {
            if (empty($_FILES[$fileKey]['name'])) {
                continue;
            }

            $info = $uploader->handle($_FILES[$fileKey], (string)$seqId);
            if ($info === false) {
                continue;
            }

            // Delete the old file before saving the new one
            $oldPath = $existing[$slot . '_file_path'] ?? null;
            if ($oldPath) {
                FileUpload::delete($oldPath);
            }

            $this->seqModel->update($seqId, [
                $slot . '_file_path'         => $info['file_path'],
                $slot . '_file_type'         => $info['file_type'],
                $slot . '_original_filename' => $info['original_filename'],
                $slot . '_file_size'         => $info['file_size'],
                $slot . '_mime_type'         => $info['mime_type'],
            ]);
        }
    }

    private function processClueFiles(int $clueId, string $seqId): void
    {
        $uploader = new FileUpload();

        // Main clue file
        if (!empty($_FILES['clue_file']['name'])) {
            $info = $uploader->handle($_FILES['clue_file'], $seqId);
            if ($info) {
                $this->clueModel->update($clueId, [
                    'file_path'         => $info['file_path'],
                    'file_type'         => $info['file_type'],
                    'original_filename' => $info['original_filename'],
                    'file_size'         => $info['file_size'],
                    'mime_type'         => $info['mime_type'],
                ]);
            } elseif ($uploader->hasErrors()) {
                flash('error', 'Clue file not saved: ' . implode(' ', $uploader->getErrors()));
            }
        }

        // Hint file
        if (!empty($_FILES['hint_file']['name'])) {
            $info = $uploader->handle($_FILES['hint_file'], $seqId);
            if ($info) {
                $this->clueModel->update($clueId, [
                    'hint_file_path'         => $info['file_path'],
                    'hint_file_type'         => $info['file_type'],
                    'hint_original_filename' => $info['original_filename'],
                    'hint_file_size'         => $info['file_size'],
                    'hint_mime_type'         => $info['mime_type'],
                ]);
            } elseif ($uploader->hasErrors()) {
                flash('error', 'Hint file not saved: ' . implode(' ', $uploader->getErrors()));
            }
        }
    }
}
