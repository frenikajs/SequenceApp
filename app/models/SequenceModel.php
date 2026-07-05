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

    /** True if another sequence already uses this start code (case-insensitive). */
    public function startCodeExists(string $code, int $excludeId = 0): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }
        $row = $this->db->fetch(
            'SELECT id FROM sequences WHERE LOWER(TRIM(start_code)) = LOWER(?) AND id != ?',
            [$code, $excludeId]
        );
        return (bool)$row;
    }

    /**
     * Find a playable sequence by its start code (case-insensitive), for the
     * landing-page quick-access. Published + unexpired only unless $includeUnpublished.
     */
    public function findByStartCode(string $code, bool $includeUnpublished = false): array|false
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }
        $sql = 'SELECT * FROM sequences
                WHERE LOWER(TRIM(start_code)) = LOWER(?)
                  AND (expires_at IS NULL OR expires_at > NOW())';
        if (!$includeUnpublished) {
            $sql .= ' AND published = 1';
        }
        $sql .= ' ORDER BY published DESC, id DESC LIMIT 1';
        return $this->db->fetch($sql, [$code]);
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
             (title, slug, description, type, gameboard_theme, start_code, intro_access_code, finale_code,
              finale_requires_code, introduction_content, intro_instruction, intro_hint_text,
              finale_content, finale_instruction, finale_hint_text, solution_content,
              thank_you_content, accusation_json, published, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['slug'],
                $data['description'] ?? null,
                $data['type'] ?? 'sequential',
                $data['gameboard_theme'] ?? 'candyland',
                $data['start_code'],
                $data['intro_access_code'] ?? null,
                $data['finale_code'] ?? null,
                (int)($data['finale_requires_code'] ?? 1),
                $data['introduction_content'] ?? null,
                $data['intro_instruction'] ?? null,
                $data['intro_hint_text'] ?? null,
                $data['finale_content'] ?? null,
                $data['finale_instruction'] ?? null,
                $data['finale_hint_text'] ?? null,
                $data['solution_content'] ?? null,
                $data['thank_you_content'] ?? null,
                $data['accusation_json'] ?? null,
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
        $cols   = ['title','slug','description','type','gameboard_theme','start_code','intro_access_code','finale_code',
                   'finale_requires_code','introduction_content','intro_instruction','intro_hint_text',
                   'finale_content','finale_instruction','finale_hint_text','solution_content','thank_you_content',
                   'survey_link','accusation_json','published','expires_at'];
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
            'intro_hint_file_path','intro_hint_file_type','intro_hint_original_filename',
            'intro_hint_file_size','intro_hint_mime_type','intro_hint_caption',
            'finale_file_path','finale_file_type','finale_original_filename',
            'finale_file_size','finale_mime_type','finale_caption',
            'finale_hint_file_path','finale_hint_file_type','finale_hint_original_filename',
            'finale_hint_file_size','finale_hint_mime_type','finale_hint_caption',
            'solution_file_path','solution_file_type','solution_original_filename',
            'solution_file_size','solution_mime_type','solution_caption',
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
        // Stamp published_at the first time it goes live (kept on later re-publishes).
        // NOTE: assign published_at BEFORE flipping `published` — MariaDB evaluates
        // SET assignments left-to-right, so later references see the updated value.
        return $this->db->execute(
            'UPDATE sequences
             SET published_at = CASE WHEN published = 0 AND published_at IS NULL
                                     THEN NOW() ELSE published_at END,
                 published = NOT published
             WHERE id = ?',
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

    public function resetStats(int $id): bool
    {
        return $this->db->execute(
            'UPDATE sequences SET view_count = 0, completion_count = 0 WHERE id = ?',
            [$id]
        );
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
             (title, slug, description, type, gameboard_theme, start_code, intro_access_code, finale_code, finale_requires_code,
              introduction_content, intro_instruction, intro_file_path, intro_file_type, intro_original_filename,
              intro_file_size, intro_mime_type, intro_caption,
              intro_hint_text, intro_hint_file_path, intro_hint_file_type, intro_hint_original_filename,
              intro_hint_file_size, intro_hint_mime_type, intro_hint_caption,
              finale_content, finale_instruction, finale_file_path, finale_file_type, finale_original_filename,
              finale_file_size, finale_mime_type, finale_caption,
              finale_hint_text, finale_hint_file_path, finale_hint_file_type, finale_hint_original_filename,
              finale_hint_file_size, finale_hint_mime_type, finale_hint_caption,
              solution_content, solution_file_path, solution_file_type, solution_original_filename,
              solution_file_size, solution_mime_type, solution_caption,
              thank_you_content, survey_link, accusation_json, published, expires_at)
             SELECT CONCAT(title, " (Copy)"), ?, description, type, gameboard_theme, start_code, intro_access_code, finale_code,
              finale_requires_code, introduction_content, intro_instruction, intro_file_path, intro_file_type,
              intro_original_filename, intro_file_size, intro_mime_type, intro_caption,
              intro_hint_text, intro_hint_file_path, intro_hint_file_type, intro_hint_original_filename,
              intro_hint_file_size, intro_hint_mime_type, intro_hint_caption,
              finale_content, finale_instruction, finale_file_path, finale_file_type, finale_original_filename,
              finale_file_size, finale_mime_type, finale_caption,
              finale_hint_text, finale_hint_file_path, finale_hint_file_type, finale_hint_original_filename,
              finale_hint_file_size, finale_hint_mime_type, finale_hint_caption,
              solution_content, solution_file_path, solution_file_type, solution_original_filename,
              solution_file_size, solution_mime_type, solution_caption,
              thank_you_content, survey_link, accusation_json, 0, expires_at
             FROM sequences WHERE id = ?',
            [$slug, $id]
        );
        $newId = (int)$this->db->lastInsertId();

        // Duplicate theme
        $this->db->execute(
            'INSERT INTO sequence_themes
             (sequence_id, bg_color, text_color, button_color, btn_text_color, accent_color,
              font_family, title_font, title_color, container_width, bg_image, custom_css)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $newId,
                $theme['bg_color']        ?? '#0f0f1a',
                $theme['text_color']      ?? '#e0e0e0',
                $theme['button_color']    ?? '#6c63ff',
                $theme['btn_text_color']  ?? '#ffffff',
                $theme['accent_color']    ?? '#ff6b6b',
                $theme['font_family']     ?? 'Inter, sans-serif',
                $theme['title_font']      ?? null,
                $theme['title_color']     ?? null,
                $theme['container_width'] ?? '800px',
                $theme['bg_image']        ?? null,
                $theme['custom_css']      ?? null,
            ]
        );

        // Duplicate clues (but NOT their files — shared reference)
        $this->db->execute(
            'INSERT INTO clues
             (sequence_id, title, content, access_code, instruction, file_path, file_type,
              original_filename, file_size, mime_type, file_caption,
              hint_text, hint_file_path, hint_file_type, hint_original_filename,
              hint_file_size, hint_mime_type, hint_caption, sort_order)
             SELECT ?, title, content, access_code, instruction, file_path, file_type,
              original_filename, file_size, mime_type, file_caption,
              hint_text, hint_file_path, hint_file_type, hint_original_filename,
              hint_file_size, hint_mime_type, hint_caption, sort_order
             FROM clues WHERE sequence_id = ? ORDER BY sort_order',
            [$newId, $id]
        );

        // Duplicate the Interactive suspect-clue matrix. Clues are copied by
        // sort_order, so pair old↔new clue ids in that same order to remap.
        $oldClues = $this->db->fetchAll('SELECT id FROM clues WHERE sequence_id = ? ORDER BY sort_order, id', [$id]);
        $newClues = $this->db->fetchAll('SELECT id FROM clues WHERE sequence_id = ? ORDER BY sort_order, id', [$newId]);
        foreach ($oldClues as $i => $oc) {
            $newClueId = $newClues[$i]['id'] ?? null;
            if ($newClueId === null) { continue; }
            $this->db->execute(
                'INSERT INTO suspect_clues
                  (sequence_id, clue_id, suspect_index, body, file_path, file_type, original_filename, file_size, mime_type)
                 SELECT ?, ?, suspect_index, body, file_path, file_type, original_filename, file_size, mime_type
                 FROM suspect_clues WHERE clue_id = ?',
                [$newId, $newClueId, $oc['id']]
            );
        }

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
                'title_font'      => null,
                'title_color'     => null,
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
                   'font_family','title_font','title_color','container_width','bg_image','custom_css'];
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

        // Optional title override (font and/or color). Both are nullable — when
        // unset the title inherits the body font and the default text colour.
        $titleRules = [];
        if (!empty($theme['title_font']))  { $titleRules[] = 'font-family: ' . $theme['title_font'] . ';'; }
        if (!empty($theme['title_color'])) { $titleRules[] = 'color: ' . $theme['title_color'] . ';'; }
        if ($titleRules) {
            $css .= "\n.seq-title { " . implode(' ', $titleRules) . " }";
        }

        if (!empty($theme['custom_css'])) {
            $css .= "\n" . $theme['custom_css'];
        }

        return $css;
    }
}
