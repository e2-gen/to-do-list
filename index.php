<?php
require_once 'includes/auth.php';
require_once 'includes/tasks.php';
require_once 'includes/categories.php';
require_once 'includes/security.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: pages/login.php");
    exit();
}

$taskManager = new TaskManager();
$categoryManager = new CategoryManager();

$user_id = $_SESSION['user_id'];
$tasks = $taskManager->getUserTasks($user_id);
$categories = $categoryManager->getUserCategories($user_id);

// توليد رمز CSRF
$csrf_token = CSRFProtection::generateToken();

// معالجة إضافة مهمة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
    // التحقق من رمز CSRF
    if (!CSRFProtection::validateToken($_POST['csrf_token'])) {
        die('طلب غير صالح');
    }
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

    if (!empty($title)) {
        $taskManager->createTask($title, $description, $due_date, $priority, $user_id, $category_id);
        header("Location: index.php");
        exit();
    }
}

// معالجة إضافة فئة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    // التحقق من رمز CSRF
    if (!CSRFProtection::validateToken($_POST['csrf_token'])) {
        die('طلب غير صالح');
    }
    
    $name = trim($_POST['category_name']);
    
    if (!empty($name)) {
        $categoryManager->createCategory($name, $user_id);
        header("Location: index.php");
        exit();
    }
}

// معالجة تغيير حالة المهمة
if (isset($_GET['toggle_task'])) {
    $task_id = $_GET['toggle_task'];
    $taskManager->toggleTaskCompletion($task_id, $user_id);
    header("Location: index.php");
    exit();
}

// معالجة حذف المهمة
if (isset($_GET['delete_task'])) {
    $task_id = $_GET['delete_task'];
    $taskManager->deleteTask($task_id, $user_id);
    header("Location: index.php");
    exit();
}

// إحصائيات المهام
$total_tasks = count($tasks);
$completed_tasks = count(array_filter($tasks, function($task) { return $task['completed']; }));
$pending_tasks = $total_tasks - $completed_tasks;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-tasks"></i> To-Do List Pro</h1>
            <div class="user-info">
                <span>مرحباً, <?php echo Sanitizer::sanitizeOutput($_SESSION['username']); ?></span>
                <a href="archive.php" class="btn-secondary"><i class="fas fa-archive"></i> الأرشيف</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </header>

        <!-- إحصائيات المهام -->
        <div class="tasks-stats">
            <div class="stat-card total">
                <i class="fas fa-tasks"></i>
                <h3>إجمالي المهام</h3>
                <div class="count"><?php echo $total_tasks; ?></div>
            </div>
            <div class="stat-card completed">
                <i class="fas fa-check-circle"></i>
                <h3>مكتملة</h3>
                <div class="count"><?php echo $completed_tasks; ?></div>
            </div>
            <div class="stat-card pending">
                <i class="fas fa-clock"></i>
                <h3>قيد الانتظار</h3>
                <div class="count"><?php echo $pending_tasks; ?></div>
            </div>
        </div>

        <!-- فلترة المهام -->
        <div class="tasks-filter">
            <button class="filter-btn active" data-filter="all">الكل</button>
            <button class="filter-btn" data-filter="completed">مكتملة</button>
            <button class="filter-btn" data-filter="pending">قيد الانتظار</button>
            <button class="filter-btn" data-filter="high">عالي الأولوية</button>
        </div>

        <!-- بحث المهام -->
        <div class="form-group">
            <input type="text" id="task-search" placeholder="ابحث في المهام...">
        </div>

        <div class="main-content">
            <!-- نموذج إضافة مهمة جديدة -->
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> إضافة مهمة جديدة</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <input type="text" name="title" placeholder="عنوان المهمة" required>
                    </div>
                    <div class="form-group">
                        <textarea name="description" placeholder="وصف المهمة (اختياري)"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>تاريخ الاستحقاق:</label>
                            <input type="date" name="due_date">
                        </div>
                        <div class="form-group">
                            <label>الأولوية:</label>
                            <select name="priority">
                                <option value="low">منخفضة</option>
                                <option value="medium" selected>متوسطة</option>
                                <option value="high">عالية</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>الفئة:</label>
                            <select name="category_id">
                                <option value="">بدون فئة</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo Sanitizer::sanitizeOutput($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_task" class="btn-primary"><i class="fas fa-plus"></i> إضافة المهمة</button>
                </form>
            </div>

            <!-- نموذج إضافة فئة جديدة -->
            <div class="card">
                <h2><i class="fas fa-folder-plus"></i> إضافة فئة جديدة</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <input type="text" name="category_name" placeholder="اسم الفئة" required>
                    </div>
                    <button type="submit" name="add_category" class="btn-secondary"><i class="fas fa-folder-plus"></i> إضافة الفئة</button>
                </form>
            </div>

            <!-- عرض المهام -->
            <div class="card">
                <h2><i class="fas fa-list-check"></i> قائمة المهام</h2>
                
                <?php if (count($tasks) > 0): ?>
                    <div class="tasks" id="tasks-container">
                        <?php foreach ($tasks as $task): ?>
                            <div class="task <?php echo $task['completed'] ? 'completed' : ''; ?> priority-<?php echo $task['priority']; ?>" data-task-id="<?php echo $task['id']; ?>">
                                <div class="task-check">
                                    <a href="?toggle_task=<?php echo $task['id']; ?>">
                                        <i class="fas fa-<?php echo $task['completed'] ? 'check-circle' : 'circle'; ?>"></i>
                                    </a>
                                </div>
                                <div class="task-details">
                                    <h3><?php echo Sanitizer::sanitizeOutput($task['title']); ?></h3>
                                    <?php if (!empty($task['description'])): ?>
                                        <p class="task-description"><?php echo Sanitizer::sanitizeOutput($task['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="task-meta">
                                        <?php if (!empty($task['due_date'])): ?>
                                            <span class="due-date">
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo date('Y-m-d', strtotime($task['due_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($task['category_name'])): ?>
                                            <span class="category">
                                                <i class="fas fa-folder"></i> 
                                                <?php echo Sanitizer::sanitizeOutput($task['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="priority priority-<?php echo $task['priority']; ?>">
                                            <i class="fas fa-flag"></i> 
                                            <?php 
                                                $priority_labels = [
                                                    'low' => 'منخفضة',
                                                    'medium' => 'متوسطة',
                                                    'high' => 'عالية'
                                                ];
                                                echo $priority_labels[$task['priority']]; 
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <a href="?delete_task=<?php echo $task['id']; ?>" class="delete-btn" onclick="return confirm('هل أنت متأكد من نقل هذه المهمة إلى الأرشيف؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($tasks) > 5): ?>
                        <div style="text-align: center; margin-top: 20px;">
                            <button id="load-more" class="btn-secondary"><i class="fas fa-chevron-down"></i> تحميل المزيد</button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="no-tasks"><i class="fas fa-inbox"></i> لا توجد مهام حالياً. قم بإضافة مهمة جديدة!</p>
                <?php endif; ?>
            </div>

            <!-- عرض الفئات -->
            <div class="card">
                <h2><i class="fas fa-folder"></i> فئات المهام</h2>
                
                <?php if (count($categories) > 0): ?>
                    <div class="categories">
                        <?php foreach ($categories as $category): ?>
                            <div class="category">
                                <h3><i class="fas fa-folder"></i> <?php echo Sanitizer::sanitizeOutput($category['name']); ?></h3>
                                <span class="task-count"><?php echo $category['task_count']; ?> مهمة</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-categories"><i class="fas fa-folder-open"></i> لا توجد فئات حالياً. قم بإضافة فئة جديدة!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
