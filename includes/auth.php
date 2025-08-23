<?php
require_once 'database.php';
require_once 'security.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // تسجيل مستخدم جديد
    public function register($username, $email, $password) {
        // تنقية المدخلات
        $username = Sanitizer::sanitizeInput($username);
        $email = Sanitizer::sanitizeInput($email);
        
        // التحقق من صحة البريد الإلكتروني
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "البريد الإلكتروني غير صحيح";
        }
        
        // التحقق من قوة كلمة المرور
        if (strlen($password) < 8) {
            return "كلمة المرور يجب أن تكون 8 أحرف على الأقل";
        }

        // التحقق من وجود المستخدم مسبقاً
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return "اسم المستخدم أو البريد الإلكتروني موجود مسبقاً";
        }

        // تسجيل المستخدم الجديد
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);

        if ($stmt->execute()) {
            return true;
        }
        return "خطأ في إنشاء الحساب";
    }

    // تسجيل الدخول
    public function login($username, $password) {
        $username = Sanitizer::sanitizeInput($username);
        
        $query = "SELECT id, username, password, login_attempts, last_attempt FROM users WHERE username = :username OR email = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            
            // التحقق من عدد محاولات الدخول الفاشلة
            if ($user['login_attempts'] >= 5 && time() - strtotime($user['last_attempt']) < 1800) {
                return "تم تجاوز عدد المحاولات المسموح بها، يرجى المحاولة بعد 30 دقيقة";
            }
            
            if (password_verify($password, $user['password'])) {
                // إعادة تعيين عداد المحاولات الفاشلة
                $this->resetLoginAttempts($user['id']);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                
                return true;
            } else {
                // زيادة عداد المحاولات الفاشلة
                $this->incrementLoginAttempts($user['id']);
                return "اسم المستخدم أو كلمة المرور غير صحيحة";
            }
        }
        return "اسم المستخدم أو كلمة المرور غير صحيحة";
    }

    private function incrementLoginAttempts($user_id) {
        $query = "UPDATE users SET login_attempts = login_attempts + 1, last_attempt = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
    }

    private function resetLoginAttempts($user_id) {
        $query = "UPDATE users SET login_attempts = 0, last_attempt = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
    }

    // التحقق من تسجيل الدخول
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // التحقق من تغيير عنوان IP أو متصفح المستخدم
        if ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->logout();
            return false;
        }
        
        return true;
    }

    // تسجيل الخروج
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        header("Location: pages/login.php");
        exit();
    }

    // الحصول على معلومات المستخدم
    public function getUser($user_id) {
        $query = "SELECT id, username, email, created_at FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
