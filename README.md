# Sequence Mystery System

A production-ready PHP web application for building interactive mystery/story experiences where players unlock content in order using access codes.

## Requirements

- PHP 8.3+
- MySQL 8.0+ or MariaDB 10.5+
- Apache with `mod_rewrite` enabled (or Nginx with equivalent rewrite rules)
- PHP extensions: `pdo_mysql`, `fileinfo`, `session`, `mbstring`

---

## Quick Start

### 1. Clone / Copy Files

Place the project folder on your server. The webroot must point to the `/public` directory.

### 2. Configure the Database

Edit `app/config/database.php` and set your MySQL credentials:

```php
return [
    'host'     => 'localhost',
    'dbname'   => 'sequence_db',
    'username' => 'your_user',
    'password' => 'your_password',
    'charset'  => 'utf8mb4',
    ...
];
```

### 3. Run Migrations

Import the schema into MySQL:

```bash
mysql -u root -p < migrations/schema.sql
```

Optionally import the example sequence data (does NOT include the admin user):

```bash
mysql -u root -p < migrations/seed.sql
```

### 4. Configure BASE_URL

Edit `app/config/config.php` and update `BASE_URL` if needed (auto-detected by default):

```php
define('BASE_URL', 'https://yourdomain.com');
```

### 5. Set Permissions

```bash
chmod 755 uploads/
chmod 755 logs/
chmod 755 uploads/sequences/
```

### 6. Apache Configuration

Point your virtualhost DocumentRoot to `/public`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/sequence/public
    <Directory /path/to/sequence/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable mod_rewrite:
```bash
a2enmod rewrite
systemctl restart apache2
```

### 7. PHP Built-in Server (Development Only)

```bash
php -S localhost:8080 -t public/
```

Then visit: `http://localhost:8080`

---

## Creating the Admin User

Run the setup script (requires PHP + database connection configured):

```bash
php migrations/create-admin.php
```

This creates `admin` / `Sequence2024!`. Edit the script first to change the password.
**Delete `create-admin.php` after running it.**

---

## Folder Structure

```
sequence/
├── public/                  ← Webroot (point server here)
│   ├── index.php            ← Application router
│   ├── .htaccess            ← URL rewriting
│   └── assets/
│       ├── css/app.css      ← Public page styles
│       ├── css/admin.css    ← Admin dashboard styles
│       ├── js/app.js        ← Public page JavaScript
│       └── js/admin.js      ← Admin dashboard JavaScript
├── app/
│   ├── config/
│   │   ├── config.php       ← App constants & settings
│   │   └── database.php     ← Database credentials
│   ├── helpers/
│   │   ├── Security.php     ← CSRF, hashing, rate limiting
│   │   ├── FileUpload.php   ← Secure file upload handling
│   │   └── functions.php    ← Global helper functions
│   ├── models/
│   │   ├── Database.php     ← PDO singleton
│   │   ├── AdminModel.php
│   │   ├── SequenceModel.php
│   │   ├── ClueModel.php
│   │   └── ProgressModel.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── AdminController.php
│   │   ├── SequenceController.php
│   │   └── MediaController.php
│   └── views/
│       ├── admin/
│       │   ├── layout.php
│       │   ├── login.php
│       │   ├── dashboard.php
│       │   └── sequences/
│       │       ├── index.php
│       │       ├── create.php
│       │       ├── edit.php
│       │       └── clues.php
│       └── public/
│           ├── sequence.php
│           └── 404.php
├── migrations/
│   ├── schema.sql           ← Database schema
│   └── seed.sql             ← Example data
├── uploads/
│   └── sequences/           ← User-uploaded files (gitignored)
└── logs/                    ← PHP error logs (gitignored)
```

---

## URL Structure

| URL | Description |
|-----|-------------|
| `/admin` | Admin dashboard |
| `/admin/login` | Admin login page |
| `/admin/sequences` | List all sequences |
| `/admin/sequences/create` | Create new sequence |
| `/admin/sequences/{id}/edit` | Edit sequence |
| `/admin/sequences/{id}/clues` | Manage clues |
| `/s/{slug}` | Public sequence page |

---

## Sequence Types

### Sequential Mode
- User enters **start code** to begin
- Introduction is shown (if set)
- Each clue requires its own **access code** to unlock
- After all clues, user enters **finale code** to reveal the finale
- Progress is tracked per session

### Open Mode
- User enters **start code** to begin
- All clues are visible immediately
- Finale can be auto-revealed or require a **finale code**

---

## Security Features

- PDO prepared statements (SQL injection prevention)
- CSRF tokens on all forms
- Output escaping with `htmlspecialchars()`
- Session hardening (HttpOnly cookies, SameSite=Lax, secure flag)
- Rate limiting on login (5 attempts per 15 minutes per IP)
- `password_hash()`/`password_verify()` with bcrypt cost 12
- MIME type validation on file uploads (via `finfo`)
- Randomised secure filenames for uploads
- Directory traversal prevention in file serving
- PHP execution blocked in uploads directory (via .htaccess)
- XSS prevention in all user-facing output

---

## File Upload Support

| Type | Formats | Max Size |
|------|---------|----------|
| Images | PNG, JPG, JPEG | 10 MB |
| Documents | PDF | 10 MB |
| Audio | MP3, WAV, OGG | 10 MB |

Uploads are stored in `/uploads/sequences/{sequence_id}/` with randomised filenames.

---

## Nginx Configuration

If using Nginx instead of Apache:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/sequence/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # Block direct PHP execution in uploads
    location ~* /uploads/.*\.php$ {
        deny all;
    }
}
```

---

## Production Checklist

- [ ] Change admin password
- [ ] Set `BASE_URL` in `config.php`
- [ ] Set `display_errors = 0` in `config.php` (already default)
- [ ] Configure `error_log` path in `config.php`
- [ ] Set `SESSION_LIFETIME` appropriately
- [ ] Enable HTTPS and update session cookie `secure` flag
- [ ] Set strong database password
- [ ] Restrict `/uploads` directory from PHP execution at server level
- [ ] Set up regular database backups
- [ ] Review `MAX_LOGIN_ATTEMPTS` and `LOGIN_ATTEMPT_WINDOW` limits

---

## Adding More Admins

Insert directly into the database (use a tool like phpMyAdmin or the MySQL CLI):

```sql
INSERT INTO admins (username, email, password_hash, is_super)
VALUES ('newadmin', 'admin2@example.com', '$2y$12$...bcrypt_hash...', 0);
```

Generate a bcrypt hash with PHP:
```php
echo password_hash('YourPassword123!', PASSWORD_BCRYPT, ['cost' => 12]);
```

---

## License

MIT — free to use for personal, commercial, and educational purposes.
