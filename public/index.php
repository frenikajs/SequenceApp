<?php
declare(strict_types=1);

// ── Bootstrap ─────────────────────────────────────────────────────────────────

require_once dirname(__DIR__) . '/app/config/config.php';
require_once APP_ROOT . '/models/Database.php';
require_once APP_ROOT . '/helpers/Security.php';
require_once APP_ROOT . '/helpers/FileUpload.php';
require_once APP_ROOT . '/helpers/functions.php';
require_once APP_ROOT . '/models/AdminModel.php';
require_once APP_ROOT . '/models/SequenceModel.php';
require_once APP_ROOT . '/models/ClueModel.php';
require_once APP_ROOT . '/models/CluePageModel.php';
require_once APP_ROOT . '/models/PuzzlePageModel.php';
require_once APP_ROOT . '/models/ProgressModel.php';
require_once APP_ROOT . '/models/SettingsModel.php';
require_once APP_ROOT . '/controllers/AuthController.php';
require_once APP_ROOT . '/controllers/AdminController.php';
require_once APP_ROOT . '/controllers/CluePageController.php';
require_once APP_ROOT . '/controllers/PuzzlePageController.php';
require_once APP_ROOT . '/controllers/SequenceController.php';
require_once APP_ROOT . '/controllers/GuideController.php';
require_once APP_ROOT . '/controllers/MediaController.php';

Security::sendSecurityHeaders();
Security::startSecureSession();

// ── Request parsing ───────────────────────────────────────────────────────────

$requestUri    = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path          = parse_url($requestUri, PHP_URL_PATH) ?? '/';
// Strip script directory prefix (e.g. /public when running in a subdirectory).
// rtrim converts '/' (root) to '' so str_replace('','',path) is a no-op at the root.
$path          = '/' . ltrim(str_replace($scriptName, '', $path), '/');
$method        = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── Router ────────────────────────────────────────────────────────────────────

// Tokenise path
$segments = array_values(array_filter(explode('/', $path)));

$seg0 = $segments[0] ?? '';
$seg1 = $segments[1] ?? '';
$seg2 = $segments[2] ?? '';
$seg3 = $segments[3] ?? '';

// ── Static uploads (optionally served via PHP for security) ───────────────────
if ($seg0 === 'uploads') {
    array_shift($segments);
    $filePath = implode('/', $segments);
    (new MediaController())->serve($filePath);
}

// ── Public decoy pages ────────────────────────────────────────────────────────
if ($seg0 === 'p' && $seg1 !== '') {
    (new CluePageController())->show($seg1);
    exit;
}

// ── Public puzzle pages ───────────────────────────────────────────────────────
if ($seg0 === 'z' && $seg1 !== '') {
    (new PuzzlePageController())->show($seg1);
    exit;
}

// ── Public "How to Play" guide ────────────────────────────────────────────────
if ($seg0 === 'how-to-play' && $seg1 === '') {
    (new GuideController())->show();
    exit;
}

// ── Public sequence pages ─────────────────────────────────────────────────────
if ($seg0 === 's' && $seg1 !== '') {
    $slug = $seg1;
    $ctrl = new SequenceController();

    if ($method === 'POST') {
        $action = $seg2;
        match ($action) {
            'reset' => $ctrl->resetProgress($slug),
            default => $ctrl->submitCode($slug),
        };
    } else {
        $ctrl->show($slug);
    }
    exit;
}

