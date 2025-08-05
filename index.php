<?php
require_once 'db.php';

// Get trending pins with error handling
try {
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.profile_image, c.name as category_name 
            FROM pins p 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.views DESC, p.created_at DESC 
            LIMIT 50
        ");
        $stmt->execute();
        $pins = $stmt->fetchAll();
    } else {
        // Fallback to file storage
        $pins = [];
        $pins_file = 'data/pins.json';
        $users_file = 'data/users.json';
        
        if (file_exists($pins_file) && file_exists($users_file)) {
            $pins_data = json_decode(file_get_contents($pins_file), true) ?: [];
            $users_data = json_decode(file_get_contents($users_file), true) ?: [];
            
            // Create user lookup
            $users_lookup = [];
            foreach ($users_data as $user) {
                $users_lookup[$user['id']] = $user;
            }
            
            // Add user data to pins
            foreach ($pins_data as $pin) {
                if (isset($users_lookup[$pin['user_id']])) {
                    $pin['username'] = $users_lookup[$pin['user_id']]['username'];
                    $pin['profile_image'] = $users_lookup[$pin['user_id']]['profile_image'];
                    $pin['category_name'] = 'General';
                    $pins[] = $pin;
                }
            }
        }
    }
} catch(PDOException $e) {
    $pins = [];
    error_log("Database error: " . $e->getMessage());
}

