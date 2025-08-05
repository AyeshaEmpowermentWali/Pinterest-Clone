<?php
// Local database setup script
$local_configs = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password']
];

$success = false;
$error_messages = [];

foreach ($local_configs as $config) {
    try {
        $pdo = new PDO("mysql:host={$config['host']};charset=utf8mb4", $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS pinterest_clone");
        $pdo->exec("USE pinterest_clone");
        
        // Read and execute SQL file
        $sql = file_get_contents('database.sql');
        if ($sql) {
            $pdo->exec($sql);
        }
        
        $success = true;
        $working_config = $config;
        break;
        
    } catch (PDOException $e) {
        $error_messages[] = "Config {$config['host']}/{$config['user']}: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Database Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #e60023;
            color: white;
            text-decoration: none;
            border-radius: 24px;
            font-weight: 600;
            margin: 10px 5px;
        }
        .btn:hover { background: #d50020; }
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            margin: 15px 0;
            border-left: 4px solid #e60023;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Local Database Setup</h1>
        
        <?php if ($success): ?>
            <div class="success">
                <h3>✅ Database Setup Successful!</h3>
                <p>Your local Pinterest clone database has been created successfully.</p>
                
                <h4>Connection Details:</h4>
                <div class="code-block">
                    Host: <?php echo $working_config['host']; ?><br>
                    Username: <?php echo $working_config['user']; ?><br>
                    Password: <?php echo $working_config['pass'] ?: '(empty)'; ?><br>
                    Database: pinterest_clone
                </div>
                
                <h4>Test Login:</h4>
                <div class="code-block">
                    Username: admin<br>
                    Password: password123
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="index.php" class="btn">🏠 Go to Homepage</a>
                <a href="login.php" class="btn">🔐 Login</a>
            </div>
            
        <?php else: ?>
            <div class="error">
                <h3>❌ Database Setup Failed</h3>
                <p>Could not connect to local MySQL database. Please make sure:</p>
                <ul>
                    <li>MySQL/MariaDB is installed and running</li>
                    <li>You have the correct username/password</li>
                    <li>PHP has PDO MySQL extension enabled</li>
                </ul>
                
                <h4>Error Details:</h4>
                <?php foreach ($error_messages as $error): ?>
                    <div style="margin: 10px 0; padding: 10px; background: #fff; border-radius: 4px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center;">
                <a href="test_connection.php" class="btn">🔍 Test Connection</a>
                <a href="index.php" class="btn">🏠 Try Anyway</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
