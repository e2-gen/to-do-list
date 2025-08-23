<?php
require_once 'includes/auth.php';
require_once 'includes/tasks.php';
require_once 'includes/security.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: pages/login.php");
    exit();
}

$taskManager = new TaskManager();
$user_id = $_SESSION['user_id'];
$archived_tasks = $taskManager->getArchivedTasks($user_id);

// معالجة استعادة المهمة
if (isset($_GET['restore_task'])) {
    $task_id = $_GET['restore_task'];
    $taskManager->restoreTask($task_id, $user_id);
    header("Location: archive.php");
    exit();
}

// معالجة الحذف النهائي
if (isset($_GET['delete_permanent'])) {
    $task_id = $_GET['delete_permanent'];
    // سيتم تنفيذ هذا في المستقبل بعد إضافة الوظيفة المناسبة
    header("Location: archive.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأرشيف - To-Do List Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-archive"></i> الأرشيف</h1>
            <div class="user-info">
                <span>مرحباً, <?php echo Sanitizer::sanitizeOutput($_SESSION['username']); ?></span>
                <a href="index.php" class="btn-secondary"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </header>

        <div class="main-content">
            <!-- عرض المهام المؤرشفة -->
            <div class="card">
                <h2><i class="fas fa-trash"></i> المهام المحذوفة</h2>
                
                <?php if (count($archived_tasks) > 0): ?>
                    <div class="tasks">
                        <?php foreach ($archived_tasks as $task): ?>
                            <div class="task archived priority-<?php echo $task['priority']; ?>">
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
                                        
                                        <span class="deleted-date">
                                            <i class="fas fa-clock"></i> 
                                            تم الحذف في: <?php echo date('Y-m-d H:i', strtotime($task['deleted_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <a href="?restore_task=<?php echo $task['id']; ?>" class="restore-btn" title="استعادة">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                    <a href="?delete_permanent=<?php echo $task['id']; ?>" class="delete-permanent-btn" title="حذف نهائي" onclick="return confirm('هل أنت متأكد من الحذف النهائي؟ لا يمكن التراجع عن هذا الإجراء')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-tasks"><i class="fas fa-inbox"></i> لا توجد مهام في الأرشيف حالياً.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