// Get categories with error handling
try {
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        $categories = $stmt->fetchAll();
    } else {
        // Fallback to file storage
        $categories_file = 'data/categories.json';
        if (file_exists($categories_file)) {
            $categories = json_decode(file_get_contents($categories_file), true) ?: [];
        } else {
            $categories = [];
        }
    }
} catch(PDOException $e) {
    $categories = [];
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinterest Clone - Discover Ideas</title>
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

        /* Header Styles */
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
            min-width: 80px;
        }

        .nav-links {
            display: flex;
            gap: 8px;
        }

        .nav-link {
            padding: 12px 16px;
            text-decoration: none;
            color: #333;
            border-radius: 24px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #111;
            color: white;
        }

        .search-container {
            flex: 1;
            position: relative;
            max-width: 600px;
        }

        .search-box {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border: none;
            border-radius: 24px;
            background-color: #f1f1f1;
            font-size: 16px;
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #767676;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 8px;
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

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 20px 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* System Status */
        .system-status {
            background: #fff3cd;
            color: #856404;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 14px;
        }

        /* Category Filter */
        .category-filter {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .category-btn {
            padding: 8px 16px;
            border: 2px solid #e1e1e1;
            border-radius: 24px;
            background: white;
            color: #333;
            cursor: pointer;
            white-space: nowrap;
            font-weight: 500;
            transition: all 0.2s;
        }

        .category-btn:hover, .category-btn.active {
            border-color: #333;
            background-color: #333;
            color: white;
        }

        /* Masonry Grid */
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

        .pin-user {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .pin-user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }

        .pin-username {
            font-size: 12px;
            color: #767676;
            text-decoration: none;
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
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .pins-grid { columns: 4; }
        }

        @media (max-width: 900px) {
            .pins-grid { columns: 3; }
            .nav-links { display: none; }
        }

        @media (max-width: 600px) {
            .pins-grid { columns: 2; }
            .search-container { max-width: none; }
            .category-filter { margin: 0 -16px 32px; padding: 0 16px; }
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
            
            <nav class="nav-links">
                <a href="index.php" class="nav-link active">Home</a>
                <a href="search.php" class="nav-link">Explore</a>
                <a href="create.php" class="nav-link">Create</a>
            </nav>

            <div class="search-container">
                <div class="search-icon">🔍</div>
                <input type="text" class="search-box" placeholder="Search for ideas" id="searchInput">
            </div>

            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <?php if ($user): ?>
                        <a href="profile.php" class="btn btn-secondary">Profile</a>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="profile-avatar" onclick="toggleUserMenu()">
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Log in</a>
                    <a href="signup.php" class="btn btn-primary">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php if (!$pdo): ?>
                <div class="system-status">
                    ⚠️ Using backup storage system (database unavailable) - <a href="fix_users.php" style="color: #e60023;">Fix Users</a>
                </div>
            <?php endif; ?>

            <?php if (empty($pins) && empty($categories)): ?>
                <div class="system-status">
                    <strong>No data found!</strong> Please run the setup to create sample data. 
                    <a href="fix_users.php" style="color: #e60023;">Setup Now</a>
                </div>
            <?php endif; ?>

            <!-- Category Filter -->
            <?php if (!empty($categories)): ?>
                <div class="category-filter">
                    <button class="category-btn active" onclick="filterByCategory('all')">All</button>
                    <?php foreach ($categories as $category): ?>
                        <button class="category-btn" onclick="filterByCategory('<?php echo htmlspecialchars($category['slug']); ?>')">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Pins Grid -->
            <?php if (!empty($pins)): ?>
                <div class="pins-grid" id="pinsGrid">
                    <?php foreach ($pins as $pin): ?>
                        <div class="pin-card" data-category="<?php echo htmlspecialchars($pin['category_name'] ?? 'uncategorized'); ?>" onclick="openPin(<?php echo $pin['id']; ?>)">
                            <img src="<?php echo htmlspecialchars($pin['image_url']); ?>" alt="<?php echo htmlspecialchars($pin['title']); ?>" class="pin-image" onerror="this.src='https://via.placeholder.com/400x600/f1f1f1/666?text=Image+Not+Found'">
                            <div class="pin-overlay">
                                <button class="save-btn" onclick="event.stopPropagation(); savePin(<?php echo $pin['id']; ?>)">Save</button>
                            </div>
                            <div class="pin-info">
                                <div class="pin-title"><?php echo htmlspecialchars($pin['title']); ?></div>
                                <div class="pin-user">
                                    <img src="<?php echo htmlspecialchars($pin['profile_image']); ?>" alt="<?php echo htmlspecialchars($pin['username']); ?>" class="pin-user-avatar" onerror="this.src='https://via.placeholder.com/24x24/e60023/ffffff?text=U'">
                                    <a href="profile.php?user=<?php echo htmlspecialchars($pin['username']); ?>" class="pin-username"><?php echo htmlspecialchars($pin['username']); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📌</div>
                    <h3 class="empty-title">No pins found</h3>
                    <p class="empty-text">Be the first to create a pin!</p>
                    <a href="fix_users.php" class="btn btn-primary" style="margin-top: 16px;">Setup Data</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const pins = document.querySelectorAll('.pin-card');
            
            pins.forEach(pin => {
                const title = pin.querySelector('.pin-title').textContent.toLowerCase();
                const username = pin.querySelector('.pin-username').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || username.includes(searchTerm)) {
                    pin.style.display = 'block';
                } else {
                    pin.style.display = 'none';
                }
            });
        });

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query) {
                    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                }
            }
        });

        // Category filtering
        function filterByCategory(category) {
            const pins = document.querySelectorAll('.pin-card');
            const buttons = document.querySelectorAll('.category-btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter pins
            pins.forEach(pin => {
                const pinCategory = pin.dataset.category.toLowerCase();
                if (category === 'all' || pinCategory.includes(category)) {
                    pin.style.display = 'block';
                } else {
                    pin.style.display = 'none';
                }
            });
        }

        // Open pin details
        function openPin(pinId) {
            alert('Pin details page coming soon!');
        }

        // Save pin functionality
        function savePin(pinId) {
            <?php if (isLoggedIn()): ?>
                alert('Pin saved! (Feature coming soon)');
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }

        // User menu toggle
        function toggleUserMenu() {
            if (confirm('Go to profile?')) {
                window.location.href = 'profile.php';
            }
        }
    </script>
</body>
</html>
