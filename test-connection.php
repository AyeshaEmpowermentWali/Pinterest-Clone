<?php
require_once 'db.php';

$status = getConnectionStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
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
        .status {
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
        }
        .status-connected { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-fallback { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover { background: #5a6268; }
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            margin: 15px 0;
            border-left: 4px solid #e60023;
        }
        .instructions {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Test</h1>
        
        <div class="status status-<?php echo $status['status']; ?>">
            <strong>Status:</strong> <?php echo $status['message']; ?>
        </div>

        <?php if ($status['status'] === 'error'): ?>
            <div class="warning">
                <h3>⚠️ Connection Failed</h3>
                <p>The database server could not be reached. This usually means:</p>
                <ul>
                    <li>The hostname <code>dbw3qq4sld28lp</code> is not accessible from your server</li>
                    <li>The database server might be down</li>
                    <li>Network/firewall issues</li>
                    <li>Incorrect credentials</li>
                </ul>
            </div>

            <div class="instructions">
                <h3>🛠️ Solutions to Try:</h3>
                
                <h4>Option 1: Use Local Database (Recommended)</h4>
                <p>Install MySQL/MariaDB locally and use these settings:</p>
                <div class="code-block">
                    Host: localhost<br>
                    Username: root<br>
                    Password: (leave empty or set your password)<br>
                    Database: pinterest_clone
                </div>
                
                <h4>Option 2: Check Your Hosting Provider</h4>
                <p>Contact your hosting provider for the correct database connection details:</p>
                <ul>
                    <li>Correct hostname/IP address</li>
                    <li>Port number (usually 3306)</li>
                    <li>Database name</li>
                    <li>Username and password</li>
                </ul>
                
                <h4>Option 3: Use Online Database Services</h4>
                <p>Try free database services like:</p>
                <ul>
                    <li>PlanetScale</li>
                    <li>Railway</li>
                    <li>Aiven</li>
                    <li>FreeSQLDatabase</li>
                </ul>
            </div>

        <?php elseif ($status['status'] === 'fallback'): ?>
            <div class="warning">
                <h3>📁 Using File-Based Storage</h3>
                <p>Since the database is not available, the system is using file-based storage as a fallback. This allows you to test the application, but with limited functionality.</p>
                
                <h4>Current Limitations:</h4>
                <ul>
                    <li>Data is stored in JSON files</li>
                    <li>No complex queries</li>
                    <li>Limited user management</li>
                    <li>No data persistence across server restarts</li>
                </ul>
            </div>

        <?php else: ?>
            <div class="instructions">
                <h3>✅ Database Connected Successfully!</h3>
                <p>Your Pinterest clone is ready to use. You can now:</p>
                <ul>
                    <li>Create user accounts</li>
                    <li>Upload and manage pins</li>
                    <li>Create boards</li>
                    <li>Search and filter content</li>
                </ul>
                
                <h4>Test Login:</h4>
                <div class="code-block">
                    Username: admin<br>
                    Password: password123
                </div>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn">🏠 Go to Homepage</a>
            <a href="login.php" class="btn btn-secondary">🔐 Login</a>
            <a href="signup.php" class="btn btn-secondary">📝 Sign Up</a>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;">
            <h4>Technical Details:</h4>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>PDO Available:</strong> <?php echo extension_loaded('pdo') ? 'Yes' : 'No'; ?></p>
            <p><strong>PDO MySQL:</strong> <?php echo extension_loaded('pdo_mysql') ? 'Yes' : 'No'; ?></p>
            <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