// ── Admin routes ──────────────────────────────────────────────────────────────
if ($seg0 === 'admin') {
    $authCtrl  = new AuthController();
    $adminCtrl = new AdminController();
    $mediaCtrl = new MediaController();

    $adminSeg1 = $seg1; // 'login', 'logout', 'sequences', 'clues', 'media', …
    $adminSeg2 = $seg2; // ID or 'create', 'clues', 'edit', …
    $adminSeg3 = $seg3; // 'clues', 'edit', 'delete', 'publish', …

    // Login / logout
    if ($adminSeg1 === 'login') {
        ($method === 'POST') ? $authCtrl->login() : $authCtrl->showLogin();
        exit;
    }
    if ($adminSeg1 === 'logout' && $method === 'POST') {
        $authCtrl->logout();
        exit;
    }

    // Media upload / delete (AJAX)
    if ($adminSeg1 === 'media') {
        match ($adminSeg2) {
            'upload' => $mediaCtrl->upload(),
            'delete' => $mediaCtrl->delete(),
            default  => notFound(),
        };
    }

    // Clue reorder (AJAX)
    if ($adminSeg1 === 'clues' && $adminSeg2 === 'reorder' && $method === 'POST') {
        $adminCtrl->reorderClues();
        exit;
    }

    // Clue page editor: /admin/clues/{id}/page[/delete]
    if ($adminSeg1 === 'clues' && ctype_digit($adminSeg2) && $adminSeg3 === 'page') {
        $clueId    = (int)$adminSeg2;
        $pageCtrl  = new CluePageController();
        $seg4      = $segments[4] ?? '';
        if ($method === 'POST' && $seg4 === 'delete') {
            $pageCtrl->deletePage($clueId);
        } elseif ($method === 'POST') {
            $pageCtrl->savePage($clueId);
        } else {
            $pageCtrl->editPage($clueId);
        }
        exit;
    }

    // Puzzle page editor: /admin/clues/{id}/puzzle[/delete]
    if ($adminSeg1 === 'clues' && ctype_digit($adminSeg2) && $adminSeg3 === 'puzzle') {
        $clueId      = (int)$adminSeg2;
        $puzzleCtrl  = new PuzzlePageController();
        $seg4        = $segments[4] ?? '';
        if ($method === 'POST' && $seg4 === 'delete') {
            $puzzleCtrl->deletePage($clueId);
        } elseif ($method === 'POST') {
            $puzzleCtrl->savePage($clueId);
        } else {
            $puzzleCtrl->editPage($clueId);
        }
        exit;
    }

    // Clue edit / delete: /admin/clues/{id}/edit|delete
    if ($adminSeg1 === 'clues' && ctype_digit($adminSeg2)) {
        $clueId = (int)$adminSeg2;
        match ($adminSeg3) {
            'edit'   => $adminCtrl->editClue($clueId),
            'delete' => $adminCtrl->deleteClue($clueId),
            default  => notFound(),
        };
        exit;
    }

    // How to Play guide editor: /admin/guide
    if ($adminSeg1 === 'guide' && $adminSeg2 === '') {
        $guideCtrl = new GuideController();
        ($method === 'POST') ? $guideCtrl->save() : $guideCtrl->edit();
        exit;
    }

    // Sequences
    if ($adminSeg1 === 'sequences') {
        // /admin/sequences
        if ($adminSeg2 === '') {
            ($method === 'POST') ? notFound() : $adminCtrl->sequences();
            exit;
        }
        // /admin/sequences/create
        if ($adminSeg2 === 'create') {
            ($method === 'POST') ? $adminCtrl->storeSequence() : $adminCtrl->createSequence();
            exit;
        }
        // /admin/sequences/{id}/...
        if (ctype_digit($adminSeg2)) {
            $seqId = (int)$adminSeg2;
            match ([$method, $adminSeg3]) {
                ['GET',  'edit']      => $adminCtrl->editSequence($seqId),
                ['POST', 'edit']      => $adminCtrl->updateSequence($seqId),
                ['POST', 'delete']    => $adminCtrl->deleteSequence($seqId),
                ['POST', 'publish']   => $adminCtrl->togglePublish($seqId),
                ['POST', 'duplicate'] => $adminCtrl->duplicateSequence($seqId),
                ['POST', 'reset-stats'] => $adminCtrl->resetStats($seqId),
                ['GET',  'clues']     => $adminCtrl->manageClues($seqId),
                ['POST', 'clues']     => $adminCtrl->addClue($seqId),
                default               => notFound(),
            };
            exit;
        }
    }

    // Admin dashboard (default)
    if ($adminSeg1 === '' || $adminSeg1 === 'dashboard') {
        $adminCtrl->dashboard();
        exit;
    }

    notFound();
}

// ── Root redirect ─────────────────────────────────────────────────────────────
if ($path === '/' || $path === '') {
    redirect('/admin');
}

notFound();
