<?php
declare(strict_types=1);

class FileUpload
{
    private array  $errors  = [];
    private ?array $fileInfo = null;

    /**
     * Process an uploaded file.
     *
     * @param array  $file        Entry from $_FILES
     * @param string $subdir      Sub-directory inside /uploads/sequences/ (e.g. "42")
     * @return array|false        File info array on success, false on failure
     */
    public function handle(array $file, string $subdir): array|false
    {
        $this->errors  = [];
        $this->fileInfo = null;

        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return false; // No file — not an error, just nothing uploaded
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->uploadErrorMessage($file['error']);
            return false;
        }

        // Size check
        if ($file['size'] > MAX_FILE_SIZE) {
            $this->errors[] = 'File exceeds maximum size of ' . formatFileSize(MAX_FILE_SIZE) . '.';
            return false;
        }

        // MIME check via finfo (more reliable than extension alone)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!array_key_exists($mimeType, ALLOWED_MIMES)) {
            $this->errors[] = 'File type not allowed. Allowed: PNG, JPG, PDF, MP3, WAV, OGG.';
            return false;
        }

        $fileType = ALLOWED_MIMES[$mimeType]; // 'image', 'pdf', 'audio'

        // Extension check
        $originalName = $file['name'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            $this->errors[] = 'File extension not allowed.';
            return false;
        }

        // Build destination path
        $destDir = UPLOAD_PATH . '/sequences/' . preg_replace('/[^a-z0-9_\-]/', '', $subdir);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Randomised filename prevents enumeration / overwrites
        $secureFilename = Security::generateSecureFilename($ext);
        $destPath       = $destDir . '/' . $secureFilename;
        $relativePath   = 'sequences/' . $subdir . '/' . $secureFilename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->errors[] = 'Failed to save uploaded file.';
            return false;
        }

        // Harden: prevent script execution in uploads dir
        $this->writeHtaccess($destDir);

        $this->fileInfo = [
            'file_path'         => $relativePath,
            'file_type'         => $fileType,
            'original_filename' => basename($originalName),
            'file_size'         => $file['size'],
            'mime_type'         => $mimeType,
        ];

        return $this->fileInfo;
    }

    /** Delete a stored upload file */
    public static function delete(string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }
        $abs = UPLOAD_PATH . '/' . ltrim($relativePath, '/');
        if (is_file($abs)) {
            return unlink($abs);
        }
        return false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /** Serve a file with security validation (call from MediaController) */
    public static function serve(string $relativePath): never
    {
        // Prevent directory traversal
        $realUploads = realpath(UPLOAD_PATH);
        $fullPath    = realpath(UPLOAD_PATH . '/' . $relativePath);

        if ($fullPath === false || !str_starts_with($fullPath, $realUploads)) {
            http_response_code(403);
            exit('Access denied.');
        }

        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('File not found.');
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mime     = $finfo->file($fullPath);

        // Only serve known-safe MIME types
        if (!array_key_exists($mime, ALLOWED_MIMES)) {
            http_response_code(403);
            exit('File type not allowed.');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($fullPath);
        exit;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL                        => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR                     => 'Missing temporary directory.',
            UPLOAD_ERR_CANT_WRITE                     => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION                      => 'File upload stopped by extension.',
            default                                   => 'Unknown upload error.',
        };
    }

    private function writeHtaccess(string $dir): void
    {
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\nphp_flag engine off\n");
        }
    }
}
