<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/auth.php';

boot_session();
if (admin_user()) { header('Location: /admin/'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $stmt = db()->prepare('SELECT id,email,password_hash FROM admins WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) throw new RuntimeException('Неверный email или пароль');
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id'=>(int)$user['id'],'email'=>$user['email']];
        header('Location: /admin/');
        exit;
    } catch (Throwable $e) { $error = $e->getMessage(); }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>4cus CMS — вход</title><link rel="stylesheet" href="/admin/assets/admin.css"></head><body class="login-body"><main class="login-card"><div class="admin-brand">4cus<span>.</span></div><h1>Вход в CMS</h1><?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><form method="post" class="stack"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Email<input type="email" name="email" required autofocus></label><label>Пароль<input type="password" name="password" required></label><button class="btn primary" type="submit">Войти</button></form></main></body></html>