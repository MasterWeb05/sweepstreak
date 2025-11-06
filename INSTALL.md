# 🚀 Sweepstreak Installation Guide

This guide will walk you through setting up Sweepstreak on your local machine or server.

## Prerequisites

Before you begin, ensure you have:

- ✅ PHP 7.4 or higher installed
- ✅ MySQL 5.7 or higher installed
- ✅ Apache or Nginx web server
- ✅ Basic knowledge of command line

## Quick Start (5 Minutes)

### Step 1: Download Files

Download or clone the Sweepstreak files to your web server directory:

```bash
# For XAMPP (Windows)
C:\xampp\htdocs\sweepstreak\

# For MAMP (Mac)
/Applications/MAMP/htdocs/sweepstreak/

# For Linux
/var/www/html/sweepstreak/
```

### Step 2: Configure Database

1. Open `config/database.php`
2. Update the database credentials:

```php
define('DB_HOST', 'localhost');      // Usually 'localhost'
define('DB_NAME', 'sweepstreak');    // Database name
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');               // Your MySQL password
```

### Step 3: Run Setup

Open your terminal/command prompt and run:

```bash
cd /path/to/sweepstreak
php setup.php
```

You should see:
```
=== Sweepstreak Database Setup ===

✓ Connected to MySQL server
✓ Database 'sweepstreak' created/verified
✓ Connected to database 'sweepstreak'
✓ Database schema created successfully
✓ Sample data inserted

=== Setup Complete! ===
```

### Step 4: Create Uploads Directory

```bash
mkdir uploads
chmod 755 uploads
```

### Step 5: Access the Application

Open your web browser and go to:
```
http://localhost/sweepstreak/
```

## Default Login Credentials

### Teacher Account
- **Username**: `teacher1`
- **Password**: `teacher123`

### Student Account
- **Username**: `student1`
- **Password**: `student123`

## Detailed Installation Steps

### For XAMPP (Windows)

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install and start Apache and MySQL

2. **Place Files**
   - Copy sweepstreak folder to `C:\xampp\htdocs\`

3. **Create Database**
   - Open http://localhost/phpmyadmin
   - Or run `php setup.php` from command prompt

4. **Configure**
   - Edit `config/database.php` if needed
   - Default XAMPP credentials: user=`root`, password=``

5. **Access**
   - Open http://localhost/sweepstreak/

### For MAMP (Mac)

1. **Install MAMP**
   - Download from https://www.mamp.info/
   - Install and start servers

2. **Place Files**
   - Copy sweepstreak folder to `/Applications/MAMP/htdocs/`

3. **Create Database**
   - Open http://localhost:8888/phpMyAdmin
   - Or run `php setup.php` from terminal

4. **Configure**
   - Edit `config/database.php`
   - Default MAMP credentials: user=`root`, password=`root`

5. **Access**
   - Open http://localhost:8888/sweepstreak/

### For Linux (Ubuntu/Debian)

1. **Install LAMP Stack**
```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql libapache2-mod-php
```

2. **Enable mod_rewrite**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

3. **Place Files**
```bash
sudo cp -r sweepstreak /var/www/html/
sudo chown -R www-data:www-data /var/www/html/sweepstreak
```

4. **Create Database**
```bash
cd /var/www/html/sweepstreak
php setup.php
```

5. **Set Permissions**
```bash
sudo mkdir /var/www/html/sweepstreak/uploads
sudo chmod 755 /var/www/html/sweepstreak/uploads
sudo chown www-data:www-data /var/www/html/sweepstreak/uploads
```

6. **Configure Apache**

Create `/etc/apache2/sites-available/sweepstreak.conf`:

```apache
<VirtualHost *:80>
    ServerName sweepstreak.local
    DocumentRoot /var/www/html/sweepstreak
    
    <Directory /var/www/html/sweepstreak>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sweepstreak_error.log
    CustomLog ${APACHE_LOG_DIR}/sweepstreak_access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite sweepstreak
sudo systemctl reload apache2
```

7. **Access**
   - Open http://localhost/sweepstreak/

## Manual Database Setup

If the automatic setup doesn't work, you can set up the database manually:

1. **Create Database**
```sql
CREATE DATABASE sweepstreak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Import Schema**
```bash
mysql -u root -p sweepstreak < database/schema.sql
```

Or use phpMyAdmin:
- Open phpMyAdmin
- Create database `sweepstreak`
- Import `database/schema.sql`

## Troubleshooting

### "Database connection failed"

**Solution:**
1. Check MySQL is running
2. Verify credentials in `config/database.php`
3. Test connection:
```bash
mysql -u root -p
```

### "Permission denied" for uploads

**Solution:**
```bash
chmod 755 uploads/
# On Linux:
sudo chown www-data:www-data uploads/
```

### "Page not found" or 404 errors

**Solution:**
1. Check `.htaccess` file exists
2. Enable mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```
3. Verify AllowOverride in Apache config

### "Session not working"

**Solution:**
1. Check PHP session directory is writable
2. Verify session settings in php.ini
3. Clear browser cookies

### "Upload failed"

**Solution:**
1. Check `uploads/` directory exists
2. Verify permissions (755)
3. Check PHP upload settings:
```ini
upload_max_filesize = 5M
post_max_size = 6M
```

## Security Recommendations

### For Production Deployment

1. **Change Default Passwords**
```sql
UPDATE users SET password = '$2y$10$...' WHERE username = 'teacher1';
```

2. **Update Database Credentials**
   - Use strong password
   - Create dedicated MySQL user

3. **Disable Error Display**
   - Edit `.htaccess`: `php_flag display_errors Off`

4. **Enable HTTPS**
   - Install SSL certificate
   - Force HTTPS in `.htaccess`

5. **Secure Uploads Directory**
   - Add `.htaccess` in uploads/:
```apache
Options -Indexes
<FilesMatch "\.php$">
    deny from all
</FilesMatch>
```

6. **Regular Backups**
```bash
mysqldump -u root -p sweepstreak > backup.sql
```

## Testing the Installation

1. **Test Login**
   - Go to http://localhost/sweepstreak/auth/login.php
   - Login with teacher1/teacher123

2. **Test Task Creation**
   - Create a new task
   - Assign to student

3. **Test Student Flow**
   - Logout and login as student1/student123
   - View assigned task
   - Submit task

4. **Test Review**
   - Login as teacher
   - Review submission
   - Approve task

5. **Verify Points**
   - Check student dashboard
   - Verify points awarded
   - Check leaderboard

## Next Steps

After successful installation:

1. 📚 Read the [README.md](README.md) for full documentation
2. 👤 Create your own teacher and student accounts
3. 🗑️ Delete default demo accounts (optional)
4. 🎨 Customize the application (colors, branding)
5. 📊 Start using the analytics dashboard

## Getting Help

If you encounter issues:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review PHP error logs
3. Check MySQL error logs
4. Verify all prerequisites are met

## System Requirements

### Minimum
- PHP 7.4
- MySQL 5.7
- 256MB RAM
- 100MB disk space

### Recommended
- PHP 8.0+
- MySQL 8.0+
- 512MB RAM
- 500MB disk space

---

**Installation complete! 🎉**

You're now ready to use Sweepstreak to improve cleaning compliance through gamification!
