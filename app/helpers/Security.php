<?php
declare(strict_types=1);

class Security
{
    // ── CSRF ─────────────────────────────────────────────────────────────────

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    // ── Output escaping ───────────────────────────────────────────────────────

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // ── Input sanitization ────────────────────────────────────────────────────

    public static function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    // ── Password ──────────────────────────────────────────────────────────────

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    // ── Slug generation ───────────────────────────────────────────────────────

    public static function generateSlug(string $title): string
    {
        $slug = mb_strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug) ?? $slug;
        $slug = preg_replace('/[\s\-]+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    // ── Secure filenames ──────────────────────────────────────────────────────

    public static function generateSecureFilename(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . strtolower($extension);
    }

    // ── Rate limiting ─────────────────────────────────────────────────────────

    public static function isRateLimited(string $ip): bool
    {
        $db  = Database::getInstance();
        $row = $db->fetch(
            'SELECT COUNT(*) AS cnt FROM login_attempts
             WHERE ip_address = ? AND success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$ip, LOGIN_ATTEMPT_WINDOW]
        );
        return (int)($row['cnt'] ?? 0) >= MAX_LOGIN_ATTEMPTS;
    }

    public static function recordLoginAttempt(string $ip, string $username, bool $success): void
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)',
            [$ip, $username, (int)$success]
        );
    }

    // ── Session hardening ─────────────────────────────────────────────────────

    public static function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Regenerate session id periodically to prevent fixation
        if (empty($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = true;
        }
    }

    // ── Security headers (sent from PHP so no .htaccess dependency) ────────────

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    // ── IP helper ─────────────────────────────────────────────────────────────

    public static function getClientIp(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
