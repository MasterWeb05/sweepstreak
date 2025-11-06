<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

init_session();

// Redirect if already logged in
if (is_logged_in()) {
    $redirect = has_role('teacher') ? '/teacher/dashboard.php' : '/student/dashboard.php';
    header("Location: $redirect");
    exit();
}

// Get some stats for the landing page
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$total_students = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM task_submissions WHERE status = 'approved'");
$total_completions = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT SUM(total_points) as total FROM users WHERE role = 'student'");
$total_points = $stmt->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweepstreak - Gamified Cleaning Monitoring</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 4rem 1rem;
            text-align: center;
        }
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        .feature-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .cta-buttons .btn {
            padding: 1rem 2rem;
            font-size: 1.125rem;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1>🧹 Sweepstreak</h1>
            <p>Transform Cleaning Compliance Through Gamification</p>
            <p style="font-size: 1rem; opacity: 0.8; max-width: 600px; margin: 0 auto 2rem;">
                A gamified monitoring app that makes student cleaning tasks fun and engaging 
                while reducing teacher workload through automated tracking and analytics.
            </p>
            <div class="cta-buttons">
                <a href="/auth/login.php" class="btn btn-success">Login</a>
                <a href="/auth/register.php" class="btn" style="background: white; color: var(--primary-color);">Register</a>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="container">
        <div class="stats-grid" style="margin-top: 3rem;">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <span class="stat-value"><?php echo $total_students; ?></span>
                <span class="stat-label">Active Students</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <span class="stat-value"><?php echo $total_completions; ?></span>
                <span class="stat-label">Tasks Completed</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <span class="stat-value"><?php echo number_format($total_points); ?></span>
                <span class="stat-label">Points Earned</span>
            </div>
        </div>

        <!-- Features Section -->
        <h2 style="text-align: center; margin-top: 4rem; margin-bottom: 2rem; font-size: 2rem;">
            Why Choose Sweepstreak?
        </h2>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🎮</div>
                <div class="feature-title">Gamification</div>
                <p style="color: var(--text-secondary);">
                    Points, streaks, badges, and leaderboards make cleaning tasks engaging and fun for students.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <div class="feature-title">Analytics Dashboard</div>
                <p style="color: var(--text-secondary);">
                    Track compliance rates, student engagement, and task completion with detailed analytics.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">Reduced Workload</div>
                <p style="color: var(--text-secondary);">
                    Automated task assignment, submission tracking, and performance monitoring save teacher time.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📸</div>
                <div class="feature-title">Photo Evidence</div>
                <p style="color: var(--text-secondary);">
                    Students can submit photo proof of completed tasks for easy verification.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <div class="feature-title">Achievement System</div>
                <p style="color: var(--text-secondary);">
                    Unlock badges and climb the leaderboard to motivate consistent participation.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <div class="feature-title">Easy to Use</div>
                <p style="color: var(--text-secondary);">
                    Simple, intuitive interface designed for both students and teachers.
                </p>
            </div>
        </div>

        <!-- How It Works -->
        <div class="card" style="margin: 4rem 0;">
            <div class="card-header" style="text-align: center; font-size: 1.5rem;">
                How It Works
            </div>
            
            <div class="grid grid-3">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">1️⃣</div>
                    <h3 style="margin-bottom: 0.5rem;">Teachers Assign</h3>
                    <p style="color: var(--text-secondary);">
                        Create cleaning tasks and assign them to students with due dates.
                    </p>
                </div>
                
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">2️⃣</div>
                    <h3 style="margin-bottom: 0.5rem;">Students Complete</h3>
                    <p style="color: var(--text-secondary);">
                        Students complete tasks and submit with optional photo evidence.
                    </p>
                </div>
                
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">3️⃣</div>
                    <h3 style="margin-bottom: 0.5rem;">Earn Rewards</h3>
                    <p style="color: var(--text-secondary);">
                        Approved tasks earn points, build streaks, and unlock badges!
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div style="text-align: center; padding: 3rem 0;">
            <h2 style="margin-bottom: 1rem;">Ready to Get Started?</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                Join Sweepstreak today and transform your cleaning compliance program!
            </p>
            <div class="cta-buttons">
                <a href="/auth/register.php" class="btn btn-primary">Create Account</a>
                <a href="/auth/login.php" class="btn btn-secondary">Login</a>
            </div>
        </div>
    </div>

    <footer style="background: var(--dark-bg); color: white; padding: 2rem 0; margin-top: 4rem; text-align: center;">
        <div class="container">
            <p>&copy; 2024 Sweepstreak. All rights reserved.</p>
            <p style="opacity: 0.7; margin-top: 0.5rem;">
                Making cleaning compliance fun and effective through gamification.
            </p>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
</body>
</html>
