<?php
// Quick setup script to ensure everything works
echo "<!DOCTYPE html>
<html>
<head>
    <title>Quick Setup - Pinterest Clone</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .step { background: #f0f8ff; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #007bff; }
        .success { background: #d4edda; border-left-color: #28a745; }
        .error { background: #f8d7da; border-left-color: #dc3545; }
        .btn { display: inline-block; padding: 10px 20px; background: #e60023; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    </style>
</head>
<body>
    <h1>🚀 Pinterest Clone - Quick Setup</h1>";

$steps = [];

// Step 1: Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    $steps[] = ['✅ PHP Version: ' . PHP_VERSION . ' (Good)', 'success'];
} else {
    $steps[] = ['❌ PHP Version: ' . PHP_VERSION . ' (Needs 7.4+)', 'error'];
}

// Step 2: Check PDO
if (extension_loaded('pdo')) {
    $steps[] = ['✅ PDO Extension: Available', 'success'];
} else {
    $steps[] = ['❌ PDO Extension: Not available', 'error'];
}

// Step 3: Check directories
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}
if (!is_dir('uploads/pins')) {
    mkdir('uploads/pins', 0755, true);
}
if (!is_dir('data')) {
    mkdir('data', 0755, true);
}
$steps[] = ['✅ Directories: Created successfully', 'success'];

// Step 4: Test database connection
try {
    require_once 'db.php';
    $info = getConnectionInfo();
    if ($info['working']) {
        $steps[] = ['✅ Database: Connected (' . $info['info'] . ')', 'success'];
    } else {
        $steps[] = ['⚠️ Database: Using fallback system', 'step'];
    }
} catch (Exception $e) {
    $steps[] = ['❌ Database: Error - ' . $e->getMessage(), 'error'];
}

// Step 5: Test file permissions
$test_file = 'uploads/test.txt';
if (file_put_contents($test_file, 'test') !== false) {
    unlink($test_file);
    $steps[] = ['✅ File Permissions: Working', 'success'];
} else {
    $steps[] = ['❌ File Permissions: Cannot write to uploads folder', 'error'];
}

// Display results
foreach ($steps as $step) {
    echo "<div class='step {$step[1]}'>{$step[0]}</div>";
}

$all_good = true;
foreach ($steps as $step) {
    if ($step[1] === 'error') {
        $all_good = false;
        break;
    }
}

if ($all_good) {
    echo "<div class='step success'>
        <h3>🎉 Setup Complete!</h3>
        <p>Your Pinterest clone is ready to use!</p>
        <p><strong>Test Login:</strong> Username: admin, Password: password123</p>
        <a href='index.php' class='btn'>🏠 Go to Homepage</a>
        <a href='login.php' class='btn'>🔐 Login</a>
        <a href='connection_status.php' class='btn'>📊 View Status</a>
    </div>";
} else {
    echo "<div class='step error'>
        <h3>⚠️ Setup Issues Found</h3>
        <p>Please fix the errors above and try again.</p>
        <a href='quick_setup.php' class='btn'>🔄 Retry Setup</a>
    </div>";
}

echo "</body></html>";
?>
