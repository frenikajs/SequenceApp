<?php
declare(strict_types=1);

class AuthController
{
    public function showLogin(): void
    {
        if (isAdmin()) {
            redirect('/admin');
        }
        view('admin.login', ['flash' => getFlash()]);
    }

    public function login(): void
    {
        validate_csrf();

        $ip       = Security::getClientIp();
        $username = Security::sanitizeString($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Security::isRateLimited($ip)) {
            Security::recordLoginAttempt($ip, $username, false);
            flash('error', 'Too many failed attempts. Please wait 15 minutes and try again.');
            redirect('/admin/login');
        }

        if ($username === '' || $password === '') {
            flash('error', 'Username and password are required.');
            redirect('/admin/login');
        }

        $model = new AdminModel();
        $admin = $model->findByUsername($username);

        if (!$admin || !Security::verifyPassword($password, $admin['password_hash'])) {
            Security::recordLoginAttempt($ip, $username, false);
            flash('error', 'Invalid username or password.');
            redirect('/admin/login');
        }

        Security::recordLoginAttempt($ip, $username, true);
        $model->updateLastLogin((int)$admin['id']);

        // Regenerate session to prevent fixation
        session_regenerate_id(true);
        $_SESSION['admin_id']       = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_super']    = (bool)$admin['is_super'];

        flash('success', 'Welcome back, ' . e($admin['username']) . '!');
        redirect('/admin');
    }

    public function logout(): void
    {
        validate_csrf();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        flash('success', 'You have been logged out.');
        redirect('/admin/login');
    }
}
