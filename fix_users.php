<?php
// Emergency user fix script
session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Users - Pinterest Clone</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .step { background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #007bff; }
        .success { background: #d4edda; border-left-color: #28a745; }
        .error { background: #f8d7da; border-left-color: #dc3545; }
        .btn { display: inline-block; padding: 12px 24px; background: #e60023; color: white; text-decoration: none; border-radius: 8px; margin: 5px; font-weight: 600; }
        .btn:hover { background: #d50020; }
        .logo { text-align: center; font-size: 36px; font-weight: bold; color: #e60023; margin-bottom: 20px; }
        .user-card { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='logo'>Pinterest Clone</div>
        <h1>🔧 User Management Fix</h1>";

// Create data directory
if (!is_dir('data')) {
    mkdir('data', 0755, true);
    echo "<div class='step success'>✅ Created data directory</div>";
}

// Create users file with proper structure
$users_file = 'data/users.json';
$users_data = [];

// Check if users file exists and load it
if (file_exists($users_file)) {
    $existing_data = json_decode(file_get_contents($users_file), true);
    if (is_array($existing_data)) {
        $users_data = $existing_data;
        echo "<div class='step'>📁 Loaded existing users file with " . count($users_data) . " users</div>";
    }
}

// Create default admin user if not exists
$admin_exists = false;
foreach ($users_data as $user) {
    if ($user['username'] === 'admin' || $user['email'] === 'admin@pinterest.com') {
        $admin_exists = true;
        break;
    }
}

if (!$admin_exists) {
    $admin_user = [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@pinterest.com',
        'full_name' => 'Pinterest Admin',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'profile_image' => 'https://via.placeholder.com/150x150/e60023/ffffff?text=Admin',
        'bio' => 'Welcome to Pinterest Clone! This is the admin account.',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $users_data[] = $admin_user;
    echo "<div class='step success'>✅ Created admin user</div>";
}

// Create demo user
$demo_exists = false;
foreach ($users_data as $user) {
    if ($user['username'] === 'demo' || $user['email'] === 'demo@pinterest.com') {
        $demo_exists = true;
        break;
    }
}

if (!$demo_exists) {
    $demo_user = [
        'id' => 2,
        'username' => 'demo',
        'email' => 'demo@pinterest.com',
        'full_name' => 'Demo User',
        'password' => password_hash('demo123', PASSWORD_DEFAULT),
        'profile_image' => 'https://via.placeholder.com/150x150/28a745/ffffff?text=Demo',
        'bio' => 'This is a demo account for testing purposes.',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $users_data[] = $demo_user;
    echo "<div class='step success'>✅ Created demo user</div>";
}

// Save users file
if (file_put_contents($users_file, json_encode($users_data, JSON_PRETTY_PRINT))) {
    echo "<div class='step success'>✅ Saved users file successfully</div>";
} else {
    echo "<div class='step error'>❌ Failed to save users file</div>";
}

// Create sample pins
$pins_file = 'data/pins.json';
$pins_data = [];

if (file_exists($pins_file)) {
    $existing_pins = json_decode(file_get_contents($pins_file), true);
    if (is_array($existing_pins)) {
        $pins_data = $existing_pins;
    }
}

if (empty($pins_data)) {
    $sample_pins = [
        [
            'id' => 1,
            'user_id' => 1,
            'title' => 'Welcome to Pinterest Clone',
            'description' => 'This is your first pin! Start exploring and creating amazing content.',
            'image_url' => 'https://picsum.photos/400/600?random=1',
            'category_id' => 1,
            'views' => 150,
            'likes' => 25,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'user_id' => 1,
            'title' => 'Beautiful Nature',
            'description' => 'Amazing landscape photography from around the world.',
            'image_url' => 'https://picsum.photos/400/500?random=2',
            'category_id' => 2,
            'views' => 89,
            'likes' => 12,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 3,
            'user_id' => 2,
            'title' => 'Modern Design',
            'description' => 'Clean and minimalist design inspiration.',
            'image_url' => 'https://picsum.photos/400/700?random=3',
            'category_id' => 3,
            'views' => 234,
            'likes' => 45,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    if (file_put_contents($pins_file, json_encode($sample_pins, JSON_PRETTY_PRINT))) {
        echo "<div class='step success'>✅ Created sample pins</div>";
    }
}

// Create categories
$categories_file = 'data/categories.json';
$categories_data = [
    ['id' => 1, 'name' => 'Fashion', 'slug' => 'fashion'],
    ['id' => 2, 'name' => 'Art', 'slug' => 'art'],
    ['id' => 3, 'name' => 'Food', 'slug' => 'food'],
    ['id' => 4, 'name' => 'Travel', 'slug' => 'travel'],
    ['id' => 5, 'name' => 'DIY', 'slug' => 'diy'],
    ['id' => 6, 'name' => 'Home Decor', 'slug' => 'home-decor'],
    ['id' => 7, 'name' => 'Photography', 'slug' => 'photography'],
    ['id' => 8, 'name' => 'Nature', 'slug' => 'nature']
];

if (file_put_contents($categories_file, json_encode($categories_data, JSON_PRETTY_PRINT))) {
    echo "<div class='step success'>✅ Created categories</div>";
}

// Display current users
echo "<div class='step'>";
echo "<h3>👥 Available Users:</h3>";
foreach ($users_data as $user) {
    echo "<div class='user-card'>";
    echo "<strong>{$user['full_name']}</strong> (@{$user['username']})<br>";
    echo "Email: {$user['email']}<br>";
    echo "ID: {$user['id']}<br>";
    if ($user['username'] === 'admin') {
        echo "<small>Password: password123</small>";
    } elseif ($user['username'] === 'demo') {
        echo "<small>Password: demo123</small>";
    }
    echo "</div>";
}
echo "</div>";

// Fix current session if needed
if (isset($_SESSION['user_id'])) {
    $session_user_found = false;
    foreach ($users_data as $user) {
        if ($user['id'] == $_SESSION['user_id']) {
            $session_user_found = true;
            $_SESSION['username'] = $user['username'];
            echo "<div class='step success'>✅ Fixed current session for user: {$user['username']}</div>";
            break;
        }
    }
    
    if (!$session_user_found) {
        echo "<div class='step error'>⚠️ Current session user not found. Please login again.</div>";
        session_destroy();
    }
} else {
    echo "<div class='step'>ℹ️ No active session. Please login to continue.</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>
    <h3>🎉 User Management Fixed!</h3>
    <p>All users have been created and the system is ready to use.</p>
    <a href='login.php' class='btn'>🔐 Login</a>
    <a href='profile.php' class='btn'>👤 View Profile</a>
    <a href='index.php' class='btn'>🏠 Homepage</a>
</div>";

echo "</div></body></html>";
?>
