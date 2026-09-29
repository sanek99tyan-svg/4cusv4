<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$template = file_get_contents(__DIR__ . '/template.html');
if ($template === false) {
    http_response_code(500);
    exit('Template not found');
}

$payload = cms_is_ready() ? public_payload() : ['content' => [], 'blocks' => [], 'gallery' => [], 'settings' => []];
$settings = $payload['settings'] ?? [];

if (!empty($settings['site_title'])) {
    $template = preg_replace('/<title>.*?<\/title>/si', '<title>' . htmlspecialchars($settings['site_title'], ENT_QUOTES, 'UTF-8') . '</title>', $template, 1);
}
if (!empty($settings['site_description'])) {
    $description = htmlspecialchars($settings['site_description'], ENT_QUOTES, 'UTF-8');
    $template = preg_replace('/<meta\s+name="description"\s+content="[^"]*"\s*\/?>/i', '<meta name="description" content="' . $description . '">', $template, 1);
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$headInject = '<link rel="stylesheet" href="/assets/cms.css">';
$bodyInject = '<script>window.__FOURCUS_CMS__=' . $json . ';</script><script src="/assets/cms.js"></script>';

$template = str_replace('</head>', $headInject . '</head>', $template);
$template = str_replace('</body>', $bodyInject . '</body>', $template);
echo $template;