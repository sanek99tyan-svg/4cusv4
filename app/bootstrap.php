<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function cms_is_ready(): bool
{
    try {
        db()->query('SELECT 1 FROM settings LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
