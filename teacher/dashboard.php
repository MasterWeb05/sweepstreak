<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('teacher');

$user_id = $_SESSION['user_id'];

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$total_students = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks WHERE is_active = 1");
$total_tasks = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM task_submissions WHERE status = 'pending'");
$pending_reviews = $stmt->fetch()['count'];

$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM task_assignments 
    WHERE status = 'approved' 
    AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$completed_this_week = $stmt->fetch()['count'];

// Get recent submissions
$stmt = $pdo->prepare("
    SELECT ts.*, ta.task_id, t.title as task_title, u.full_name as student_name
    FROM task_submissions ts
    JOIN task_assignments ta ON ts.assignment_id = ta.id
    JOIN tasks t ON ta.task_id = t.id
    JOIN users u ON ts.student_id = u.id
    WHERE ts.status = 'pending'
    ORDER BY ts.submission_date DESC
    LIMIT 10
");
$stmt->execute();
$pending_submissions = $stmt->fetchAll();

// Get compliance rate
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('submitted', 'approved') THEN 1 ELSE 0 END) as completed
    FROM task_assignments
    WHERE due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$compliance = $stmt->fetch();
$compliance_rate = $compliance['total'] > 0 ? round(($compliance['completed'] / $compliance['total']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Sweepstreak</title>
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
        <h1>Teacher Dashboard 👨‍🏫</h1>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <span class="stat-value"><?php echo $total_students; ?></span>
                <span class="stat-label">Total Students</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <span class="stat-value"><?php echo $total_tasks; ?></span>
                <span class="stat-label">Active Tasks</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <span class="stat-value"><?php echo $pending_reviews; ?></span>
                <span class="stat-label">Pending Reviews</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <span class="stat-value"><?php echo $compliance_rate; ?>%</span>
                <span class="stat-label">Compliance Rate</span>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">⚡ Quick Actions</div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="manage_tasks.php?action=create" class="btn btn-primary">➕ Create New Task</a>
                    <a href="manage_tasks.php?action=assign" class="btn btn-success">📌 Assign Tasks</a>
                    <a href="review_submissions.php" class="btn btn-secondary">✅ Review Submissions (<?php echo $pending_reviews; ?>)</a>
                    <a href="analytics.php" class="btn btn-secondary">📊 View Analytics</a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">🔔 Recent Submissions</div>
                
                <?php if (empty($pending_submissions)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✨</div>
                        <div class="empty-state-text">No pending submissions</div>
                    </div>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($pending_submissions as $submission): ?>
                            <div style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                    <?php echo htmlspecialchars($submission['student_name']); ?>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">
                                    <?php echo htmlspecialchars($submission['task_title']); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                    <?php echo format_datetime($submission['submission_date']); ?>
                                </div>
                                <a href="review_submissions.php?id=<?php echo $submission['id']; ?>" 
                                   class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">
                                    Review
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Weekly Overview -->
        <div class="card">
            <div class="card-header">📈 This Week's Overview</div>
            <div class="stats-grid">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--secondary-color);">
                        <?php echo $completed_this_week; ?>
                    </div>
                    <div style="color: var(--text-secondary);">Tasks Completed</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--primary-color);">
                        <?php echo $compliance_rate; ?>%
                    </div>
                    <div style="color: var(--text-secondary);">Compliance Rate</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--warning-color);">
                        <?php echo $pending_reviews; ?>
                    </div>
                    <div style="color: var(--text-secondary);">Awaiting Review</div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
