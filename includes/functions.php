<?php
// Common functions for Sweepstreak

// Start session if not already started
function init_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function is_logged_in() {
    init_session();
    return isset($_SESSION['user_id']);
}

// Check if user has specific role
function has_role($role) {
    init_session();
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Location: /auth/login.php');
        exit();
    }
}

// Redirect if not specific role
function require_role($role) {
    require_login();
    if (!has_role($role)) {
        header('Location: /index.php');
        exit();
    }
}

// Sanitize input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Calculate streak
function calculate_streak($user_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT last_completion_date, current_streak 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['last_completion_date']) {
        return 1; // First completion
    }
    
    $last_date = new DateTime($user['last_completion_date']);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $last_date->setTime(0, 0, 0);
    
    $diff = $today->diff($last_date)->days;
    
    if ($diff == 0) {
        // Same day, maintain streak
        return $user['current_streak'];
    } elseif ($diff == 1) {
        // Consecutive day, increment streak
        return $user['current_streak'] + 1;
    } else {
        // Streak broken, start new
        return 1;
    }
}

// Update user streak and points
function update_user_progress($user_id, $points, $pdo) {
    $new_streak = calculate_streak($user_id, $pdo);
    
    // Calculate bonus points for streak
    $streak_multiplier = 1 + (floor($new_streak / 3) * 0.1); // 10% bonus every 3 days
    $total_points = round($points * $streak_multiplier);
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET total_points = total_points + ?,
            current_streak = ?,
            longest_streak = GREATEST(longest_streak, ?),
            last_completion_date = CURDATE()
        WHERE id = ?
    ");
    $stmt->execute([$total_points, $new_streak, $new_streak, $user_id]);
    
    // Check for new badges
    check_and_award_badges($user_id, $pdo);
    
    return ['points' => $total_points, 'streak' => $new_streak];
}

// Check and award badges
function check_and_award_badges($user_id, $pdo) {
    // Get user stats
    $stmt = $pdo->prepare("
        SELECT total_points, current_streak,
               (SELECT COUNT(*) FROM task_submissions WHERE student_id = ? AND status = 'approved') as completed_tasks
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id, $user_id]);
    $stats = $stmt->fetch();
    
    // Get all badges
    $badges = $pdo->query("SELECT * FROM badges")->fetchAll();
    
    foreach ($badges as $badge) {
        // Check if user already has this badge
        $stmt = $pdo->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
        $stmt->execute([$user_id, $badge['id']]);
        
        if ($stmt->fetch()) {
            continue; // Already has badge
        }
        
        // Check if user meets requirements
        $earned = false;
        switch ($badge['requirement_type']) {
            case 'points':
                $earned = $stats['total_points'] >= $badge['requirement_value'];
                break;
            case 'streak':
                $earned = $stats['current_streak'] >= $badge['requirement_value'];
                break;
            case 'tasks':
                $earned = $stats['completed_tasks'] >= $badge['requirement_value'];
                break;
        }
        
        if ($earned) {
            $stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $badge['id']]);
        }
    }
}

// Get user badges
function get_user_badges($user_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT b.*, ub.earned_date
        FROM badges b
        JOIN user_badges ub ON b.id = ub.badge_id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_date DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Format date for display
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime for display
function format_datetime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

// Get leaderboard
function get_leaderboard($limit = 10, $pdo) {
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, total_points, current_streak, profile_image
        FROM users
        WHERE role = 'student'
        ORDER BY total_points DESC, current_streak DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Upload file
function upload_file($file, $upload_dir = 'uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}
?>
