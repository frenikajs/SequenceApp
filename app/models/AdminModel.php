<?php
declare(strict_types=1);

class AdminModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM admins WHERE id = ?', [$id]);
    }

    public function findByUsername(string $username): array|false
    {
        return $this->db->fetch('SELECT * FROM admins WHERE username = ?', [$username]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll('SELECT id, username, email, is_super, created_at, last_login FROM admins ORDER BY username');
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO admins (username, email, password_hash, is_super) VALUES (?, ?, ?, ?)',
            [
                $data['username'],
                $data['email'],
                Security::hashPassword($data['password']),
                (int)($data['is_super'] ?? 0),
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach (['username', 'email'] as $col) {
            if (isset($data[$col])) {
                $fields[] = "$col = ?";
                $params[]  = $data[$col];
            }
        }
        if (isset($data['password']) && $data['password'] !== '') {
            $fields[] = 'password_hash = ?';
            $params[]  = Security::hashPassword($data['password']);
        }
        if (empty($fields)) {
            return false;
        }
        $params[] = $id;
        return $this->db->execute('UPDATE admins SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }

    public function updateLastLogin(int $id): bool
    {
        return $this->db->execute('UPDATE admins SET last_login = NOW() WHERE id = ?', [$id]);
    }
}
