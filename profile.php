<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start with safe error handling
try {
    require_once 'db.php';
} catch (Exception $e) {
    session_start();
    $pdo = null;
}

// Initialize variables
$username = '';
$profile_user = null;
$is_own_profile = false;
$user_pins = [];
$user_boards = [];
$pins_count = 0;
$boards_count = 0;
$followers_count = 0;
$following_count = 0;

// Function to get user data from file storage
function getUserFromFile($username) {
    $users_file = 'data/users.json';
    if (file_exists($users_file)) {
        $users_data = json_decode(file_get_contents($users_file), true) ?: [];
        foreach ($users_data as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
    }
    return null;
}

// Function to get user by ID from file storage
function getUserByIdFromFile($user_id) {
    $users_file = 'data/users.json';
    if (file_exists($users_file)) {
        $users_data = json_decode(file_get_contents($users_file), true) ?: [];
        foreach ($users_data as $user) {
            if ($user['id'] == $user_id) {
                return $user;
            }
        }
    }
    return null;
}

// Function to get pins from file storage
function getPinsFromFile($user_id) {
    $pins_file = 'data/pins.json';
    if (file_exists($pins_file)) {
        $pins_data = json_decode(file_get_contents($pins_file), true) ?: [];
        $user_pins = [];
        foreach ($pins_data as $pin) {
            if ($pin['user_id'] == $user_id) {
                $user_pins[] = $pin;
            }
        }
        return $user_pins;
    }
    return [];
}

// Get username from URL or session
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $username = trim($_GET['user']);
} elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Get username from session user_id
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            if ($result) {
                $username = $result['username'];
            }
        } catch (Exception $e) {
            // Try file storage
            $user = getUserByIdFromFile($_SESSION['user_id']);
            if ($user) {
                $username = $user['username'];
            }
        }
    } else {
        // Use file storage
        $user = getUserByIdFromFile($_SESSION['user_id']);
        if ($user) {
            $username = $user['username'];
        }
    }
} elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $username = $_SESSION['username'];
}

// If still no username, redirect to login
if (empty($username)) {
    echo "<script>
        alert('Please login to view profiles');
        window.location.href = 'login.php';
    </script>";
    exit();
}

// Try to get user info from database first
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $profile_user = $stmt->fetch();
        
        if ($profile_user) {
            // Get user's pins
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as category_name 
                FROM pins p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.user_id = ? 
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$profile_user['id']]);
            $user_pins = $stmt->fetchAll();
            
            // Get user's boards
            $stmt = $pdo->prepare("
                SELECT b.*, COUNT(bp.pin_id) as pin_count 
                FROM boards b 
                LEFT JOIN board_pins bp ON b.id = bp.board_id 
                WHERE b.user_id = ? 
                GROUP BY b.id 
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$profile_user['id']]);
            $user_boards = $stmt->fetchAll();
            
            // Get stats
            $pins_count = count($user_pins);
            $boards_count = count($user_boards);
        }
    } catch (Exception $e) {
        // Database error, fall back to file storage
        $pdo = null;
    }
}

// Fallback to file storage if database failed or no user found
if (!$profile_user) {
    $profile_user = getUserFromFile($username);
    if ($profile_user) {
        $user_pins = getPinsFromFile($profile_user['id']);
        $pins_count = count($user_pins);
        $boards_count = 0; // File storage doesn't support boards yet
    }
}

// If still no user found, show error with helpful message
if (!$profile_user) {
    echo "<script>
        alert('User \"$username\" not found!\\n\\nAvailable users:\\n- admin (password: password123)\\n- demo (password: demo123)\\n\\nPlease run fix_users.php to create users.');
        window.location.href = 'fix_users.php';
    </script>";
    exit();
}

// Check if this is user's own profile
$is_own_profile = isLoggedIn() && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user['id'];

