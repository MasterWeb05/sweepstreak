<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

init_session();

// Redirect if already logged in
if (is_logged_in()) {
    $redirect = has_role('teacher') ? '/teacher/dashboard.php' : '/student/dashboard.php';
    header("Location: $redirect");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            $redirect = $user['role'] === 'teacher' ? '/teacher/dashboard.php' : '/student/dashboard.php';
            header("Location: $redirect");
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sweepstreak</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🧹 Sweepstreak</h1>
                <p>Login to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 1rem; color: var(--text-secondary);">
                Don't have an account? <a href="register.php" style="color: var(--primary-color);">Register here</a>
            </p>
            
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <p style="font-size: 0.875rem; color: var(--text-secondary); text-align: center;">
                    <strong>Demo Accounts:</strong><br>
                    Teacher: teacher1 / teacher123<br>
                    Student: student1 / student123
                </p>
            </div>
        </div>
    </div>
</body>
</html>
