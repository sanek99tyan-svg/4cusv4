<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!cms_is_ready()) {
    echo json_encode(['ok' => false, 'installed' => false], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(['ok' => true, 'installed' => true, 'data' => public_payload()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);