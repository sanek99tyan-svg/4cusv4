<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/auth.php';
require_admin();
verify_csrf();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'content') {
        $ru = $_POST['ru'] ?? []; $en = $_POST['en'] ?? [];
        $stmt = db()->prepare('UPDATE content_items SET value_ru=?, value_en=? WHERE id=?');
        foreach ($ru as $id => $value) $stmt->execute([(string)$value, (string)($en[$id] ?? ''), (int)$id]);
        header('Location: /admin/?m=content&saved=1'); exit;
    }

    if ($action === 'blocks') {
        $enabled = $_POST['enabled'] ?? []; $sort = $_POST['sort'] ?? [];
        $stmt = db()->prepare('UPDATE blocks SET enabled=?, sort_order=? WHERE id=?');
        foreach ($sort as $id => $order) $stmt->execute([isset($enabled[$id]) ? 1 : 0, (int)$order, (int)$id]);
        header('Location: /admin/?m=blocks&saved=1'); exit;
    }

    if ($action === 'settings') {
        $stmt = db()->prepare('UPDATE settings SET value=? WHERE id=?');
        foreach (($_POST['settings'] ?? []) as $id => $value) $stmt->execute([(string)$value,(int)$id]);
        header('Location: /admin/?m=settings&saved=1'); exit;
    }

    if ($action === 'gallery_add') {
        $imageUrl = trim($_POST['image_url'] ?? '');
        if (!empty($_FILES['image']['tmp_name'])) {
            $file = $_FILES['image'];
            if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Ошибка загрузки файла');
            if ($file['size'] > 8 * 1024 * 1024) throw new RuntimeException('Файл больше 8 MB');
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            if (!isset($map[$mime])) throw new RuntimeException('Разрешены JPG, PNG, WEBP, GIF');
            $config = require dirname(__DIR__) . '/app/config.php';
            $dir = rtrim($config['app']['upload_dir'],'/') . '/gallery';
            if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Не удалось создать папку uploads/gallery');
            $name = bin2hex(random_bytes(16)) . '.' . $map[$mime];
            if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Не удалось сохранить изображение');
            $imageUrl = rtrim($config['app']['upload_url'],'/') . '/gallery/' . $name;
        }
        $stmt = db()->prepare('INSERT INTO gallery(title,category,description,image_url,link_url,sort_order,active) VALUES(?,?,?,?,?,?,1)');
        $stmt->execute([trim($_POST['title']??''),trim($_POST['category']??''),trim($_POST['description']??''),$imageUrl,trim($_POST['link_url']??''),(int)($_POST['sort_order']??0)]);
        header('Location: /admin/?m=gallery&saved=1'); exit;
    }

    if ($action === 'gallery_delete') {
        $stmt = db()->prepare('SELECT image_url FROM gallery WHERE id=?');
        $stmt->execute([(int)($_POST['id']??0)]);
        $url = (string)$stmt->fetchColumn();
        db()->prepare('DELETE FROM gallery WHERE id=?')->execute([(int)($_POST['id']??0)]);
        if (str_starts_with($url,'/uploads/gallery/')) {
            $path = dirname(__DIR__) . $url;
            if (is_file($path)) @unlink($path);
        }
        header('Location: /admin/?m=gallery&deleted=1'); exit;
    }

    throw new RuntimeException('Неизвестное действие');
} catch (Throwable $e) {
    http_response_code(400);
    echo '<h1>Ошибка</h1><p>'.htmlspecialchars($e->getMessage()).'</p><p><a href="/admin/">Назад</a></p>';
}