<?php
require_once '../includes/auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        header("Location: ../index.php");
        exit();
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}

// إنشاء رابط تسجيل الدخول بجوجل
require_once '../includes/google-config.php';
$googleLoginUrl = getGoogleAuthUrl();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - To-Do List Pro</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-tasks"></i> To-Do List Pro</h1>
            <h2>تسجيل الدخول</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">اسم المستخدم أو البريد الإلكتروني:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">كلمة المرور:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">تسجيل الدخول</button>
            </form>
            
            <div class="social-login-divider">
                <span>أو</span>
            </div>
            
            <a href="<?php echo $googleLoginUrl; ?>" class="btn-google">
                <i class="fab fa-google"></i> تسجيل الدخول باستخدام جوجل
            </a>
            
            <p class="auth-link">
                ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
            </p>
        </div>
    </div>
</body>
</html>
