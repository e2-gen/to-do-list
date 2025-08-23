<?php
// إعدادات التطبيق
define('APP_NAME', 'To-Do List Pro');
define('APP_VERSION', '2.0.0');
define('BASE_URL', 'http://localhost/todo-app');

// إعدادات قاعدة البيانات
define('DB_HOST', 'sql307.ezyro.com');
define('DB_NAME', 'ezyro_39190610_todolist');
define('DB_USER', 'ezyro_39190610');
define('DB_PASS', '72f54b9a3daa2');

// إعدادات الجلسة المحسنة
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// تفعيل الإبلاغ عن الأخطاء (تعطيل في بيئة الإنتاج)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// المنطقة الزمنية
date_default_timezone_set('Africa/Cairo');

// تضمين ملف الأمان
require_once 'security.php';

// تطبيق إعدادات الجلسة الآمنة
SessionManager::secureSession();
?>
