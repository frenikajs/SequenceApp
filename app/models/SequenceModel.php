<?php
declare(strict_types=1);

class SequenceModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Reads ─────────────────────────────────────────────────────────────────

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM sequences WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->db->fetch('SELECT * FROM sequences WHERE slug = ?', [$slug]);
    }

    public function getAll(int $limit = 10, int $offset = 0, string $search = ''): array
    {
        if ($search !== '') {
            return $this->db->fetchAll(
                'SELECT * FROM sequences WHERE title LIKE ? OR slug LIKE ?
                 ORDER BY created_at DESC LIMIT ? OFFSET ?',
                ['%' . $search . '%', '%' . $search . '%', $limit, $offset]
            );
        }
        return $this->db->fetchAll(
            'SELECT * FROM sequences ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public function count(string $search = ''): int
    {
        if ($search !== '') {
            $row = $this->db->fetch(
                'SELECT COUNT(*) AS cnt FROM sequences WHERE title LIKE ? OR slug LIKE ?',
                ['%' . $search . '%', '%' . $search . '%']
            );
        } else {
            $row = $this->db->fetch('SELECT COUNT(*) AS cnt FROM sequences');
        }
        return (int)($row['cnt'] ?? 0);
    }

    public function getStats(): array
    {
        $total = $this->db->fetch('SELECT COUNT(*) AS cnt FROM sequences');
        $pub   = $this->db->fetch('SELECT COUNT(*) AS cnt FROM sequences WHERE published = 1');
        $comp  = $this->db->fetch('SELECT SUM(completion_count) AS cnt FROM sequences');
        $views = $this->db->fetch('SELECT SUM(view_count) AS cnt FROM sequences');
        return [
            'total'       => (int)($total['cnt'] ?? 0),
            'published'   => (int)($pub['cnt'] ?? 0),
            'unpublished' => (int)($total['cnt'] ?? 0) - (int)($pub['cnt'] ?? 0),
            'completions' => (int)($comp['cnt'] ?? 0),
            'views'       => (int)($views['cnt'] ?? 0),
        ];
    }

    // ── Writes ────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO sequences
             (title, slug, description, type, start_code, finale_code, finale_requires_code,
              introduction_content, finale_content, published, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['slug'],
                $data['description'] ?? null,
                $data['type'] ?? 'sequential',
                $data['start_code'],
                $data['finale_code'] ?? null,
                (int)($data['finale_requires_code'] ?? 1),
                $data['introduction_content'] ?? null,
                $data['finale_content'] ?? null,
                (int)($data['published'] ?? 0),
                $data['expires_at'] ?? null,
            ]
        );
        $id = (int)$this->db->lastInsertId();

        // Insert default theme
        $this->db->execute(
            'INSERT INTO sequence_themes (sequence_id) VALUES (?)',
            [$id]
        );

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $cols   = ['title','slug','description','type','start_code','finale_code',
                   'finale_requires_code','introduction_content','finale_content',
                   'published','expires_at'];
        $fields = [];
        $params = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[]  = $data[$col];
            }
        }

        // Media fields
        $mediaFields = [
            'intro_file_path','intro_file_type','intro_original_filename',
            'intro_file_size','intro_mime_type','intro_caption',
            'finale_file_path','finale_file_type','finale_original_filename',
            'finale_file_size','finale_mime_type','finale_caption',
        ];
        foreach ($mediaFields as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[]  = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }
        $params[] = $id;
        return $this->db->execute(
            'UPDATE sequences SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $params
        );
    }

    public function delete(int $id): bool
    {
        // Cascade handles clues & themes
        return $this->db->execute('DELETE FROM sequences WHERE id = ?', [$id]);
    }

    public function togglePublish(int $id): bool
    {
        return $this->db->execute(
            'UPDATE sequences SET published = NOT published WHERE id = ?',
            [$id]
        );
    }

    public function incrementView(int $id): void
    {
        $this->db->execute('UPDATE sequences SET view_count = view_count + 1 WHERE id = ?', [$id]);
    }

    public function incrementCompletion(int $id): void
    {
        $this->db->execute('UPDATE sequences SET completion_count = completion_count + 1 WHERE id = ?', [$id]);
    }

    public function duplicate(int $id): int|false
    {
        $seq = $this->findById($id);
        if (!$seq) {
            return false;
        }

        $theme = $this->getTheme($id);

        // Build unique slug
        $baseSlug = $seq['slug'] . '-copy';
        $slug     = $baseSlug;
        $i        = 1;
        while ($this->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $i++;
        }

        $this->db->execute(
            'INSERT INTO sequences
             (title, slug, description, type, start_code, finale_code, finale_requires_code,
              introduction_content, intro_file_path, intro_file_type, intro_original_filename,
              intro_file_size, intro_mime_type, intro_caption,
              finale_content, finale_file_path, finale_file_type, finale_original_filename,
              finale_file_size, finale_mime_type, finale_caption, published, expires_at)
             SELECT CONCAT(title, " (Copy)"), ?, description, type, start_code, finale_code,
              finale_requires_code, introduction_content, intro_file_path, intro_file_type,
              intro_original_filename, intro_file_size, intro_mime_type, intro_caption,
              finale_content, finale_file_path, finale_file_type, finale_original_filename,
              finale_file_size, finale_mime_type, finale_caption, 0, expires_at
             FROM sequences WHERE id = ?',
            [$slug, $id]
        );
        $newId = (int)$this->db->lastInsertId();

        // Duplicate theme
        $this->db->execute(
            'INSERT INTO sequence_themes
             (sequence_id, bg_color, text_color, button_color, btn_text_color, accent_color,
              font_family, container_width, bg_image, custom_css)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $newId,
                $theme['bg_color']        ?? '#0f0f1a',
                $theme['text_color']      ?? '#e0e0e0',
                $theme['button_color']    ?? '#6c63ff',
                $theme['btn_text_color']  ?? '#ffffff',
                $theme['accent_color']    ?? '#ff6b6b',
                $theme['font_family']     ?? 'Inter, sans-serif',
                $theme['container_width'] ?? '800px',
                $theme['bg_image']        ?? null,
                $theme['custom_css']      ?? null,
            ]
        );

        // Duplicate clues (but NOT their files — shared reference)
        $this->db->execute(
            'INSERT INTO clues
             (sequence_id, title, content, access_code, file_path, file_type,
              original_filename, file_size, mime_type, file_caption,
              hint_text, hint_file_path, hint_file_type, hint_original_filename,
              hint_file_size, hint_mime_type, hint_caption, sort_order)
             SELECT ?, title, content, access_code, file_path, file_type,
              original_filename, file_size, mime_type, file_caption,
              hint_text, hint_file_path, hint_file_type, hint_original_filename,
              hint_file_size, hint_mime_type, hint_caption, sort_order
             FROM clues WHERE sequence_id = ? ORDER BY sort_order',
            [$newId, $id]
        );

        return $newId;
    }

    // ── Theme ─────────────────────────────────────────────────────────────────

    public function getTheme(int $sequenceId): array
    {
        $row = $this->db->fetch(
            'SELECT * FROM sequence_themes WHERE sequence_id = ?',
            [$sequenceId]
        );
        if (!$row) {
            // Return defaults if theme row missing
            return [
                'bg_color'        => '#0f0f1a',
                'text_color'      => '#e0e0e0',
                'button_color'    => '#6c63ff',
                'btn_text_color'  => '#ffffff',
                'accent_color'    => '#ff6b6b',
                'font_family'     => 'Inter, sans-serif',
                'container_width' => '800px',
                'bg_image'        => null,
                'custom_css'      => null,
            ];
        }
        return $row;
    }

    public function saveTheme(int $sequenceId, array $data): bool
    {
        $cols   = ['bg_color','text_color','button_color','btn_text_color','accent_color',
                   'font_family','container_width','bg_image','custom_css'];
        $fields = [];
        $params = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[]  = $data[$col] ?: null;
            }
        }
        if (empty($fields)) {
            return false;
        }
        $params[] = $sequenceId;
        return $this->db->execute(
            'UPDATE sequence_themes SET ' . implode(', ', $fields) . ' WHERE sequence_id = ?',
            $params
        );
    }

    public function buildCss(array $theme): string
    {
        $bgImage = $theme['bg_image']
            ? 'background-image: url("' . UPLOAD_URL . '/' . htmlspecialchars($theme['bg_image']) . '");'
              . 'background-size: cover; background-position: center; background-attachment: fixed;'
            : '';

        $css = <<<CSS
        :root {
            --bg-color: {$theme['bg_color']};
            --text-color: {$theme['text_color']};
            --btn-color: {$theme['button_color']};
            --btn-text: {$theme['btn_text_color']};
            --accent: {$theme['accent_color']};
            --container-width: {$theme['container_width']};
            --font: {$theme['font_family']};
        }
        body { background-color: var(--bg-color); color: var(--text-color); font-family: var(--font); {$bgImage} }
        .seq-container { max-width: var(--container-width); }
        .btn-primary { background: var(--btn-color); color: var(--btn-text); }
        .btn-primary:hover { filter: brightness(1.15); }
        .accent { color: var(--accent); }
        CSS;

        if (!empty($theme['custom_css'])) {
            $css .= "\n" . $theme['custom_css'];
        }

        return $css;
    }
}
