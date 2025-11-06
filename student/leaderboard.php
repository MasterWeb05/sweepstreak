<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('student');

$user_id = $_SESSION['user_id'];

// Get leaderboard
$leaderboard = get_leaderboard(50, $pdo);

// Get current user's rank
$stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as rank
    FROM users
    WHERE role = 'student' 
    AND (total_points > (SELECT total_points FROM users WHERE id = ?)
         OR (total_points = (SELECT total_points FROM users WHERE id = ?) 
             AND current_streak > (SELECT current_streak FROM users WHERE id = ?)))
");
$stmt->execute([$user_id, $user_id, $user_id]);
$user_rank = $stmt->fetch()['rank'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Sweepstreak</title>
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
        <h1>🏆 Leaderboard</h1>
        
        <div class="card" style="text-align: center; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white;">
            <h2>Your Rank: #<?php echo $user_rank; ?></h2>
            <p>Keep cleaning to climb the leaderboard!</p>
        </div>

        <div class="card">
            <div class="card-header">Top Students</div>
            
            <?php if (empty($leaderboard)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏆</div>
                    <div class="empty-state-text">No students yet</div>
                </div>
            <?php else: ?>
                <?php foreach ($leaderboard as $index => $student): ?>
                    <?php 
                    $rank = $index + 1;
                    $is_current_user = $student['id'] == $user_id;
                    $rank_emoji = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : ''));
                    ?>
                    <div class="leaderboard-item" style="<?php echo $is_current_user ? 'border: 2px solid var(--primary-color);' : ''; ?>">
                        <div class="leaderboard-rank">
                            <?php echo $rank_emoji ? $rank_emoji : $rank; ?>
                        </div>
                        <div class="leaderboard-avatar">
                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                        </div>
                        <div class="leaderboard-info">
                            <div class="leaderboard-name">
                                <?php echo htmlspecialchars($student['full_name']); ?>
                                <?php if ($is_current_user): ?>
                                    <span class="badge badge-info">You</span>
                                <?php endif; ?>
                            </div>
                            <div class="leaderboard-stats">
                                <span>⭐ <?php echo $student['total_points']; ?> points</span>
                                <span>🔥 <?php echo $student['current_streak']; ?> day streak</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
