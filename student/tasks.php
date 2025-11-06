<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('student');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle task submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_task'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $notes = clean_input($_POST['notes']);
    
    // Verify assignment belongs to user
    $stmt = $pdo->prepare("SELECT * FROM task_assignments WHERE id = ? AND student_id = ?");
    $stmt->execute([$assignment_id, $user_id]);
    $assignment = $stmt->fetch();
    
    if ($assignment) {
        // Handle photo upload
        $photo_filename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo_filename = upload_file($_FILES['photo'], '../uploads/');
        }
        
        // Create submission
        $stmt = $pdo->prepare("
            INSERT INTO task_submissions (assignment_id, student_id, photo_evidence, notes)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$assignment_id, $user_id, $photo_filename, $notes])) {
            // Update assignment status
            $stmt = $pdo->prepare("UPDATE task_assignments SET status = 'submitted' WHERE id = ?");
            $stmt->execute([$assignment_id]);
            
            $message = 'Task submitted successfully! Waiting for teacher review.';
        } else {
            $error = 'Failed to submit task. Please try again.';
        }
    } else {
        $error = 'Invalid task assignment.';
    }
}

// Get all tasks
$stmt = $pdo->prepare("
    SELECT ta.*, t.title, t.description, t.location, t.base_points,
           ts.submission_date, ts.status as submission_status, ts.review_notes
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    LEFT JOIN task_submissions ts ON ta.id = ts.assignment_id
    WHERE ta.student_id = ?
    ORDER BY 
        CASE ta.status
            WHEN 'pending' THEN 1
            WHEN 'submitted' THEN 2
            WHEN 'approved' THEN 3
            WHEN 'rejected' THEN 4
        END,
        ta.due_date ASC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

// Check if showing submission form
$show_submit_form = isset($_GET['submit']) ? (int)$_GET['submit'] : null;
$submit_task = null;

if ($show_submit_form) {
    $stmt = $pdo->prepare("
        SELECT ta.*, t.title, t.description, t.location, t.base_points
        FROM task_assignments ta
        JOIN tasks t ON ta.task_id = t.id
        WHERE ta.id = ? AND ta.student_id = ? AND ta.status = 'pending'
    ");
    $stmt->execute([$show_submit_form, $user_id]);
    $submit_task = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Sweepstreak</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">🧹 Sweepstreak</div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="tasks.php">My Tasks</a></li>
                    <li><a href="leaderboard.php">Leaderboard</a></li>
                    <li><a href="/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>📋 My Tasks</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Submit Task Form -->
        <?php if ($submit_task): ?>
        <div class="card">
            <div class="card-header">📤 Submit Task: <?php echo htmlspecialchars($submit_task['title']); ?></div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="<?php echo $submit_task['id']; ?>">
                
                <div class="form-group">
                    <label class="form-label">Task Details</label>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($submit_task['location']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($submit_task['description']); ?></p>
                    <p><strong>Points:</strong> <?php echo $submit_task['base_points']; ?> (+ streak bonus)</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="photo">Photo Evidence (Optional)</label>
                    <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                    <small style="color: var(--text-secondary);">Upload a photo showing the completed task</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="notes">Notes (Optional)</label>
                    <textarea id="notes" name="notes" class="form-control" placeholder="Add any additional notes..."></textarea>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" name="submit_task" class="btn btn-primary">Submit Task</button>
                    <a href="tasks.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Tasks List -->
        <div class="card">
            <div class="card-header">All Tasks</div>
            
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-text">No tasks assigned yet</div>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div>
                                <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="task-meta">
                                    <span>📍 <?php echo htmlspecialchars($task['location']); ?></span>
                                    <span>📅 Due: <?php echo format_date($task['due_date']); ?></span>
                                    <span>⭐ <?php echo $task['base_points']; ?> points</span>
                                </div>
                            </div>
                            <?php
                            $status_class = $task['status'] === 'approved' ? 'badge-success' : 
                                          ($task['status'] === 'rejected' ? 'badge-danger' : 
                                          ($task['status'] === 'submitted' ? 'badge-warning' : 'badge-info'));
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($task['status']); ?>
                            </span>
                        </div>
                        
                        <div class="task-description">
                            <?php echo htmlspecialchars($task['description']); ?>
                        </div>
                        
                        <?php if ($task['status'] === 'submitted'): ?>
                            <p style="color: var(--text-secondary); font-size: 0.875rem;">
                                Submitted on <?php echo format_datetime($task['submission_date']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($task['status'] === 'rejected' && $task['review_notes']): ?>
                            <div class="alert alert-danger">
                                <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($task['review_notes']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="task-actions">
                            <?php if ($task['status'] === 'pending'): ?>
                                <a href="?submit=<?php echo $task['id']; ?>" class="btn btn-primary btn-sm">Submit Task</a>
                            <?php elseif ($task['status'] === 'rejected'): ?>
                                <a href="?submit=<?php echo $task['id']; ?>" class="btn btn-warning btn-sm">Resubmit Task</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
