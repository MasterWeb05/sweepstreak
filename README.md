# 🧹 Sweepstreak

**A Gamified Monitoring App for Student Cleaning Compliance and Teacher Workload Management**

Sweepstreak transforms the mundane task of cleaning compliance into an engaging, game-like experience for students while significantly reducing teacher workload through automated tracking, analytics, and streamlined task management.

## 🌟 Features

### For Students
- **📋 Task Dashboard** - View assigned cleaning tasks with due dates and locations
- **📸 Photo Submissions** - Submit completed tasks with optional photo evidence
- **🔥 Streak System** - Build daily streaks for consecutive task completions
- **⭐ Points & Rewards** - Earn points with streak multipliers (10% bonus every 3 days)
- **🏆 Badges** - Unlock achievement badges for milestones
- **📊 Leaderboard** - Compete with peers and climb the rankings
- **📈 Progress Tracking** - Monitor personal stats and achievements

### For Teachers
- **📝 Task Management** - Create and assign cleaning tasks to students
- **✅ Quick Review** - Approve or reject submissions with feedback
- **📊 Analytics Dashboard** - Track compliance rates and student engagement
- **👥 Student Overview** - Monitor individual and class performance
- **📈 Compliance Metrics** - View weekly trends and completion rates
- **💡 Insights** - Get automated recommendations based on data
- **⚡ Reduced Workload** - Automated tracking and notifications

### Gamification Elements
- **Points System** - Base points + streak multipliers
- **Daily Streaks** - Consecutive day completion tracking
- **Achievement Badges** - 8 different badges to unlock
- **Leaderboard Rankings** - Real-time competitive standings
- **Progress Visualization** - Stats cards and charts

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Architecture**: MVC-inspired structure with PDO for database operations

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)

## 🚀 Installation

### 1. Clone or Download

```bash
git clone <repository-url>
cd sweepstreak
```

### 2. Configure Database

Edit `config/database.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sweepstreak');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Run Setup Script

```bash
php setup.php
```

This will:
- Create the database
- Set up all tables
- Insert sample data
- Create default user accounts

### 4. Configure Web Server

#### Apache (.htaccess)

Create `.htaccess` in the root directory:

```apache
RewriteEngine On
RewriteBase /

# Redirect to index.php if file doesn't exist
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

#### Nginx

Add to your server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### 5. Set Permissions

```bash
chmod 755 uploads/
chmod 644 config/database.php
```

### 6. Access the Application

Open your browser and navigate to:
```
http://localhost/sweepstreak/
```

## 👤 Default Accounts

### Teacher Account
- **Username**: `teacher1`
- **Password**: `teacher123`
- **Email**: teacher@school.com

### Student Accounts
- **Username**: `student1`, `student2`, `student3`
- **Password**: `student123`
- **Emails**: student1@school.com, student2@school.com, student3@school.com

## 📁 Project Structure

```
sweepstreak/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── main.js            # JavaScript utilities
├── auth/
│   ├── login.php              # Login page
│   ├── register.php           # Registration page
│   └── logout.php             # Logout handler
├── config/
│   └── database.php           # Database configuration
├── database/
│   └── schema.sql             # Database schema
├── includes/
│   └── functions.php          # Helper functions
├── student/
│   ├── dashboard.php          # Student dashboard
│   ├── tasks.php              # Task management
│   └── leaderboard.php        # Leaderboard view
├── teacher/
│   ├── dashboard.php          # Teacher dashboard
│   ├── manage_tasks.php       # Task creation/assignment
│   ├── review_submissions.php # Review student submissions
│   └── analytics.php          # Analytics dashboard
├── uploads/                   # Photo uploads directory
├── index.php                  # Landing page
├── setup.php                  # Database setup script
└── README.md                  # This file
```

## 🎮 How to Use

### For Students

1. **Login** with your student credentials
2. **View Tasks** on your dashboard
3. **Complete Tasks** in real life
4. **Submit** with optional photo evidence
5. **Earn Points** and build your streak
6. **Unlock Badges** by reaching milestones
7. **Compete** on the leaderboard

### For Teachers

1. **Login** with your teacher credentials
2. **Create Tasks** with descriptions and point values
3. **Assign Tasks** to students with due dates
4. **Review Submissions** - approve or reject with feedback
5. **Monitor Analytics** - track compliance and engagement
6. **View Insights** - get automated recommendations

## 🏆 Badge System

| Badge | Requirement | Icon |
|-------|------------|------|
| First Step | Complete 1 task | 🌟 |
| Clean Streak | 3-day streak | 🔥 |
| Week Warrior | 7-day streak | ⚡ |
| Point Master | 100 total points | 💯 |
| Dedication | 14-day streak | 🏆 |
| Elite Cleaner | 500 total points | 👑 |
| Consistency King | 30-day streak | 💎 |
| Task Champion | 50 completed tasks | 🎯 |

## 📊 Points System

- **Base Points**: Set per task (default: 10)
- **Streak Multiplier**: +10% for every 3 consecutive days
- **Example**: 
  - Day 1-2: 10 points
  - Day 3-5: 11 points (10% bonus)
  - Day 6-8: 12 points (20% bonus)

## 🔒 Security Features

- Password hashing with `password_hash()`
- Prepared statements (PDO) to prevent SQL injection
- Input sanitization and validation
- Session-based authentication
- Role-based access control
- CSRF protection ready

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check credentials in `config/database.php`
- Ensure database exists

### Upload Directory Error
- Create `uploads/` directory
- Set permissions: `chmod 755 uploads/`

### Session Issues
- Check PHP session configuration
- Ensure cookies are enabled in browser

### Rewrite Rules Not Working
- Enable mod_rewrite in Apache
- Check .htaccess file exists
- Verify AllowOverride is set

## 📈 Future Enhancements

- [ ] Email notifications for task assignments
- [ ] Mobile app (iOS/Android)
- [ ] Real-time notifications with WebSockets
- [ ] Advanced analytics with charts
- [ ] Team-based competitions
- [ ] Custom badge creation
- [ ] Export reports to PDF/Excel
- [ ] Multi-language support
- [ ] Dark mode theme

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open source and available under the MIT License.

## 👨‍💻 Support

For issues, questions, or suggestions:
- Create an issue in the repository
- Contact: support@sweepstreak.com

## 🎯 Research Context

This application was developed to study **"The Effectiveness of Sweepstreak: A Gamified Monitoring App on Student Cleaning Compliance and Teacher Workload"**

### Research Objectives
1. Measure impact on student cleaning compliance rates
2. Assess reduction in teacher workload
3. Evaluate effectiveness of gamification elements
4. Analyze student engagement and motivation
5. Compare traditional vs. gamified monitoring approaches

### Key Metrics Tracked
- Task completion rates
- Student engagement levels
- Teacher time spent on monitoring
- Streak maintenance rates
- Badge achievement rates
- Overall compliance trends

---

**Made with ❤️ for better cleaning compliance through gamification**
