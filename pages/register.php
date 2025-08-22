<?php
require_once '../includes/auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // التحقق من تطابق كلمتي المرور
    if ($password !== $confirm_password) {
        $error = 'كلمتا المرور غير متطابقتين';
    } else {
        // محاولة تسجيل المستخدم الجديد
        if ($auth->register($username, $email, $password)) {
            $success = 'تم إنشاء الحساب بنجاح. يمكنك الآن <a href="login.php">تسجيل الدخول</a>.';
        } else {
            $error = 'اسم المستخدم أو البريد الإلكتروني موجود مسبقاً';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب - To-Do List Pro</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-tasks"></i> To-Do List Pro</h1>
            <h2>إنشاء حساب جديد</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">اسم المستخدم:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">كلمة المرور:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">تأكيد كلمة المرور:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">إنشاء الحساب</button>
                </form>
                
                <p class="auth-link">
                    لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>