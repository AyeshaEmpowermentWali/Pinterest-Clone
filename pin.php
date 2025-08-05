<?php
require_once 'db.php';

$pin_id = $_GET['id'] ?? 0;
$pin = null;
$pin_user = null;
$related_pins = [];

// Get pin details with error handling
if ($pin_id) {
    try {
        if ($pdo) {
            // Get pin from database
            $stmt = $pdo->prepare("
                SELECT p.*, u.username, u.full_name, u.profile_image, c.name as category_name 
                FROM pins p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$pin_id]);
            $pin = $stmt->fetch();
            
            if ($pin) {
                // Update view count
                $stmt = $pdo->prepare("UPDATE pins SET views = views + 1 WHERE id = ?");
                $stmt->execute([$pin_id]);
                
                // Get related pins
                $stmt = $pdo->prepare("
                    SELECT p.*, u.username, u.profile_image 
                    FROM pins p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.id != ? AND (p.category_id = ? OR p.user_id = ?) 
                    ORDER BY RAND() 
                    LIMIT 12
                ");
                $stmt->execute([$pin_id, $pin['category_id'], $pin['user_id']]);
                $related_pins = $stmt->fetchAll();
            }
        } else {
            // Fallback to file storage
            $pins_file = 'data/pins.json';
            $users_file = 'data/users.json';
            
            if (file_exists($pins_file) && file_exists($users_file)) {
                $pins_data = json_decode(file_get_contents($pins_file), true) ?: [];
                $users_data = json_decode(file_get_contents($users_file), true) ?: [];
                
                // Find pin
                foreach ($pins_data as $p) {
                    if ($p['id'] == $pin_id) {
                        $pin = $p;
                        break;
                    }
                }
                
                // Find user
                if ($pin) {
                    foreach ($users_data as $user) {
                        if ($user['id'] == $pin['user_id']) {
                            $pin['username'] = $user['username'];
                            $pin['full_name'] = $user['full_name'];
                            $pin['profile_image'] = $user['profile_image'];
                            break;
                        }
                    }
                    
                    // Get related pins
                    foreach ($pins_data as $p) {
                        if ($p['id'] != $pin_id && count($related_pins) < 12) {
                            foreach ($users_data as $user) {
                                if ($user['id'] == $p['user_id']) {
                                    $p['username'] = $user['username'];
                                    $p['profile_image'] = $user['profile_image'];
                                    $related_pins[] = $p;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Pin fetch error: " . $e->getMessage());
    }
}

// If pin not found, redirect to home
if (!$pin) {
    echo "<script>
        alert('Pin not found!');
        window.location.href = 'index.php';
    </script>";
    exit();
}

// Ensure required fields exist
$pin['description'] = $pin['description'] ?? '';
$pin['source_url'] = $pin['source_url'] ?? '';
$pin['category_name'] = $pin['category_name'] ?? 'General';
$pin['views'] = $pin['views'] ?? 0;
$pin['likes'] = $pin['likes'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pin['title']); ?> - Pinterest Clone</title>
    <meta name="description" content="<?php echo htmlspecialchars($pin['description']); ?>">
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

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .pin-detail {
            display: flex;
            gap: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .pin-image-section {
            flex: 1;
            max-width: 600px;
        }

        .pin-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .pin-info-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
        }

        .pin-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .save-btn {
            background: #e60023;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .save-btn:hover {
            background: #d50020;
            transform: translateY(-2px);
        }

        .share-btn {
            background: #f1f1f1;
            color: #333;
            border: none;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .share-btn:hover {
            background: #e1e1e1;
        }

        .pin-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }

        .pin-description {
            font-size: 16px;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .pin-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 24px;
            padding: 16px 0;
            border-top: 1px solid #e1e1e1;
            border-bottom: 1px solid #e1e1e1;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .meta-number {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .meta-label {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .pin-user {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            text-decoration: none;
        }

        .user-username {
            font-size: 14px;
            color: #666;
        }

        .follow-btn {
            background: #111;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .pin-source {
            margin-top: auto;
        }

        .source-link {
            color: #e60023;
            text-decoration: none;
            font-weight: 600;
        }

        .source-link:hover {
            text-decoration: underline;
        }

        /* Related Pins */
        .related-section {
            margin-top: 60px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 24px;
            text-align: center;
        }

        .related-pins {
            columns: 4;
            column-gap: 16px;
        }

        .related-pin {
            break-inside: avoid;
            margin-bottom: 16px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .related-pin:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .related-pin-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .related-pin-info {
            padding: 12px;
        }

        .related-pin-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .related-pin-user {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .related-user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }

        .related-username {
            font-size: 12px;
            color: #666;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .pin-detail {
                flex-direction: column;
                gap: 0;
            }

            .pin-info-section {
                padding: 24px;
            }

            .pin-title {
                font-size: 24px;
            }

            .related-pins {
                columns: 2;
            }
        }

        @media (max-width: 480px) {
            .related-pins {
                columns: 1;
            }

            .pin-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">Pinterest</a>
            <div class="nav-actions">
                <a href="index.php" class="btn btn-secondary">← Back</a>
                <?php if (isLoggedIn()): ?>
                    <a href="create.php" class="btn btn-primary">Create</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Pin Detail -->
            <div class="pin-detail">
                <div class="pin-image-section">
                    <img src="<?php echo htmlspecialchars($pin['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($pin['title']); ?>" 
                         class="pin-image"
                         onerror="this.src='https://via.placeholder.com/600x800/f1f1f1/666?text=Image+Not+Found'">
                </div>

                <div class="pin-info-section">
                    <div class="pin-actions">
                        <button class="save-btn" onclick="savePin(<?php echo $pin['id']; ?>)">
                            💾 Save
                        </button>
                        <button class="share-btn" onclick="sharePin()">
                            📤 Share
                        </button>
                    </div>

                    <h1 class="pin-title"><?php echo htmlspecialchars($pin['title']); ?></h1>

                    <?php if (!empty($pin['description'])): ?>
                        <p class="pin-description"><?php echo nl2br(htmlspecialchars($pin['description'])); ?></p>
                    <?php endif; ?>

                    <div class="pin-meta">
                        <div class="meta-item">
                            <div class="meta-number"><?php echo number_format($pin['views']); ?></div>
                            <div class="meta-label">Views</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-number"><?php echo number_format($pin['likes']); ?></div>
                            <div class="meta-label">Likes</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-number"><?php echo htmlspecialchars($pin['category_name']); ?></div>
                            <div class="meta-label">Category</div>
                        </div>
                    </div>

                    <div class="pin-user">
                        <img src="<?php echo htmlspecialchars($pin['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($pin['username']); ?>" 
                             class="user-avatar"
                             onerror="this.src='https://via.placeholder.com/48x48/e60023/ffffff?text=<?php echo strtoupper(substr($pin['username'], 0, 1)); ?>'">
                        <div class="user-info">
                            <a href="profile.php?user=<?php echo htmlspecialchars($pin['username']); ?>" class="user-name">
                                <?php echo htmlspecialchars($pin['full_name'] ?? $pin['username']); ?>
                            </a>
                            <div class="user-username">@<?php echo htmlspecialchars($pin['username']); ?></div>
                        </div>
                        <?php if (isLoggedIn() && $_SESSION['username'] !== $pin['username']): ?>
                            <button class="follow-btn" onclick="followUser('<?php echo htmlspecialchars($pin['username']); ?>')">
                                Follow
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($pin['source_url'])): ?>
                        <div class="pin-source">
                            <a href="<?php echo htmlspecialchars($pin['source_url']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               class="source-link">
                                🔗 Visit Source
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Related Pins -->
            <?php if (!empty($related_pins)): ?>
                <div class="related-section">
                    <h2 class="section-title">More like this</h2>
                    <div class="related-pins">
                        <?php foreach ($related_pins as $related_pin): ?>
                            <div class="related-pin" onclick="openPin(<?php echo $related_pin['id']; ?>)">
                                <img src="<?php echo htmlspecialchars($related_pin['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($related_pin['title']); ?>" 
                                     class="related-pin-image"
                                     onerror="this.src='https://via.placeholder.com/300x400/f1f1f1/666?text=Image+Not+Found'">
                                <div class="related-pin-info">
                                    <div class="related-pin-title"><?php echo htmlspecialchars($related_pin['title']); ?></div>
                                    <div class="related-pin-user">
                                        <img src="<?php echo htmlspecialchars($related_pin['profile_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($related_pin['username']); ?>" 
                                             class="related-user-avatar"
                                             onerror="this.src='https://via.placeholder.com/24x24/e60023/ffffff?text=U'">
                                        <a href="profile.php?user=<?php echo htmlspecialchars($related_pin['username']); ?>" 
                                           class="related-username">
                                            <?php echo htmlspecialchars($related_pin['username']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Pin functions
        function openPin(pinId) {
            window.location.href = `pin.php?id=${pinId}`;
        }

        function savePin(pinId) {
            <?php if (isLoggedIn()): ?>
                alert('Pin saved! (Feature coming soon)');
            <?php else: ?>
                if (confirm('Please login to save pins. Go to login page?')) {
                    window.location.href = 'login.php';
                }
            <?php endif; ?>
        }

        function sharePin() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($pin['title']); ?>',
                    text: '<?php echo addslashes($pin['description']); ?>',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link copied to clipboard!');
                }).catch(() => {
                    alert('Link: ' + window.location.href);
                });
            }
        }

        function followUser(username) {
            <?php if (isLoggedIn()): ?>
                alert('Follow feature coming soon!');
            <?php else: ?>
                if (confirm('Please login to follow users. Go to login page?')) {
                    window.location.href = 'login.php';
                }
            <?php endif; ?>
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.history.back();
            }
        });

        // Image error handling
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    if (!this.src.includes('placeholder')) {
                        this.src = this.src.includes('related') ? 
                            'https://via.placeholder.com/300x400/f1f1f1/666?text=Image+Not+Found' :
                            'https://via.placeholder.com/600x800/f1f1f1/666?text=Image+Not+Found';
                    }
                });
            });
        });
    </script>
</body>
</html>
