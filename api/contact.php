<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false]); exit; }

try {
    $body = json_decode(file_get_contents('php://input') ?: '{}', true);
    $name = trim((string)($body['name'] ?? ''));
    $contact = trim((string)($body['contact'] ?? ''));
    $task = trim((string)($body['task'] ?? ''));
    if ($name === '' || $contact === '' || $task === '') throw new RuntimeException('Заполните все поля');
    if (mb_strlen($name) > 190 || mb_strlen($contact) > 255 || mb_strlen($task) > 5000) throw new RuntimeException('Слишком длинные данные');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ipHash = $ip ? hash('sha256', $ip . '|4cus') : null;
    $stmt = db()->prepare('INSERT INTO contact_submissions(name,contact,task,ip_hash) VALUES(?,?,?,?)');
    $stmt->execute([$name,$contact,$task,$ipHash]);
    echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}