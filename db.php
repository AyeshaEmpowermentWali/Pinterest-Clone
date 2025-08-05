<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration with better error handling
$db_configs = [
    // Primary configuration (your provided credentials)
    [
        'host' => 'dbw3qq4sld28lp.mysql.database.azure.com',
        'username' => 'u2fm1vryymcjr',
        'password' => 'mfd4eyv5w7bkari',
        'database' => 'pinterest_clone',
        'port' => 3306
    ],
    // Localhost fallback for local development
    [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'pinterest_clone',
        'port' => 3306
    ],
    [
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
        'database' => 'pinterest_clone',
        'port' => 3306
    ]
];

$pdo = null;
$connection_info = '';
$using_local = false;

// Suppress connection errors and try each configuration
foreach ($db_configs as $index => $config) {
    try {
        // Suppress DNS resolution errors
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        
        // Set a short timeout to avoid long waits
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5 // 5 second timeout
        ];
        
        // Try to connect without database first
        $test_pdo = @new PDO($dsn, $config['username'], $config['password'], $options);
        
        // Check if database exists, create if not
        $stmt = $test_pdo->query("SHOW DATABASES LIKE '{$config['database']}'");
        if ($stmt->rowCount() == 0) {
            $test_pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['database']}");
        }
        
        // Now connect to the specific database
        $dsn_with_db = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = @new PDO($dsn_with_db, $config['username'], $config['password'], $options);
        
        // Test the connection with a simple query
        $pdo->query("SELECT 1");
        
        // If we get here, connection is successful
        $connection_info = "Connected to: {$config['host']} as {$config['username']}";
        $using_local = ($config['host'] === 'localhost' || $config['host'] === '127.0.0.1');
        
        // If using local database, set it up
        if ($using_local) {
            setupLocalDatabase($pdo);
        }
        
        break;
        
    } catch(Exception $e) {
        // Silently continue to next configuration
        continue;
    }
}

// If still no connection, create SQLite fallback
if (!$pdo) {
    try {
        $pdo = createSQLiteFallback();
        $connection_info = "Using SQLite fallback database";
    } catch(Exception $e) {
        // Even SQLite failed, continue without database
        $connection_info = "No database connection available";
    }
}

