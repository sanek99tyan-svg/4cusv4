<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function setting(string $key, string $default = ''): string
{
    try {
        $stmt = db()->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string)$value;
    } catch (Throwable $e) {
        return $default;
    }
}

function all_content(): array
{
    try {
        $rows = db()->query('SELECT content_key, value_ru, value_en FROM content_items ORDER BY content_key')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['content_key']] = ['ru' => $row['value_ru'], 'en' => $row['value_en']];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function blocks_state(): array
{
    try {
        $rows = db()->query('SELECT slug, enabled, sort_order FROM blocks ORDER BY sort_order, id')->fetchAll();
        $out = [];
        foreach ($rows as $row) $out[$row['slug']] = ['enabled' => (bool)$row['enabled'], 'sort_order' => (int)$row['sort_order']];
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function gallery_items(): array
{
    try {
        return db()->query('SELECT id, title, category, description, image_url, link_url FROM gallery WHERE active = 1 ORDER BY sort_order, id')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function public_payload(): array
{
    return [
        'content' => all_content(),
        'blocks' => blocks_state(),
        'gallery' => gallery_items(),
        'settings' => [
            'site_title' => setting('site_title', '4cus — Digital Team'),
            'site_description' => setting('site_description', '4cus — digital team'),
            'contact_email' => setting('contact_email', ''),
            'contact_phone' => setting('contact_phone', ''),
        ],
    ];
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}