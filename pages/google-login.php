<?php
session_start();
require_once '../includes/google-config.php';
require_once '../includes/database.php';
require_once '../includes/security.php';

// إنشاء كائن Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

// معالجة الاستجابة من جوجل
if (isset($_GET['code'])) {
    try {
        // استبدال رمز المصادقة ب token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // الحصول على معلومات المستخدم
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $google_id = $google_account_info->id;
        $picture = $google_account_info->picture;

        // التحقق من وجود المستخدم في قاعدة البيانات
        $db = new Database();
        $conn = $db->getConnection();

        // البحث عن المستخدم باستخدام البريد الإلكتروني
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
                $updateQuery = "UPDATE users SET google_id = :google_id WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindParam(':google_id', $google_id);
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
            // إنشاء اسم مستخدم من الاسم الكامل
            $username = generateUsernameFromName($name);
            
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

// دالة لإنشاء اسم مستخدم من الاسم الكامل
function generateUsernameFromName($name) {
    $username = strtolower(str_replace(' ', '', $name));
    $username = preg_replace('/[^a-z0-9]/', '', $username);
    
    // إذا كان الاسم قصيرًا، أضف أرقام عشوائية
    if (strlen($username) < 4) {
        $username .= rand(100, 999);
    }
    
    return $username;
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