function setupLocalDatabase($pdo) {
    try {
        // Check if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            // Create tables
            $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                profile_image VARCHAR(255) DEFAULT 'https://via.placeholder.com/150x150/e60023/ffffff?text=User',
                bio TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                slug VARCHAR(50) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS boards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                is_private BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS pins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                image_url VARCHAR(500) NOT NULL,
                category_id INT,
                source_url VARCHAR(500),
                views INT DEFAULT 0,
                likes INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS board_pins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                board_id INT NOT NULL,
                pin_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
                FOREIGN KEY (pin_id) REFERENCES pins(id) ON DELETE CASCADE,
                UNIQUE KEY unique_board_pin (board_id, pin_id)
            );

            CREATE TABLE IF NOT EXISTS pin_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                pin_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (pin_id) REFERENCES pins(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_pin_like (user_id, pin_id)
            );

            CREATE TABLE IF NOT EXISTS follows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                follower_id INT NOT NULL,
                following_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_follow (follower_id, following_id)
            );
            ";
            
            $pdo->exec($sql);
            
            // Insert sample data
            $pdo->exec("
                INSERT IGNORE INTO categories (name, slug) VALUES
                ('Fashion', 'fashion'),
                ('Art', 'art'),
                ('Food', 'food'),
                ('Travel', 'travel'),
                ('DIY', 'diy'),
                ('Home Decor', 'home-decor'),
                ('Photography', 'photography'),
                ('Nature', 'nature'),
                ('Technology', 'technology'),
                ('Fitness', 'fitness');
            ");
            
            // Create admin user
            $admin_password = password_hash('password123', PASSWORD_DEFAULT);
            $pdo->exec("
                INSERT IGNORE INTO users (username, email, full_name, password, bio) VALUES
                ('admin', 'admin@pinterest.com', 'Pinterest Admin', '$admin_password', 'Welcome to our Pinterest clone!');
            ");
            
            // Add sample pins
            $pdo->exec("
                INSERT IGNORE INTO pins (user_id, title, description, image_url, category_id, views) VALUES
                (1, 'Beautiful Sunset', 'Amazing sunset photography from the mountains', 'https://picsum.photos/400/600?random=1', 8, 150),
                (1, 'Delicious Pasta Recipe', 'Easy homemade pasta with fresh ingredients', 'https://picsum.photos/400/500?random=2', 3, 89),
                (1, 'Modern Living Room', 'Minimalist design inspiration for your home', 'https://picsum.photos/400/700?random=3', 6, 234),
                (1, 'Fashion Trends 2024', 'Latest fashion trends and styling tips', 'https://picsum.photos/400/800?random=4', 1, 178),
                (1, 'DIY Garden Ideas', 'Creative ways to beautify your garden', 'https://picsum.photos/400/550?random=5', 5, 92),
                (1, 'Travel Photography', 'Stunning landscapes from around the world', 'https://picsum.photos/400/650?random=6', 4, 267),
                (1, 'Abstract Art', 'Modern abstract painting techniques', 'https://picsum.photos/400/600?random=7', 2, 145),
                (1, 'Fitness Motivation', 'Daily workout routines and tips', 'https://picsum.photos/400/750?random=8', 10, 198);
            ");
            
            // Create default board
            $pdo->exec("
                INSERT IGNORE INTO boards (user_id, name, description) VALUES
                (1, 'My Favorites', 'Collection of my favorite pins');
            ");
        }
    } catch(PDOException $e) {
        error_log("Setup error: " . $e->getMessage());
    }
}

function createSQLiteFallback() {
    try {
        // Create data directory
        if (!is_dir('data')) {
            mkdir('data', 0755, true);
        }
        
        $pdo = new PDO('sqlite:data/pinterest.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Create tables for SQLite
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                full_name TEXT NOT NULL,
                profile_image TEXT DEFAULT 'https://via.placeholder.com/150x150/e60023/ffffff?text=User',
                bio TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                image_url TEXT NOT NULL,
                category_id INTEGER,
                source_url TEXT,
                views INTEGER DEFAULT 0,
                likes INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );

            CREATE TABLE IF NOT EXISTS boards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                is_private INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
        ");
        
        // Insert sample data
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            // Insert categories
            $pdo->exec("
                INSERT INTO categories (name, slug) VALUES
                ('Fashion', 'fashion'),
                ('Art', 'art'),
                ('Food', 'food'),
                ('Travel', 'travel'),
                ('DIY', 'diy');
            ");
            
            // Insert admin user
            $admin_password = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, bio) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@pinterest.com', 'Pinterest Admin', $admin_password, 'Welcome to Pinterest clone!']);
            
            // Insert sample pins
            $pdo->exec("
                INSERT INTO pins (user_id, title, description, image_url, category_id, views) VALUES
                (1, 'Beautiful Sunset', 'Amazing sunset photography', 'https://picsum.photos/400/600?random=1', 1, 150),
                (1, 'Delicious Recipe', 'Easy homemade pasta', 'https://picsum.photos/400/500?random=2', 3, 89),
                (1, 'Modern Design', 'Minimalist home inspiration', 'https://picsum.photos/400/700?random=3', 2, 234);
            ");
        }
        
        return $pdo;
        
    } catch(PDOException $e) {
        die("Critical Error: Cannot create any database connection. " . $e->getMessage());
    }
}

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(Exception $e) {
        return null;
    }
}

function redirect($url) {
    echo "<script>window.location.href = '$url';</script>";
    exit();
}

function getConnectionInfo() {
    global $connection_info, $using_local;
    return [
        'info' => $connection_info,
        'local' => $using_local,
        'working' => !empty($connection_info)
    ];
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Create uploads directory
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}
if (!is_dir('uploads/pins')) {
    mkdir('uploads/pins', 0755, true);
}
?>
