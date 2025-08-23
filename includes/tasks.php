<?php
require_once 'database.php';
require_once 'security.php';

class TaskManager {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // إنشاء مهمة جديدة
    public function createTask($title, $description, $due_date, $priority, $user_id, $category_id = null) {
        // تنقية المدخلات
        $title = Sanitizer::sanitizeInput($title);
        $description = Sanitizer::sanitizeInput($description);
        
        $query = "INSERT INTO tasks (title, description, due_date, priority, user_id, category_id) 
                  VALUES (:title, :description, :due_date, :priority, :user_id, :category_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':category_id', $category_id);

        return $stmt->execute();
    }

    // تحديث المهمة
    public function updateTask($task_id, $title, $description, $due_date, $priority, $category_id) {
        // تنقية المدخلات
        $title = Sanitizer::sanitizeInput($title);
        $description = Sanitizer::sanitizeInput($description);
        
        $query = "UPDATE tasks SET title = :title, description = :description, due_date = :due_date, 
                  priority = :priority, category_id = :category_id WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':id', $task_id);

        return $stmt->execute();
    }

    // حذف المهمة (نقل إلى الأرشيف)
    public function deleteTask($task_id, $user_id) {
        $query = "UPDATE tasks SET deleted = 1, deleted_at = NOW() WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // استعادة المهمة من الأرشيف
    public function restoreTask($task_id, $user_id) {
        $query = "UPDATE tasks SET deleted = 0, deleted_at = NULL WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // الحصول على جميع مهام المستخدم (باستثناء المحذوفة)
    public function getUserTasks($user_id, $completed = null, $category_id = null) {
        $query = "SELECT t.*, c.name as category_name FROM tasks t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  WHERE t.user_id = :user_id AND t.deleted = 0";
        
        if ($completed !== null) {
            $query .= " AND t.completed = :completed";
        }
        
        if ($category_id !== null) {
            $query .= " AND t.category_id = :category_id";
        }
        
        $query .= " ORDER BY t.priority DESC, t.due_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($completed !== null) {
            $stmt->bindParam(':completed', $completed, PDO::PARAM_BOOL);
        }
        
        if ($category_id !== null) {
            $stmt->bindParam(':category_id', $category_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // الحصول على المهام المحذوفة (في الأرشيف)
    public function getArchivedTasks($user_id) {
        $query = "SELECT t.*, c.name as category_name FROM tasks t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  WHERE t.user_id = :user_id AND t.deleted = 1
                  ORDER BY t.deleted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // تحديث حالة المهمة (مكتملة/غير مكتملة)
    public function toggleTaskCompletion($task_id, $user_id) {
        $query = "UPDATE tasks SET completed = NOT completed, completed_at = CASE WHEN completed = 0 THEN NOW() ELSE NULL END WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // الحصول على مهمة محددة
    public function getTask($task_id, $user_id) {
        $query = "SELECT t.*, c.name as category_name FROM tasks t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  WHERE t.id = :id AND t.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch();
    }
}
?>
