<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('student');

$user_id = $_SESSION['user_id'];

// Get user stats
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get pending tasks
$stmt = $pdo->prepare("
    SELECT ta.*, t.title, t.description, t.location, t.base_points
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    WHERE ta.student_id = ? AND ta.status = 'pending'
    ORDER BY ta.due_date ASC
");
$stmt->execute([$user_id]);
$pending_tasks = $stmt->fetchAll();

// Get submitted tasks
$stmt = $pdo->prepare("
    SELECT ta.*, t.title, t.description, t.location, ts.submission_date, ts.status as submission_status
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    LEFT JOIN task_submissions ts ON ta.id = ts.assignment_id
    WHERE ta.student_id = ? AND ta.status = 'submitted'
    ORDER BY ts.submission_date DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$submitted_tasks = $stmt->fetchAll();

// Get completed tasks count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM task_submissions 
    WHERE student_id = ? AND status = 'approved'
");
$stmt->execute([$user_id]);
$completed_count = $stmt->fetch()['count'];

// Get user badges
$badges = get_user_badges($user_id, $pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Sweepstreak</title>
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
        <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <span class="stat-value"><?php echo $user['current_streak']; ?></span>
                <span class="stat-label">Day Streak</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <span class="stat-value"><?php echo $user['total_points']; ?></span>
                <span class="stat-label">Total Points</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <span class="stat-value"><?php echo $completed_count; ?></span>
                <span class="stat-label">Tasks Completed</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <span class="stat-value"><?php echo count($badges); ?></span>
                <span class="stat-label">Badges Earned</span>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Pending Tasks -->
            <div class="card">
                <div class="card-header">📋 Pending Tasks</div>
                
                <?php if (empty($pending_tasks)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✨</div>
                        <div class="empty-state-text">No pending tasks. Great job!</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_tasks as $task): ?>
                        <div class="task-card">
                            <div class="task-header">
                                <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                <span class="badge badge-warning"><?php echo $task['base_points']; ?> pts</span>
                            </div>
                            <div class="task-meta">
                                <span>📍 <?php echo htmlspecialchars($task['location']); ?></span>
                                <span>📅 Due: <?php echo format_date($task['due_date']); ?></span>
                            </div>
                            <div class="task-description">
                                <?php echo htmlspecialchars($task['description']); ?>
                            </div>
                            <div class="task-actions">
                                <a href="tasks.php?submit=<?php echo $task['id']; ?>" class="btn btn-primary btn-sm">Submit Task</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Submissions -->
            <div class="card">
                <div class="card-header">📤 Recent Submissions</div>
                
                <?php if (empty($submitted_tasks)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-text">No submissions yet</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($submitted_tasks as $task): ?>
                        <div class="task-card">
                            <div class="task-header">
                                <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                <?php
                                $status_class = $task['submission_status'] === 'approved' ? 'badge-success' : 
                                              ($task['submission_status'] === 'rejected' ? 'badge-danger' : 'badge-warning');
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($task['submission_status']); ?>
                                </span>
                            </div>
                            <div class="task-meta">
                                <span>📍 <?php echo htmlspecialchars($task['location']); ?></span>
                                <span>🕐 <?php echo format_datetime($task['submission_date']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badges Section -->
        <?php if (!empty($badges)): ?>
        <div class="card">
            <div class="card-header">🏆 Your Badges</div>
            <div class="badge-grid">
                <?php foreach ($badges as $badge): ?>
                    <div class="badge-item">
                        <div class="badge-icon"><?php echo $badge['icon']; ?></div>
                        <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                        <div class="badge-desc"><?php echo htmlspecialchars($badge['description']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
