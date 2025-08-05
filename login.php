<?php
// Start with error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'db.php';
} catch (Exception $e) {
    session_start();
    $pdo = null;
}

$error = '';

// Emergency fallback if no database connection
if (!$pdo) {
    $users_file = 'data/users.json';
    $users_data = [];
    
    if (file_exists($users_file)) {
        $users_data = json_decode(file_get_contents($users_file), true) ?: [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $user_found = false;
        
        // Try database login first
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $user_found = true;
                }
            } catch (Exception $e) {
                // Database error, try file-based login
                $pdo = null;
            }
        }
        
        // File-based login fallback
        if (!$pdo && !$user_found) {
            foreach ($users_data as $user) {
                if ($user['email'] === $email && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['using_file_storage'] = true;
                    $user_found = true;
                    break;
                }
            }
        }
        
        if ($user_found) {
            echo "<script>window.location.href = 'index.php';</script>";
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in - Pinterest Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            text-align: center;
            padding: 40px 40px 20px;
            background: linear-gradient(135deg, #e60023 0%, #ff6b6b 100%);
            color: white;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            opacity: 0.9;
            font-size: 16px;
        }

        .auth-form {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s;
            outline: none;
        }

        .form-input:focus {
            border-color: #e60023;
            box-shadow: 0 0 0 3px rgba(230, 0, 35, 0.1);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e60023 0%, #ff6b6b 100%);
            color: white;
            margin-bottom: 16px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(230, 0, 35, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e1e1e1;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #d1d5db;
        }

        .error-message {
            background: #fee;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c53030;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .auth-footer {
            text-align: center;
            padding: 20px 40px 40px;
            color: #666;
            font-size: 14px;
        }

        .auth-link {
            color: #e60023;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: #666;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e1e1;
        }

        .divider span {
            padding: 0 16px;
        }

        .system-status {
            background: #fff3cd;
            color: #856404;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            text-align: center;
        }

        .demo-credentials {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #007bff;
        }

        @media (max-width: 480px) {
            .auth-container {
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            
            .auth-header,
            .auth-form,
            .auth-footer {
                padding-left: 24px;
                padding-right: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">Pinterest</div>
            <div class="auth-subtitle">Welcome back</div>
        </div>

        <form class="auth-form" method="POST">
            <?php if (!$pdo): ?>
                <div class="system-status">
                    ⚠️ Using backup login system (database unavailable)
                </div>
            <?php endif; ?>

            <div class="demo-credentials">
                <strong>🔑 Demo Login:</strong><br>
                Email: admin@pinterest.com<br>
                Password: password123
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>

            <button type="submit" class="btn btn-primary">Log in</button>
            
            <div class="divider">
                <span>OR</span>
            </div>
            
            <a href="index.php" class="btn btn-secondary">Continue as Guest</a>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="signup.php" class="auth-link">Sign up</a>
            <br><br>
            <a href="index.php" class="auth-link">← Back to Home</a>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('.auth-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Auto-focus first input
        document.getElementById('email').focus();

        // Demo login quick fill
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                document.getElementById('email').value = 'admin@pinterest.com';
                document.getElementById('password').value = 'password123';
            }
        });
    </script>
</body>
</html>
