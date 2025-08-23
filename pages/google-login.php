<?php
session_start();
require_once '../includes/google-config.php';
require_once '../includes/database.php';
require_once '../includes/security.php';

// معالجة الاستجابة من جوجل
if (isset($_GET['code'])) {
    try {
        // استبدال رمز المصادقة ب token
        $tokenData = getGoogleAccessToken($_GET['code']);
        
        if (!$tokenData || !isset($tokenData['access_token'])) {
            throw new Exception('فشل في الحصول على token من جوجل');
        }
        
        // الحصول على معلومات المستخدم
        $userInfo = getGoogleUserInfo($tokenData['access_token']);
        
        if (!$userInfo || !isset($userInfo['email'])) {
            throw new Exception('فشل في الحصول على معلومات المستخدم من جوجل');
        }
        
        $email = Sanitizer::sanitizeInput($userInfo['email']);
        $name = isset($userInfo['name']) ? Sanitizer::sanitizeInput($userInfo['name']) : '';
        $google_id = isset($userInfo['sub']) ? Sanitizer::sanitizeInput($userInfo['sub']) : '';
        $picture = isset($userInfo['picture']) ? Sanitizer::sanitizeInput($userInfo['picture']) : '';

        // التحقق من وجود المستخدم في قاعدة البيانات
        $db = new Database();
        $conn = $db->getConnection();

        // البحث عن المستخدم باستخدام البريد الإلكتروني أو google_id
        $query = "SELECT * FROM users WHERE email = :email OR google_id = :google_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':google_id', $google_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // المستخدم موجود، تسجيل الدخول
            $user = $stmt->fetch();
            
            // تحديث معلومات جوجل إذا لزم الأمر
            if (empty($user['google_id'])) {
                $updateQuery = "UPDATE users SET google_id = :google_id, profile_picture = :picture WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindParam(':google_id', $google_id);
                $updateStmt->bindParam(':picture', $picture);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['login_method'] = 'google';

            header("Location: ../index.php");
            exit();
        } else {
            // المستخدم جديد، إنشاء حساب جديد
            $username = generateUsernameFromName($name, $email);
            
            // التأكد من أن اسم المستخدم فريد
            $username = makeUniqueUsername($conn, $username);
            
            $insertQuery = "INSERT INTO users (username, email, google_id, profile_picture, email_verified, created_at) 
                            VALUES (:username, :email, :google_id, :picture, 1, NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bindParam(':username', $username);
            $insertStmt->bindParam(':email', $email);
            $insertStmt->bindParam(':google_id', $google_id);
            $insertStmt->bindParam(':picture', $picture);
            
            if ($insertStmt->execute()) {
                $user_id = $conn->lastInsertId();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['login_method'] = 'google';

                header("Location: ../index.php");
                exit();
            } else {
                $error = "حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة مرة أخرى.";
                header("Location: login.php?error=" . urlencode($error));
                exit();
            }
        }
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء المصادقة: " . $e->getMessage();
        header("Location: login.php?error=" . urlencode($error));
        exit();
    }
} else {
    // إعادة توجيه إلى صفحة تسجيل الدخول مع خطأ
    $error = "فشل المصادقة مع جوجل";
    header("Location: login.php?error=" . urlencode($error));
    exit();
}

// دالة لإنشاء اسم مستخدم من الاسم الكامل أو البريد الإلكتروني
function generateUsernameFromName($name, $email) {
    if (!empty($name)) {
        $username = strtolower(str_replace(' ', '', $name));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
        
        // إذا كان الاسم قصيرًا، أضف أرقام عشوائية
        if (strlen($username) < 4) {
            $username .= rand(100, 999);
        }
        
        return $username;
    } else {
        // استخدام جزء من البريد الإلكتروني إذا لم يكن هناك اسم
        $emailParts = explode('@', $email);
        return preg_replace('/[^a-z0-9]/', '', $emailParts[0]);
    }
}

// دالة لجعل اسم المستخدم فريدًا
function makeUniqueUsername($conn, $username) {
    $originalUsername = $username;
    $counter = 1;
    
    while (true) {
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return $username;
        }
        
        $username = $originalUsername . $counter;
        $counter++;
    }
}
?>
