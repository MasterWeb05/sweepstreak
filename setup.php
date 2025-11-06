<?php
/**
 * Sweepstreak Setup Script
 * This script helps set up the database for the application
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sweepstreak';

echo "=== Sweepstreak Database Setup ===\n\n";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to MySQL server\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$db_name' created/verified\n";
    
    // Connect to the database
    $pdo->exec("USE $db_name");
    echo "✓ Connected to database '$db_name'\n";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Database schema created successfully\n";
    echo "✓ Sample data inserted\n\n";
    
    echo "=== Setup Complete! ===\n\n";
    echo "Default Accounts:\n";
    echo "----------------\n";
    echo "Teacher Account:\n";
    echo "  Username: teacher1\n";
    echo "  Password: teacher123\n\n";
    echo "Student Accounts:\n";
    echo "  Username: student1, student2, student3\n";
    echo "  Password: student123\n\n";
    echo "You can now access the application at: http://localhost/\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
