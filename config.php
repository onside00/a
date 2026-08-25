<?php
declare(strict_types=1);

/**
 * Sports Live configuration
 * IMPORTANT:
 * 1) Change DB credentials below.
 * 2) Change the admin password hash after first login.
 * 3) Keep this file unreadable from the web (protected by .htaccess).
 */

define('DB_HOST', getenv('SPORTS_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('SPORTS_DB_NAME') ?: 'sports_live');
define('DB_USER', getenv('SPORTS_DB_USER') ?: 'sports_user');
define('DB_PASS', getenv('SPORTS_DB_PASS') ?: 'CHANGE_ME_DB_PASSWORD');

define('SITE_NAME', 'الفارس لايف');
define('SITE_TIMEZONE', 'Asia/Baghdad');

/**
 * Default admin password:
 * Admin@2026#Secure
 *
 * Change it as soon as the site is running.
 */
define('ADMIN_PASSWORD_HASH', getenv('SPORTS_ADMIN_PASSWORD_HASH') ?: '$2y$12$hGk6zVlqQj2Ko6tecT2aDe8ucRi3PqdHraUFlwNHWOUwT6VyN8ydG');

date_default_timezone_set(SITE_TIMEZONE);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function is_admin(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: admin.php');
        exit;
    }
}

function safe_logo_name(?string $filename): string {
    return basename((string)$filename);
}

function upload_png(string $fieldName, ?string $oldFile = null): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldFile;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('فشل رفع الصورة.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('حجم الصورة يجب ألا يتجاوز 2MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if ($mime !== 'image/png') {
        throw new RuntimeException('يسمح فقط بصور PNG.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false || ($imageInfo[2] ?? null) !== IMAGETYPE_PNG) {
        throw new RuntimeException('الملف ليس صورة PNG صالحة.');
    }

    $name = bin2hex(random_bytes(16)) . '.png';
    $targetDir = __DIR__ . '/uploads/teams';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $target = $targetDir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('تعذر حفظ الصورة.');
    }

    if ($oldFile) {
        $oldPath = $targetDir . '/' . basename($oldFile);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return $name;
}
