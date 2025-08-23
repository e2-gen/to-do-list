<?php
// إعدادات تسجيل الدخول عبر جوجل
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', 'https://to-do-gen.unaux.com/pages/google-login.php');

// مكتبة Google API Client
require_once __DIR__ . '/../vendor/autoload.php';

$googleConfig = new Google_Client();
$googleConfig->setClientId(GOOGLE_CLIENT_ID);
$googleConfig->setClientSecret(GOOGLE_CLIENT_SECRET);
$googleConfig->setRedirectUri(GOOGLE_REDIRECT_URI);
$googleConfig->addScope('email');
$googleConfig->addScope('profile');
?>
