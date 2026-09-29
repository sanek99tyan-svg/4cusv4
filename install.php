<?php
declare(strict_types=1);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? 'fourcus');
    $user = trim($_POST['db_user'] ?? '');
    $pass = (string)($_POST['db_pass'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = (string)($_POST['admin_password'] ?? '');

    try {
        if (!$user || !$name || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 8) {
            throw new RuntimeException('Заполните все поля. Пароль администратора — минимум 8 символов.');
        }

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $sql = file_get_contents(__DIR__ . '/install.sql');
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', (string)$sql))) as $statement) {
            $pdo->exec($statement);
        }

        $template = file_get_contents(__DIR__ . '/template.html') ?: '';
        preg_match_all('/data-i18n=["\']([^"\']+)["\'][^>]*>([^<]*)</u', $template, $matches, PREG_SET_ORDER);
        $stmt = $pdo->prepare('INSERT IGNORE INTO content_items (content_key,value_ru,value_en) VALUES (?,?,?)');
        foreach ($matches as $m) {
            $key = trim($m[1]);
            $value = html_entity_decode(trim(strip_tags($m[2])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($key !== '') $stmt->execute([$key, $value, '']);
        }

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admins (email,password_hash) VALUES (?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)');
        $stmt->execute([$adminEmail, $hash]);

        $local = "<?php\nreturn " . var_export([
            'db' => ['host'=>$host,'port'=>$port,'name'=>$name,'user'=>$user,'pass'=>$pass]
        ], true) . ";\n";
        if (file_put_contents(__DIR__ . '/app/config.local.php', $local, LOCK_EX) === false) {
            throw new RuntimeException('Не удалось записать app/config.local.php. Проверьте права на папку app.');
        }
        @chmod(__DIR__ . '/app/config.local.php', 0600);

        if (!is_dir(__DIR__ . '/uploads/gallery')) @mkdir(__DIR__ . '/uploads/gallery', 0755, true);
        $message = 'Готово. CMS установлена. Теперь войдите в админку.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Установка 4cus CMS</title><style>
body{font-family:Arial,sans-serif;background:#0b0c0f;color:#fff;margin:0;min-height:100vh;display:grid;place-items:center}.card{width:min(720px,92vw);background:#12151a;border:1px solid #262a33;border-radius:24px;padding:28px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}label{display:grid;gap:7px;color:#a7adba;font-size:13px}.wide{grid-column:1/-1}input{background:#0d1015;color:#fff;border:1px solid #2b303a;border-radius:12px;padding:13px}button{border:0;border-radius:999px;padding:14px 20px;font-weight:800;cursor:pointer}.ok{background:#173d2a;color:#7ef0a8;padding:12px;border-radius:12px}.err{background:#481b22;color:#ff9ca9;padding:12px;border-radius:12px}a{color:#9fb2ff}@media(max-width:640px){.grid{grid-template-columns:1fr}.wide{grid-column:auto}}
</style></head><body><main class="card"><h1>4cus CMS</h1><p>Однократная установка движка и администратора.</p><?php if($message):?><p class="ok"><?=htmlspecialchars($message)?></p><p><a href="/admin/login.php">Открыть админку →</a></p><?php endif;?><?php if($error):?><p class="err"><?=htmlspecialchars($error)?></p><?php endif;?><form method="post" class="grid"><label>DB Host<input name="db_host" value="<?=htmlspecialchars($_POST['db_host']??'127.0.0.1')?>" required></label><label>DB Port<input name="db_port" value="<?=htmlspecialchars($_POST['db_port']??'3306')?>" required></label><label>DB Name<input name="db_name" value="<?=htmlspecialchars($_POST['db_name']??'fourcus')?>" required></label><label>DB User<input name="db_user" value="<?=htmlspecialchars($_POST['db_user']??'')?>" required></label><label class="wide">DB Password<input type="password" name="db_pass"></label><label>Admin email<input type="email" name="admin_email" required></label><label>Admin password<input type="password" name="admin_password" minlength="8" required></label><div class="wide"><button type="submit">Установить CMS</button></div></form></main></body></html>