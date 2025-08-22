<?php
require_once 'database.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // تسجيل مستخدم جديد
    public function register($username, $email, $password) {
        // التحقق من وجود المستخدم مسبقاً
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false; // المستخدم موجود مسبقاً
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
        return false;
    }

    // تسجيل الدخول
    public function login($username, $password) {
        $query = "SELECT id, username, password FROM users WHERE username = :username OR email = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
        }
        return false;
    }

    // التحقق من تسجيل الدخول
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // تسجيل الخروج
    public function logout() {
        session_destroy();
        header("Location: login.php");
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