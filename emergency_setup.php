<?php
// Emergency setup script that works without any database
echo "<!DOCTYPE html>
<html>
<head>
    <title>Emergency Setup - Pinterest Clone</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 20px auto; 
            padding: 20px; 
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .step { 
            background: #e7f3ff; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 8px; 
            border-left: 4px solid #007bff; 
        }
        .success { 
            background: #d4edda; 
            border-left-color: #28a745; 
        }
        .error { 
            background: #f8d7da; 
            border-left-color: #dc3545; 
        }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            background: #e60023; 
            color: white; 
            text-decoration: none; 
            border-radius: 8px; 
            margin: 5px; 
            font-weight: 600;
        }
        .btn:hover { background: #d50020; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .logo {
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            color: #e60023;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='logo'>Pinterest Clone</div>
        <h1>🚨 Emergency Setup</h1>
        <p>This script will create a working Pinterest clone without any database dependencies.</p>";

$steps_completed = 0;
$total_steps = 5;

// Step 1: Create directories
echo "<div class='step'>Step 1: Creating directories...</div>";
$dirs = ['data', 'uploads', 'uploads/pins'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<div class='success'>✅ Created directory: $dir</div>";
        } else {
            echo "<div class='error'>❌ Failed to create directory: $dir</div>";
        }
    } else {
        echo "<div class='success'>✅ Directory exists: $dir</div>";
    }
}
$steps_completed++;

// Step 2: Create users file
echo "<div class='step'>Step 2: Creating user storage...</div>";
$users_file = 'data/users.json';
$default_users = [
    [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@pinterest.com',
        'full_name' => 'Pinterest Admin',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'profile_image' => 'https://via.placeholder.com/150x150/e60023/ffffff?text=Admin',
        'bio' => 'Welcome to Pinterest Clone!',
        'created_at' => date('Y-m-d H:i:s')
    ]
];

if (file_put_contents($users_file, json_encode($default_users, JSON_PRETTY_PRINT))) {
    echo "<div class='success'>✅ Created user storage with admin account</div>";
    $steps_completed++;
} else {
    echo "<div class='error'>❌ Failed to create user storage</div>";
}

// Step 3: Create pins file
echo "<div class='step'>Step 3: Creating pins storage...</div>";
$pins_file = 'data/pins.json';
$default_pins = [
    [
        'id' => 1,
        'user_id' => 1,
        'title' => 'Welcome to Pinterest Clone',
        'description' => 'This is a sample pin to get you started!',
        'image_url' => 'https://picsum.photos/400/600?random=1',
        'category_id' => 1,
        'views' => 100,
        'likes' => 10,
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'user_id' => 1,
        'title' => 'Beautiful Landscape',
        'description' => 'Amazing nature photography',
        'image_url' => 'https://picsum.photos/400/500?random=2',
        'category_id' => 2,
        'views' => 85,
        'likes' => 15,
        'created_at' => date('Y-m-d H:i:s')
    ]
];

if (file_put_contents($pins_file, json_encode($default_pins, JSON_PRETTY_PRINT))) {
    echo "<div class='success'>✅ Created pins storage with sample pins</div>";
    $steps_completed++;
} else {
    echo "<div class='error'>❌ Failed to create pins storage</div>";
}

// Step 4: Create categories file
echo "<div class='step'>Step 4: Creating categories storage...</div>";
$categories_file = 'data/categories.json';
$default_categories = [
    ['id' => 1, 'name' => 'Fashion', 'slug' => 'fashion'],
    ['id' => 2, 'name' => 'Art', 'slug' => 'art'],
    ['id' => 3, 'name' => 'Food', 'slug' => 'food'],
    ['id' => 4, 'name' => 'Travel', 'slug' => 'travel'],
    ['id' => 5, 'name' => 'DIY', 'slug' => 'diy']
];

if (file_put_contents($categories_file, json_encode($default_categories, JSON_PRETTY_PRINT))) {
    echo "<div class='success'>✅ Created categories storage</div>";
    $steps_completed++;
} else {
    echo "<div class='error'>❌ Failed to create categories storage</div>";
}

// Step 5: Test file permissions
echo "<div class='step'>Step 5: Testing file permissions...</div>";
$test_file = 'uploads/test.txt';
if (file_put_contents($test_file, 'test') !== false) {
    unlink($test_file);
    echo "<div class='success'>✅ File permissions working</div>";
    $steps_completed++;
} else {
    echo "<div class='error'>❌ File permission issues detected</div>";
}

// Summary
echo "<div class='step'>";
echo "<h3>Setup Summary</h3>";
echo "<p>Completed: $steps_completed / $total_steps steps</p>";

if ($steps_completed === $total_steps) {
    echo "<div class='success'>";
    echo "<h3>🎉 Emergency Setup Complete!</h3>";
    echo "<p>Your Pinterest clone is now ready to use with file-based storage.</p>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<p>Email: admin@pinterest.com<br>Password: password123</p>";
    echo "<p>The system will work without any database. All data is stored in JSON files.</p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='index.php' class='btn'>🏠 Go to Homepage</a>";
    echo "<a href='login.php' class='btn btn-secondary'>🔐 Login</a>";
    echo "<a href='signup.php' class='btn btn-secondary'>📝 Sign Up</a>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Setup Incomplete</h3>";
    echo "<p>Some steps failed. Please check file permissions and try again.</p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='emergency_setup.php' class='btn'>🔄 Retry Setup</a>";
    echo "</div>";
}

echo "</div>";
echo "</div></body></html>";
?>
