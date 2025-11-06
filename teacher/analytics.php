<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('teacher');

// Get overall statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_students,
        AVG(u.total_points) as avg_points,
        AVG(u.current_streak) as avg_streak,
        MAX(u.current_streak) as max_streak
    FROM users u
    WHERE u.role = 'student'
");
$overall_stats = $stmt->fetch();

// Get compliance rate by week
$stmt = $pdo->query("
    SELECT 
        WEEK(assigned_date) as week_num,
        COUNT(*) as total_assigned,
        SUM(CASE WHEN status IN ('submitted', 'approved') THEN 1 ELSE 0 END) as completed,
        ROUND(SUM(CASE WHEN status IN ('submitted', 'approved') THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as compliance_rate
    FROM task_assignments
    WHERE assigned_date >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
    GROUP BY WEEK(assigned_date)
    ORDER BY week_num DESC
");
$weekly_compliance = $stmt->fetchAll();

// Get top performers
$stmt = $pdo->query("
    SELECT u.full_name, u.username, u.total_points, u.current_streak,
           COUNT(ts.id) as completed_tasks
    FROM users u
    LEFT JOIN task_submissions ts ON u.id = ts.student_id AND ts.status = 'approved'
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.total_points DESC
    LIMIT 10
");
$top_performers = $stmt->fetchAll();

// Get task completion rates
$stmt = $pdo->query("
    SELECT t.title, t.location,
           COUNT(ta.id) as assigned,
           SUM(CASE WHEN ta.status = 'approved' THEN 1 ELSE 0 END) as completed,
           ROUND(SUM(CASE WHEN ta.status = 'approved' THEN 1 ELSE 0 END) / COUNT(ta.id) * 100, 1) as completion_rate
    FROM tasks t
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    WHERE t.is_active = 1
    GROUP BY t.id
    HAVING assigned > 0
    ORDER BY completion_rate DESC
");
$task_stats = $stmt->fetchAll();

// Get student engagement
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN total_points = 0 THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN total_points > 0 AND total_points < 50 THEN 1 ELSE 0 END) as low_engagement,
        SUM(CASE WHEN total_points >= 50 AND total_points < 200 THEN 1 ELSE 0 END) as medium_engagement,
        SUM(CASE WHEN total_points >= 200 THEN 1 ELSE 0 END) as high_engagement
    FROM users
    WHERE role = 'student'
");
$engagement = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Sweepstreak</title>
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
        <h1>📊 Analytics Dashboard</h1>

        <!-- Overall Statistics -->
        <div class="card">
            <div class="card-header">📈 Overall Statistics</div>
            <div class="stats-grid">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--primary-color);">
                        <?php echo $overall_stats['total_students']; ?>
                    </div>
                    <div style="color: var(--text-secondary);">Total Students</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--secondary-color);">
                        <?php echo round($overall_stats['avg_points']); ?>
                    </div>
                    <div style="color: var(--text-secondary);">Avg Points</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--warning-color);">
                        <?php echo round($overall_stats['avg_streak'], 1); ?>
                    </div>
                    <div style="color: var(--text-secondary);">Avg Streak (days)</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--danger-color);">
                        <?php echo $overall_stats['max_streak']; ?>
                    </div>
                    <div style="color: var(--text-secondary);">Longest Streak</div>
                </div>
            </div>
        </div>

        <!-- Student Engagement -->
        <div class="card">
            <div class="card-header">👥 Student Engagement Levels</div>
            <div class="stats-grid">
                <div style="text-align: center; padding: 1rem; background: #fee2e2; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #991b1b;">
                        <?php echo $engagement['inactive']; ?>
                    </div>
                    <div style="color: #7f1d1d;">Inactive (0 pts)</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #fef3c7; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #92400e;">
                        <?php echo $engagement['low_engagement']; ?>
                    </div>
                    <div style="color: #78350f;">Low (1-49 pts)</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #dbeafe; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #1e40af;">
                        <?php echo $engagement['medium_engagement']; ?>
                    </div>
                    <div style="color: #1e3a8a;">Medium (50-199 pts)</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #d1fae5; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #065f46;">
                        <?php echo $engagement['high_engagement']; ?>
                    </div>
                    <div style="color: #064e3b;">High (200+ pts)</div>
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Weekly Compliance -->
            <div class="card">
                <div class="card-header">📅 Weekly Compliance Rates</div>
                
                <?php if (empty($weekly_compliance)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <div class="empty-state-text">No data available</div>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Week</th>
                                <th>Assigned</th>
                                <th>Completed</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekly_compliance as $week): ?>
                                <tr>
                                    <td>Week <?php echo $week['week_num']; ?></td>
                                    <td><?php echo $week['total_assigned']; ?></td>
                                    <td><?php echo $week['completed']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $week['compliance_rate'] >= 80 ? 'badge-success' : ($week['compliance_rate'] >= 60 ? 'badge-warning' : 'badge-danger'); ?>">
                                            <?php echo $week['compliance_rate']; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Top Performers -->
            <div class="card">
                <div class="card-header">🏆 Top Performers</div>
                
                <?php if (empty($top_performers)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏆</div>
                        <div class="empty-state-text">No data available</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($top_performers as $index => $student): ?>
                        <div class="leaderboard-item">
                            <div class="leaderboard-rank"><?php echo $index + 1; ?></div>
                            <div class="leaderboard-avatar">
                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                            </div>
                            <div class="leaderboard-info">
                                <div class="leaderboard-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                <div class="leaderboard-stats">
                                    <span>⭐ <?php echo $student['total_points']; ?> pts</span>
                                    <span>🔥 <?php echo $student['current_streak']; ?> days</span>
                                    <span>✅ <?php echo $student['completed_tasks']; ?> tasks</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Task Performance -->
        <div class="card">
            <div class="card-header">📋 Task Completion Rates</div>
            
            <?php if (empty($task_stats)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <div class="empty-state-text">No task data available</div>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Location</th>
                            <th>Assigned</th>
                            <th>Completed</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($task_stats as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['location']); ?></td>
                                <td><?php echo $task['assigned']; ?></td>
                                <td><?php echo $task['completed']; ?></td>
                                <td>
                                    <span class="badge <?php echo $task['completion_rate'] >= 80 ? 'badge-success' : ($task['completion_rate'] >= 60 ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo $task['completion_rate']; ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Insights -->
        <div class="card">
            <div class="card-header">💡 Insights & Recommendations</div>
            <div style="line-height: 1.8;">
                <?php
                $total_students = $overall_stats['total_students'];
                $avg_points = $overall_stats['avg_points'];
                $inactive_count = $engagement['inactive'];
                
                if ($inactive_count > 0) {
                    echo "<p>⚠️ <strong>$inactive_count student(s)</strong> have not completed any tasks yet. Consider reaching out to encourage participation.</p>";
                }
                
                if ($avg_points < 50) {
                    echo "<p>📉 Average points are relatively low. Consider increasing task frequency or point values to boost engagement.</p>";
                } else {
                    echo "<p>✅ Good overall engagement! Average points per student: " . round($avg_points) . "</p>";
                }
                
                if (!empty($weekly_compliance)) {
                    $latest_week = $weekly_compliance[0];
                    if ($latest_week['compliance_rate'] < 70) {
                        echo "<p>⚠️ Recent compliance rate is below 70%. Consider reviewing task difficulty or deadlines.</p>";
                    } else {
                        echo "<p>✅ Strong compliance rate this week: {$latest_week['compliance_rate']}%</p>";
                    }
                }
                
                if ($overall_stats['max_streak'] >= 7) {
                    echo "<p>🔥 Excellent! Some students are maintaining week-long streaks. The gamification is working!</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
