<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/auth.php';
require_admin();

$module = $_GET['m'] ?? 'dashboard';
$allowed = ['dashboard','content','blocks','gallery','settings','submissions'];
if (!in_array($module,$allowed,true)) $module='dashboard';

$contentRows = $module==='content' ? db()->query('SELECT * FROM content_items ORDER BY content_key')->fetchAll() : [];
$blocks = $module==='blocks' ? db()->query('SELECT * FROM blocks ORDER BY sort_order,id')->fetchAll() : [];
$gallery = $module==='gallery' ? db()->query('SELECT * FROM gallery ORDER BY sort_order,id')->fetchAll() : [];
$settings = $module==='settings' ? db()->query('SELECT * FROM settings ORDER BY `key`')->fetchAll() : [];
$submissions = $module==='submissions' ? db()->query('SELECT * FROM contact_submissions ORDER BY id DESC LIMIT 300')->fetchAll() : [];
$counts = [
    'content'=>(int)db()->query('SELECT COUNT(*) FROM content_items')->fetchColumn(),
    'gallery'=>(int)db()->query('SELECT COUNT(*) FROM gallery')->fetchColumn(),
    'submissions'=>(int)db()->query('SELECT COUNT(*) FROM contact_submissions')->fetchColumn()
];
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>4cus CMS</title><link rel="stylesheet" href="/admin/assets/admin.css"></head><body>
<div class="admin-shell">
<aside class="sidebar"><div class="admin-brand">4cus<span>.</span></div><nav>
<a class="<?=$module==='dashboard'?'active':''?>" href="/admin/">Обзор</a>
<a class="<?=$module==='content'?'active':''?>" href="?m=content">Тексты</a>
<a class="<?=$module==='blocks'?'active':''?>" href="?m=blocks">Блоки</a>
<a class="<?=$module==='gallery'?'active':''?>" href="?m=gallery">Галерея</a>
<a class="<?=$module==='settings'?'active':''?>" href="?m=settings">Настройки / SEO</a>
<a class="<?=$module==='submissions'?'active':''?>" href="?m=submissions">Заявки</a>
</nav><div class="sidebar-bottom"><a href="/" target="_blank">Открыть сайт ↗</a><a href="/admin/logout.php">Выйти</a></div></aside>
<main class="admin-main">
<header class="admin-top"><div><small>4CUS CONTROL PANEL</small><h1><?=e(ucfirst($module))?></h1></div><div class="user-chip"><?=e(admin_user()['email'])?></div></header>

<?php if($module==='dashboard'):?>
<div class="stats"><article><b><?=$counts['content']?></b><span>редактируемых текстов</span></article><article><b><?=$counts['gallery']?></b><span>работ в галерее</span></article><article><b><?=$counts['submissions']?></b><span>заявок</span></article></div>
<section class="panel"><h2>Модульная CMS готова</h2><p>Редактируйте тексты RU/EN, включайте и выключайте блоки, управляйте галереей, SEO и заявками. Главный дизайн и анимации остаются из версии 4cusv4.</p></section>
<?php endif;?>

<?php if($module==='content'):?>
<section class="panel"><div class="panel-head"><div><h2>Тексты сайта</h2><p>Все элементы с data-i18n можно менять без редактирования HTML.</p></div></div>
<form method="post" action="/admin/save.php" class="stack"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="content">
<?php foreach($contentRows as $row):?><article class="editor-row"><div class="key"><?=e($row['content_key'])?></div><label>RU<textarea name="ru[<?=e($row['id'])?>]" rows="2"><?=e($row['value_ru'])?></textarea></label><label>EN<textarea name="en[<?=e($row['id'])?>]" rows="2"><?=e($row['value_en'])?></textarea></label></article><?php endforeach;?>
<div class="sticky-save"><button class="btn primary">Сохранить тексты</button></div></form></section>
<?php endif;?>

<?php if($module==='blocks'):?>
<section class="panel"><h2>Блоки страницы</h2><form method="post" action="/admin/save.php" class="stack"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="blocks">
<?php foreach($blocks as $b):?><div class="block-row"><div><strong><?=e($b['title'])?></strong><small><?=e($b['slug'])?></small></div><label class="switchline"><input type="checkbox" name="enabled[<?=e($b['id'])?>]" value="1" <?=$b['enabled']?'checked':''?>> Показывать</label><label>Порядок<input type="number" name="sort[<?=e($b['id'])?>]" value="<?=e((string)$b['sort_order'])?>"></label></div><?php endforeach;?>
<button class="btn primary">Сохранить блоки</button></form></section>
<?php endif;?>

<?php if($module==='gallery'):?>
<section class="panel"><div class="panel-head"><div><h2>Галерея</h2><p>Добавляйте изображения с компьютера или по URL.</p></div></div>
<form method="post" action="/admin/save.php" enctype="multipart/form-data" class="grid-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="gallery_add"><label>Название<input name="title" required></label><label>Категория<input name="category"></label><label class="wide">Описание<textarea name="description" rows="3"></textarea></label><label>Фото URL<input name="image_url" placeholder="https://..."></label><label>Или загрузить<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label><label>Ссылка проекта<input name="link_url"></label><label>Порядок<input type="number" name="sort_order" value="0"></label><div class="wide"><button class="btn primary">Добавить</button></div></form>
<div class="gallery-admin"><?php foreach($gallery as $g):?><article><div class="thumb"><?php if($g['image_url']):?><img src="<?=e($g['image_url'])?>" alt=""><?php endif;?></div><div><strong><?=e($g['title'])?></strong><small><?=e($g['category'])?></small></div><form method="post" action="/admin/save.php" onsubmit="return confirm('Удалить?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="gallery_delete"><input type="hidden" name="id" value="<?=e((string)$g['id'])?>"><button class="btn danger">Удалить</button></form></article><?php endforeach;?></div></section>
<?php endif;?>

<?php if($module==='settings'):?>
<section class="panel"><h2>Настройки и SEO</h2><form method="post" action="/admin/save.php" class="stack"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="settings"><?php foreach($settings as $s):?><label><?=e($s['key'])?><input name="settings[<?=e($s['id'])?>]" value="<?=e($s['value'])?>"></label><?php endforeach;?><button class="btn primary">Сохранить настройки</button></form></section>
<?php endif;?>

<?php if($module==='submissions'):?>
<section class="panel"><h2>Заявки</h2><div class="submission-list"><?php if(!$submissions):?><p>Пока заявок нет.</p><?php endif;?><?php foreach($submissions as $s):?><article><div><strong><?=e($s['name'])?></strong><span><?=e($s['contact'])?></span></div><p><?=nl2br(e($s['task']))?></p><small><?=e($s['created_at'])?></small></article><?php endforeach;?></div></section>
<?php endif;?>
</main></div></body></html>