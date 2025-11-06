<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('teacher');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $location = clean_input($_POST['location']);
    $base_points = (int)$_POST['base_points'];
    
    if (empty($title) || empty($description) || empty($location) || $base_points < 1) {
        $error = 'Please fill in all fields correctly';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, location, base_points, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$title, $description, $location, $base_points, $user_id])) {
            $message = 'Task created successfully!';
        } else {
            $error = 'Failed to create task';
        }
    }
}

// Handle task assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $task_id = (int)$_POST['task_id'];
    $student_ids = $_POST['student_ids'] ?? [];
    $assigned_date = $_POST['assigned_date'];
    $due_date = $_POST['due_date'];
    
    if (empty($task_id) || empty($student_ids) || empty($assigned_date) || empty($due_date)) {
        $error = 'Please fill in all fields';
    } else {
        $success_count = 0;
        foreach ($student_ids as $student_id) {
            $stmt = $pdo->prepare("
                INSERT INTO task_assignments (task_id, student_id, assigned_by, assigned_date, due_date)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE due_date = VALUES(due_date)
            ");
            
            if ($stmt->execute([$task_id, $student_id, $user_id, $assigned_date, $due_date])) {
                $success_count++;
            }
        }
        
        $message = "Task assigned to $success_count student(s) successfully!";
    }
}

// Handle task deletion
if (isset($_GET['delete'])) {
    $task_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("UPDATE tasks SET is_active = 0 WHERE id = ? AND created_by = ?");
    if ($stmt->execute([$task_id, $user_id])) {
        $message = 'Task deleted successfully!';
    }
}

// Get all tasks
$stmt = $pdo->prepare("
    SELECT t.*, 
           COUNT(DISTINCT ta.id) as assignment_count,
           COUNT(DISTINCT CASE WHEN ta.status = 'approved' THEN ta.id END) as completed_count
    FROM tasks t
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    WHERE t.created_by = ? AND t.is_active = 1
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

// Get all students
$students = $pdo->query("SELECT id, username, full_name FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll();

$action = $_GET['action'] ?? 'list';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tasks - Sweepstreak</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">🧹 Sweepstreak</div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_tasks.php">Manage Tasks</a></li>
                    <li><a href="review_submissions.php">Review Submissions</a></li>
                    <li><a href="analytics.php">Analytics</a></li>
                    <li><a href="/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>📋 Manage Tasks</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 1.5rem; display: flex; gap: 0.5rem;">
            <a href="?action=list" class="btn <?php echo $action === 'list' ? 'btn-primary' : 'btn-secondary'; ?>">View Tasks</a>
            <a href="?action=create" class="btn <?php echo $action === 'create' ? 'btn-primary' : 'btn-secondary'; ?>">Create Task</a>
            <a href="?action=assign" class="btn <?php echo $action === 'assign' ? 'btn-primary' : 'btn-secondary'; ?>">Assign Task</a>
        </div>

        <?php if ($action === 'create'): ?>
        <!-- Create Task Form -->
        <div class="card">
            <div class="card-header">➕ Create New Task</div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="title">Task Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control" 
                           placeholder="e.g., Classroom 101, Cafeteria, Library" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="base_points">Base Points</label>
                    <input type="number" id="base_points" name="base_points" class="form-control" 
                           min="1" value="10" required>
                </div>
                
                <button type="submit" name="create_task" class="btn btn-primary">Create Task</button>
            </form>
        </div>

        <?php elseif ($action === 'assign'): ?>
        <!-- Assign Task Form -->
        <div class="card">
            <div class="card-header">📌 Assign Task to Students</div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="task_id">Select Task</label>
                    <select id="task_id" name="task_id" class="form-control" required>
                        <option value="">Choose a task...</option>
                        <?php foreach ($tasks as $task): ?>
                            <option value="<?php echo $task['id']; ?>">
                                <?php echo htmlspecialchars($task['title']); ?> (<?php echo $task['base_points']; ?> pts)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Students</label>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 6px;">
                        <?php foreach ($students as $student): ?>
                            <label style="display: block; padding: 0.25rem;">
                                <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['full_name']); ?> (@<?php echo htmlspecialchars($student['username']); ?>)
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="assigned_date">Assigned Date</label>
                        <input type="date" id="assigned_date" name="assigned_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                </div>
                
                <button type="submit" name="assign_task" class="btn btn-primary">Assign Task</button>
            </form>
        </div>

        <?php else: ?>
        <!-- Tasks List -->
        <div class="card">
            <div class="card-header">All Tasks</div>
            
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <div class="empty-state-text">No tasks created yet</div>
                </div>
            <?php else: ?>
                <div class="table" style="display: block; overflow-x: auto;">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Points</th>
                                <th>Assignments</th>
                                <th>Completed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['title']); ?></strong><br>
                                        <small style="color: var(--text-secondary);">
                                            <?php echo htmlspecialchars(substr($task['description'], 0, 50)); ?>...
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['location']); ?></td>
                                    <td><?php echo $task['base_points']; ?></td>
                                    <td><?php echo $task['assignment_count']; ?></td>
                                    <td><?php echo $task['completed_count']; ?></td>
                                    <td>
                                        <a href="?action=assign&task_id=<?php echo $task['id']; ?>" 
                                           class="btn btn-primary btn-sm">Assign</a>
                                        <a href="?delete=<?php echo $task['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this task?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
