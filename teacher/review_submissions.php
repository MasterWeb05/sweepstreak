<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('teacher');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle submission review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $action = $_POST['action'];
    $review_notes = clean_input($_POST['review_notes']);
    
    $stmt = $pdo->prepare("SELECT * FROM task_submissions WHERE id = ?");
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch();
    
    if ($submission) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        // Update submission
        $stmt = $pdo->prepare("
            UPDATE task_submissions 
            SET status = ?, reviewed_by = ?, review_date = NOW(), review_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $user_id, $review_notes, $submission_id]);
        
        // Update assignment
        $stmt = $pdo->prepare("UPDATE task_assignments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $submission['assignment_id']]);
        
        if ($status === 'approved') {
            // Get task points
            $stmt = $pdo->prepare("
                SELECT t.base_points 
                FROM task_assignments ta
                JOIN tasks t ON ta.task_id = t.id
                WHERE ta.id = ?
            ");
            $stmt->execute([$submission['assignment_id']]);
            $task = $stmt->fetch();
            
            // Update user progress
            $result = update_user_progress($submission['student_id'], $task['base_points'], $pdo);
            
            // Update submission with points awarded
            $stmt = $pdo->prepare("UPDATE task_submissions SET points_awarded = ? WHERE id = ?");
            $stmt->execute([$result['points'], $submission_id]);
            
            $message = "Submission approved! Student earned {$result['points']} points (streak: {$result['streak']} days)";
        } else {
            $message = 'Submission rejected. Student can resubmit.';
        }
    } else {
        $error = 'Submission not found';
    }
}

// Get pending submissions
$stmt = $pdo->prepare("
    SELECT ts.*, ta.task_id, ta.due_date, t.title as task_title, t.location, t.base_points,
           u.full_name as student_name, u.username as student_username
    FROM task_submissions ts
    JOIN task_assignments ta ON ts.assignment_id = ta.id
    JOIN tasks t ON ta.task_id = t.id
    JOIN users u ON ts.student_id = u.id
    WHERE ts.status = 'pending'
    ORDER BY ts.submission_date ASC
");
$stmt->execute();
$submissions = $stmt->fetchAll();

// Get specific submission if ID provided
$review_submission = null;
if (isset($_GET['id'])) {
    $submission_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT ts.*, ta.task_id, ta.due_date, t.title as task_title, t.description, t.location, t.base_points,
               u.full_name as student_name, u.username as student_username, u.current_streak
        FROM task_submissions ts
        JOIN task_assignments ta ON ts.assignment_id = ta.id
        JOIN tasks t ON ta.task_id = t.id
        JOIN users u ON ts.student_id = u.id
        WHERE ts.id = ?
    ");
    $stmt->execute([$submission_id]);
    $review_submission = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submissions - Sweepstreak</title>
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
        <h1>✅ Review Submissions</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($review_submission): ?>
        <!-- Review Form -->
        <div class="card">
            <div class="card-header">Review Submission</div>
            
            <div style="margin-bottom: 1.5rem;">
                <h3><?php echo htmlspecialchars($review_submission['task_title']); ?></h3>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($review_submission['student_name']); ?> 
                   (@<?php echo htmlspecialchars($review_submission['student_username']); ?>)</p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($review_submission['location']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($review_submission['description']); ?></p>
                <p><strong>Base Points:</strong> <?php echo $review_submission['base_points']; ?></p>
                <p><strong>Current Streak:</strong> <?php echo $review_submission['current_streak']; ?> days</p>
                <p><strong>Submitted:</strong> <?php echo format_datetime($review_submission['submission_date']); ?></p>
            </div>
            
            <?php if ($review_submission['photo_evidence']): ?>
                <div style="margin-bottom: 1.5rem;">
                    <strong>Photo Evidence:</strong><br>
                    <img src="/uploads/<?php echo htmlspecialchars($review_submission['photo_evidence']); ?>" 
                         alt="Task evidence" 
                         style="max-width: 100%; max-height: 400px; border-radius: 8px; margin-top: 0.5rem;">
                </div>
            <?php endif; ?>
            
            <?php if ($review_submission['notes']): ?>
                <div style="margin-bottom: 1.5rem;">
                    <strong>Student Notes:</strong>
                    <p style="background: var(--light-bg); padding: 1rem; border-radius: 6px; margin-top: 0.5rem;">
                        <?php echo nl2br(htmlspecialchars($review_submission['notes'])); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="submission_id" value="<?php echo $review_submission['id']; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="review_notes">Review Notes (Optional)</label>
                    <textarea id="review_notes" name="review_notes" class="form-control" 
                              placeholder="Add feedback for the student..."></textarea>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" name="review_submission" value="approve" 
                            onclick="return this.form.action.value='approve'" 
                            class="btn btn-success">✅ Approve</button>
                    <button type="submit" name="review_submission" value="reject" 
                            onclick="return this.form.action.value='reject'" 
                            class="btn btn-danger">❌ Reject</button>
                    <a href="review_submissions.php" class="btn btn-secondary">Cancel</a>
                </div>
                <input type="hidden" name="action" value="">
            </form>
        </div>
        
        <script>
            document.querySelectorAll('button[name="review_submission"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelector('input[name="action"]').value = this.value;
                });
            });
        </script>

        <?php else: ?>
        <!-- Submissions List -->
        <div class="card">
            <div class="card-header">Pending Submissions (<?php echo count($submissions); ?>)</div>
            
            <?php if (empty($submissions)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✨</div>
                    <div class="empty-state-text">No pending submissions</div>
                </div>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div>
                                <div class="task-title"><?php echo htmlspecialchars($submission['task_title']); ?></div>
                                <div class="task-meta">
                                    <span>👤 <?php echo htmlspecialchars($submission['student_name']); ?></span>
                                    <span>📍 <?php echo htmlspecialchars($submission['location']); ?></span>
                                    <span>🕐 <?php echo format_datetime($submission['submission_date']); ?></span>
                                </div>
                            </div>
                            <span class="badge badge-warning"><?php echo $submission['base_points']; ?> pts</span>
                        </div>
                        
                        <?php if ($submission['notes']): ?>
                            <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                                <strong>Notes:</strong> <?php echo htmlspecialchars(substr($submission['notes'], 0, 100)); ?>
                                <?php echo strlen($submission['notes']) > 100 ? '...' : ''; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="task-actions">
                            <a href="?id=<?php echo $submission['id']; ?>" class="btn btn-primary btn-sm">Review</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
