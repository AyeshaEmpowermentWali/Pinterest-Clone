<?php
// Debug script to check profile issues
echo "<!DOCTYPE html>
<html>
<head>
    <title>Profile Debug - Pinterest Clone</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .debug-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .btn { display: inline-block; padding: 10px 20px; background: #e60023; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Profile Debug Information</h1>";

// Check session
session_start();
echo "<div class='debug-section'>";
echo "<h3>Session Information:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>✅ User logged in: ID = {$_SESSION['user_id']}, Username = " . ($_SESSION['username'] ?? 'N/A') . "</div>";
} else {
    echo "<div class='warning'>⚠️ No user logged in</div>";
}
echo "</div>";

// Check database connection
echo "<div class='debug-section'>";
echo "<h3>Database Connection:</h3>";
try {
    require_once 'db.php';
    if ($pdo) {
        echo "<div class='success'>✅ Database connected successfully</div>";
        
        // Check users table
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch()['count'];
            echo "<div class='success'>✅ Users table accessible: $count users found</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Users table error: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ Using fallback storage system</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Check file storage
echo "<div class='debug-section'>";
echo "<h3>File Storage:</h3>";
$users_file = 'data/users.json';
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    if ($users_data) {
        echo "<div class='success'>✅ Users file exists: " . count($users_data) . " users found</div>";
        foreach ($users_data as $user) {
            echo "<div>- {$user['username']} ({$user['email']})</div>";
        }
    } else {
        echo "<div class='error'>❌ Users file exists but is invalid JSON</div>";
    }
} else {
    echo "<div class='warning'>⚠️ Users file not found</div>";
}
echo "</div>";

// Check URL parameters
echo "<div class='debug-section'>";
echo "<h3>URL Parameters:</h3>";
if (isset($_GET['user'])) {
    echo "<div class='success'>✅ User parameter: " . htmlspecialchars($_GET['user']) . "</div>";
} else {
    echo "<div class='warning'>⚠️ No user parameter in URL</div>";
}
echo "</div>";

// Check file permissions
echo "<div class='debug-section'>";
echo "<h3>File Permissions:</h3>";
$dirs_to_check = ['data', 'uploads', 'uploads/pins'];
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<div class='success'>✅ Directory $dir is writable</div>";
        } else {
            echo "<div class='error'>❌ Directory $dir is not writable</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ Directory $dir does not exist</div>";
    }
}
echo "</div>";

// PHP Information
echo "<div class='debug-section'>";
echo "<h3>PHP Information:</h3>";
echo "<div>PHP Version: " . PHP_VERSION . "</div>";
echo "<div>Error Reporting: " . (error_reporting() ? 'Enabled' : 'Disabled') . "</div>";
echo "<div>Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "</div>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>
    <a href='profile.php' class='btn'>🔄 Try Profile Again</a>
    <a href='index.php' class='btn'>🏠 Go Home</a>
    <a href='emergency_setup.php' class='btn'>🛠️ Emergency Setup</a>
</div>";

echo "</body></html>";
?>
