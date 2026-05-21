<?php
declare(strict_types=1);

class MediaController
{
    /**
     * POST /admin/media/upload
     * Expects: target (sequence|clue), target_id, media_slot (intro|finale|clue|hint)
     */
    public function upload(): never
    {
        requireAdmin();
        validate_csrf();

        $target    = $_POST['target']    ?? '';
        $targetId  = (int)($_POST['target_id']  ?? 0);
        $mediaSlot = $_POST['media_slot'] ?? '';
        $fileKey   = $_POST['file_key']   ?? 'file';

        if (!$targetId || !in_array($target, ['sequence', 'clue'], true)) {
            jsonResponse(['success' => false, 'error' => 'Invalid target.'], 400);
        }

        $file = $_FILES[$fileKey] ?? null;
        if (!$file) {
            jsonResponse(['success' => false, 'error' => 'No file received.'], 400);
        }

        $subdir  = $target === 'sequence' ? (string)$targetId : 'clue-' . $targetId;
        $uploader = new FileUpload();
        $info     = $uploader->handle($file, $subdir);

        if ($info === false) {
            jsonResponse(['success' => false, 'error' => implode(' ', $uploader->getErrors())], 422);
        }

        // Persist to DB
        if ($target === 'sequence') {
            $seqModel = new SequenceModel();
            $prefix   = match ($mediaSlot) {
                'intro'  => 'intro_',
                'finale' => 'finale_',
                default  => 'intro_',
            };
            $seqModel->update($targetId, [
                $prefix . 'file_path'         => $info['file_path'],
                $prefix . 'file_type'         => $info['file_type'],
                $prefix . 'original_filename' => $info['original_filename'],
                $prefix . 'file_size'         => $info['file_size'],
                $prefix . 'mime_type'         => $info['mime_type'],
            ]);
        } else {
            $clueModel = new ClueModel();
            $prefix    = ($mediaSlot === 'hint') ? 'hint_' : '';
            $clueModel->update($targetId, [
                $prefix . 'file_path'         => $info['file_path'],
                $prefix . 'file_type'         => $info['file_type'],
                $prefix . 'original_filename' => $info['original_filename'],
                $prefix . 'file_size'         => $info['file_size'],
                $prefix . 'mime_type'         => $info['mime_type'],
            ]);
        }

        jsonResponse([
            'success'       => true,
            'file_path'     => $info['file_path'],
            'file_type'     => $info['file_type'],
            'original_name' => $info['original_filename'],
            'file_size'     => formatFileSize($info['file_size']),
            'url'           => UPLOAD_URL . '/' . $info['file_path'],
        ]);
    }

    /**
     * POST /admin/media/delete
     */
    public function delete(): never
    {
        requireAdmin();
        validate_csrf();

        $target    = $_POST['target']    ?? '';
        $targetId  = (int)($_POST['target_id'] ?? 0);
        $mediaSlot = $_POST['media_slot'] ?? '';

        if ($target === 'sequence') {
            $seqModel = new SequenceModel();
            $seq      = $seqModel->findById($targetId);
            if (!$seq) {
                jsonResponse(['success' => false, 'error' => 'Sequence not found.'], 404);
            }
            $prefix = match ($mediaSlot) {
                'intro'       => 'intro_',
                'finale'      => 'finale_',
                'intro_hint'  => 'intro_hint_',
                'finale_hint' => 'finale_hint_',
                default       => 'intro_',
            };
            FileUpload::delete($seq[$prefix . 'file_path'] ?? '');
            $seqModel->update($targetId, [
                $prefix . 'file_path'         => null,
                $prefix . 'file_type'         => null,
                $prefix . 'original_filename' => null,
                $prefix . 'file_size'         => null,
                $prefix . 'mime_type'         => null,
            ]);
        } else {
            $clueModel = new ClueModel();
            $clue      = $clueModel->findById($targetId);
            if (!$clue) {
                jsonResponse(['success' => false, 'error' => 'Clue not found.'], 404);
            }
            $prefix = ($mediaSlot === 'hint') ? 'hint_' : '';
            FileUpload::delete($clue[$prefix . 'file_path'] ?? '');
            $clueModel->update($targetId, [
                $prefix . 'file_path'         => null,
                $prefix . 'file_type'         => null,
                $prefix . 'original_filename' => null,
                $prefix . 'file_size'         => null,
                $prefix . 'mime_type'         => null,
            ]);
        }

        jsonResponse(['success' => true]);
    }

    /** GET /uploads/{path} — secured file serving */
    public function serve(string $path): never
    {
        FileUpload::serve($path);
    }
}
