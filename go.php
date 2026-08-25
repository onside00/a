<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('الرابط غير صالح.');
}

$db->begin_transaction();

try {
    $stmt = $db->prepare("
        SELECT id, redirect_url
        FROM matches
        WHERE id = ?
          AND status = 'active'
          AND match_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();

    if (!$match) {
        $db->rollback();
        http_response_code(404);
        exit('المباراة غير متاحة.');
    }

    $url = trim((string)$match['redirect_url']);

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $db->rollback();
        http_response_code(500);
        exit('رابط التحويل غير صالح.');
    }

    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        $db->rollback();
        http_response_code(400);
        exit('نوع الرابط غير مسموح.');
    }

    $update = $db->prepare("UPDATE matches SET views = views + 1 WHERE id = ?");
    $update->bind_param('i', $id);
    $update->execute();

    $db->commit();

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: ' . $url, true, 302);
    exit;
} catch (Throwable $e) {
    $db->rollback();
    http_response_code(500);
    exit('حدث خطأ غير متوقع.');
}