// Ensure required fields exist with safe defaults
$profile_user['profile_image'] = $profile_user['profile_image'] ?? 'https://via.placeholder.com/150x150/e60023/ffffff?text=' . strtoupper(substr($profile_user['username'], 0, 1));
$profile_user['bio'] = $profile_user['bio'] ?? '';
$profile_user['full_name'] = $profile_user['full_name'] ?? $profile_user['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['full_name']); ?> - Pinterest Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
            color: #333;
            line-height: 1.6;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            padding: 12px 16px;
        }

        .nav-container {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            gap: 16px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #e60023;
            text-decoration: none;
        }

        .nav-actions {
            margin-left: auto;
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #e60023;
            color: white;
        }

        .btn-primary:hover {
            background-color: #d50020;
        }

        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #e1e1e1;
        }

        /* Profile Section */
        .profile-section {
            margin-top: 80px;
            padding: 40px 20px;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            object-fit: cover;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .profile-username {
            font-size: 16px;
            color: #666;
            margin-bottom: 16px;
        }

        .profile-bio {
            font-size: 16px;
            color: #333;
            max-width: 600px;
            margin: 0 auto 24px;
            line-height: 1.5;
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-bottom: 24px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }

        .profile-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        /* System Status */
        .system-status {
            background: #fff3cd;
            color: #856404;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            text-align: center;
        }

        /* Content */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        .pins-grid {
            columns: 5;
            column-gap: 16px;
            margin: 0 auto;
        }

        .pin-card {
            break-inside: avoid;
            margin-bottom: 16px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .pin-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .pin-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .pin-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            opacity: 0;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pin-card:hover .pin-overlay {
            opacity: 1;
        }

        .save-btn {
            background: #e60023;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .pin-info {
            padding: 12px;
        }

        .pin-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 14px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .empty-text {
            font-size: 16px;
            margin-bottom: 24px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .pins-grid { columns: 4; }
        }

        @media (max-width: 900px) {
            .pins-grid { columns: 3; }
            .profile-stats { gap: 24px; }
        }

        @media (max-width: 600px) {
            .pins-grid { columns: 2; }
            .profile-stats { 
                flex-wrap: wrap;
                gap: 16px;
            }
            .profile-actions {
                flex-direction: column;
                align-items: center;
            }
        }

        @media (max-width: 400px) {
            .pins-grid { columns: 1; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">Pinterest</a>
            <div class="nav-actions">
                <a href="index.php" class="btn btn-secondary">Home</a>
                <?php if ($is_own_profile): ?>
                    <a href="create.php" class="btn btn-primary">Create</a>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                <?php elseif (isLoggedIn()): ?>
                    <a href="create.php" class="btn btn-primary">Create</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Profile Section -->
    <section class="profile-section">
        <?php if (!$pdo): ?>
            <div class="system-status">
                ⚠️ Using backup storage system (database unavailable)
            </div>
        <?php endif; ?>

        <img src="<?php echo htmlspecialchars($profile_user['profile_image']); ?>" 
             alt="<?php echo htmlspecialchars($profile_user['full_name']); ?>" 
             class="profile-avatar"
             onerror="this.src='https://via.placeholder.com/150x150/e60023/ffffff?text=<?php echo strtoupper(substr($profile_user['username'], 0, 1)); ?>'">
        
        <h1 class="profile-name"><?php echo htmlspecialchars($profile_user['full_name']); ?></h1>
        <p class="profile-username">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
        
        <?php if (!empty($profile_user['bio'])): ?>
            <p class="profile-bio"><?php echo htmlspecialchars($profile_user['bio']); ?></p>
        <?php endif; ?>

        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $pins_count; ?></div>
                <div class="stat-label">Pins</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $boards_count; ?></div>
                <div class="stat-label">Boards</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $followers_count; ?></div>
                <div class="stat-label">Followers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $following_count; ?></div>
                <div class="stat-label">Following</div>
            </div>
        </div>

        <div class="profile-actions">
            <?php if ($is_own_profile): ?>
                <button class="btn btn-secondary" onclick="editProfile()">Edit Profile</button>
            <?php else: ?>
                <button class="btn btn-primary" onclick="followUser(<?php echo $profile_user['id']; ?>)">Follow</button>
                <button class="btn btn-secondary" onclick="sendMessage()">Message</button>
            <?php endif; ?>
        </div>
    </section>

    <!-- Content -->
    <div class="content-container">
        <h2 style="text-align: center; margin-bottom: 32px; color: #333;">Pins</h2>
        
        <?php if (count($user_pins) > 0): ?>
            <div class="pins-grid">
                <?php foreach ($user_pins as $pin): ?>
                    <div class="pin-card" onclick="openPin(<?php echo $pin['id']; ?>)">
                        <img src="<?php echo htmlspecialchars($pin['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($pin['title']); ?>" 
                             class="pin-image"
                             onerror="this.src='https://via.placeholder.com/400x600/f1f1f1/666?text=Image+Not+Found'">
                        <div class="pin-overlay">
                            <button class="save-btn" onclick="event.stopPropagation(); savePin(<?php echo $pin['id']; ?>)">Save</button>
                        </div>
                        <div class="pin-info">
                            <div class="pin-title"><?php echo htmlspecialchars($pin['title']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📌</div>
                <h3 class="empty-title">No pins yet</h3>
                <p class="empty-text">
                    <?php echo $is_own_profile ? "Start creating pins to share your ideas!" : "This user hasn't created any pins yet."; ?>
                </p>
                <?php if ($is_own_profile): ?>
                    <a href="create.php" class="btn btn-primary">Create Pin</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Pin functions
        function openPin(pinId) {
            alert('Pin details page coming soon!');
        }

        function savePin(pinId) {
            <?php if (isLoggedIn()): ?>
                alert('Pin saved! (Feature coming soon)');
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }

        // Profile functions
        function editProfile() {
            alert('Profile editing feature coming soon!');
        }

        function followUser(userId) {
            <?php if (isLoggedIn()): ?>
                alert('Follow feature coming soon!');
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }

        function sendMessage() {
            alert('Messaging feature coming soon!');
        }

        // Error handling for images
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    if (!this.src.includes('placeholder')) {
                        this.src = 'https://via.placeholder.com/400x600/f1f1f1/666?text=Image+Not+Found';
                    }
                });
            });
        });
    </script>
</body>
</html>
