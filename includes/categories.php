<?php
require_once 'database.php';

class CategoryManager {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // إنشاء فئة جديدة
    public function createCategory($name, $user_id) {
        $query = "INSERT INTO categories (name, user_id) VALUES (:name, :user_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // تحديث الفئة
    public function updateCategory($category_id, $name, $user_id) {
        $query = "UPDATE categories SET name = :name WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':id', $category_id);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // حذف الفئة
    public function deleteCategory($category_id, $user_id) {
        $query = "DELETE FROM categories WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $category_id);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // الحصول على جميع فئات المستخدم
    public function getUserCategories($user_id) {
        $query = "SELECT c.*, COUNT(t.id) as task_count 
                  FROM categories c 
                  LEFT JOIN tasks t ON c.id = t.category_id 
                  WHERE c.user_id = :user_id 
                  GROUP BY c.id 
                  ORDER BY c.name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // الحصول على فئة محددة
    public function getCategory($category_id, $user_id) {
        $query = "SELECT * FROM categories WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $category_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch();
    }
}
?>