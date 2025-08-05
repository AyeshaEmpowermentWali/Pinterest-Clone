<?php
require_once 'db.php';

$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$pins = [];

if ($query || $category) {
    $sql = "
        SELECT p.*, u.username, u.profile_image, c.name as category_name 
        FROM pins p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1
    ";
    $params = [];
    
    if ($query) {
        $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    
    if ($category) {
        $sql .= " AND c.slug = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY p.views DESC, p.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pins = $stmt->fetchAll();
}

// Get categories
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Pinterest Clone</title>
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
            min-width: 80px;
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

        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
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

        .search-header {
            margin-bottom: 32px;
        }

        .search-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .search-subtitle {
            color: #666;
            font-size: 16px;
        }

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
            text-decoration: none;
        }

        .category-btn:hover, .category-btn.active {
            border-color: #333;
            background-color: #333;
            color: white;
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

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-results-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .no-results-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .no-results-text {
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .pins-grid { columns: 4; }
        }

        @media (max-width: 900px) {
            .pins-grid { columns: 3; }
        }

        @media (max-width: 600px) {
            .pins-grid { columns: 2; }
            .search-container { max-width: none; }
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
            
            <div class="search-container">
                <div class="search-icon">🔍</div>
                <input type="text" class="search-box" placeholder="Search for ideas" value="<?php echo htmlspecialchars($query); ?>" id="searchInput">
            </div>

            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="btn btn-secondary">Profile</a>
                    <a href="create.php" class="btn btn-primary">Create</a>
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
            <div class="search-header">
                <h1 class="search-title">
                    <?php if ($query): ?>
                        Search results for "<?php echo htmlspecialchars($query); ?>"
                    <?php elseif ($category): ?>
                        <?php 
                        $cat_name = '';
                        foreach ($categories as $cat) {
                            if ($cat['slug'] === $category) {
                                $cat_name = $cat['name'];
                                break;
                            }
                        }
                        echo $cat_name;
                        ?>
                    <?php else: ?>
                        Search
                    <?php endif; ?>
                </h1>
                <p class="search-subtitle"><?php echo count($pins); ?> pins found</p>
            </div>

            <!-- Category Filter -->
            <div class="category-filter">
                <a href="search.php?q=<?php echo urlencode($query); ?>" class="category-btn <?php echo !$category ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="search.php?q=<?php echo urlencode($query); ?>&category=<?php echo $cat['slug']; ?>" 
                       class="category-btn <?php echo $category === $cat['slug'] ? 'active' : ''; ?>">
                        <?php echo $cat['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Results -->
            <?php if (count($pins) > 0): ?>
                <div class="pins-grid">
                    <?php foreach ($pins as $pin): ?>
                        <div class="pin-card" onclick="openPin(<?php echo $pin['id']; ?>)">
                            <img src="<?php echo $pin['image_url']; ?>" alt="<?php echo htmlspecialchars($pin['title']); ?>" class="pin-image">
                            <div class="pin-overlay">
                                <button class="save-btn" onclick="event.stopPropagation(); savePin(<?php echo $pin['id']; ?>)">Save</button>
                            </div>
                            <div class="pin-info">
                                <div class="pin-title"><?php echo htmlspecialchars($pin['title']); ?></div>
                                <div class="pin-user">
                                    <img src="<?php echo $pin['profile_image']; ?>" alt="<?php echo $pin['username']; ?>" class="pin-user-avatar">
                                    <a href="profile.php?user=<?php echo $pin['username']; ?>" class="pin-username"><?php echo $pin['username']; ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3 class="no-results-title">No results found</h3>
                    <p class="no-results-text">Try searching for something else or browse our categories</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query) {
                    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                }
            }
        });

        // Pin functions
        function openPin(pinId) {
            window.location.href = `pin.php?id=${pinId}`;
        }

        function savePin(pinId) {
            <?php if (isLoggedIn()): ?>
                window.location.href = `save_pin.php?pin_id=${pinId}`;
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }
    </script>
</body>
</html>
