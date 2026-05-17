<?php
declare(strict_types=1);

// ── Routing helpers ───────────────────────────────────────────────────────────

function redirect(string $path, int $code = 302): never
{
    $url = str_starts_with($path, 'http') ? $path : url($path);
    header('Location: ' . $url, true, $code);
    exit;
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

// ── View rendering ─────────────────────────────────────────────────────────────

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $file = APP_ROOT . '/views/' . str_replace('.', '/', $template) . '.php';
    if (!file_exists($file)) {
        throw new RuntimeException("View not found: $template");
    }
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    require $file;
}

// ── Flash messages ─────────────────────────────────────────────────────────────

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): array|null
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Security shorthands ────────────────────────────────────────────────────────

function e(string $value): string
{
    return Security::escape($value);
}

function csrf_field(): string
{
    return Security::csrfField();
}

function validate_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCsrfToken($token)) {
        http_response_code(403);
        die('CSRF token mismatch.');
    }
}

// ── Admin auth ─────────────────────────────────────────────────────────────────

function isAdmin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        flash('error', 'Please log in to access the admin area.');
        redirect('/admin/login');
    }
}

// ── Formatting ─────────────────────────────────────────────────────────────────

function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i     = 0;
    while ($bytes >= 1024 && $i < 3) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

function formatDate(string $date, string $format = 'M j, Y g:i a'): string
{
    return date($format, strtotime($date));
}

function truncate(string $text, int $length = 120): string
{
    $plain = strip_tags($text);
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }
    return mb_substr($plain, 0, $length) . '…';
}

function slugify(string $text): string
{
    return Security::generateSlug($text);
}

// ── File type helper ──────────────────────────────────────────────────────────

function fileCategory(string $mimeType): string
{
    return ALLOWED_MIMES[$mimeType] ?? 'unknown';
}

function mediaIcon(string $fileType): string
{
    return match ($fileType) {
        'image' => '🖼',
        'pdf'   => '📄',
        'audio' => '🎵',
        default => '📎',
    };
}

// ── JSON response ─────────────────────────────────────────────────────────────

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── 404 ───────────────────────────────────────────────────────────────────────

function notFound(string $message = 'Page not found'): never
{
    http_response_code(404);
    view('public.404', ['message' => $message]);
    exit;
}
