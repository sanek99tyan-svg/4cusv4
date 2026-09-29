<?php
declare(strict_types=1);
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/functions.php';

$ok = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = db();
        $sql = file_get_contents(__DIR__ . '/install.sql');
        if ($sql === false) throw new RuntimeException('install.sql not found');
        $pdo->exec($sql);

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Введите корректный email.');
        if (strlen($password) < 8) throw new RuntimeException('Пароль должен быть минимум 8 символов.');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)');
        $st->execute([$email,$hash]);

        // Автоматически собираем все data-i18n ключи из текущего дизайна,
        // чтобы каждый текстовый элемент можно было редактировать в CMS.
        $html = file_get_contents(__DIR__ . '/template.html');
        if ($html !== false) {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
            $xp = new DOMXPath($dom);
            $insert = $pdo->prepare('INSERT IGNORE INTO content_items(content_key,value_ru,value_en) VALUES(?,?,?)');
            foreach ($xp->query('//*[@data-i18n]') as $node) {
                $key = trim((string)$node->getAttribute('data-i18n'));
                $value = trim((string)$node->textContent);
                if ($key !== '') $insert->execute([$key,$value,$value]);
            }
            foreach ($xp->query('//*[@data-i18n-placeholder]') as $node) {
                $key = trim((string)$node->getAttribute('data-i18n-placeholder'));
                $value = trim((string)$node->getAttribute('placeholder'));
                if ($key !== '') $insert->execute([$key,$value,$value]);
            }
            libxml_clear_errors();
        }

        $ok = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Установка 4cus CMS</title><link rel="stylesheet" href="/admin/assets/admin.css"></head>
<body class="login-body"><main class="login-card"><div class="admin-brand">4cus<span>.</span></div>
<h1>Установка CMS</h1>
<?php if($ok):?><div class="alert" style="background:#12351e;color:#aaf3bf">CMS установлена. После входа удалите setup.php с сервера.</div><a class="btn primary" href="/admin/login.php">Войти в админку</a>
<?php else:?>
<p>Создаём таблицы, все редактируемые тексты и первого администратора.</p>
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<form method="post" class="stack"><label>Email<input type="email" name="email" required></label><label>Пароль<input type="password" name="password" minlength="8" required></label><button class="btn primary">Установить CMS</button></form>
<?php endif;?></main></body></html>