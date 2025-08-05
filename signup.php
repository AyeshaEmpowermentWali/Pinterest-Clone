<?php
// Start with safe error handling - no database dependency initially
session_start();

$error = '';
$success = '';
$pdo = null;

// Try to connect to database, but don't fail if it doesn't work
try {
    // Suppress any connection errors
    $old_error_reporting = error_reporting(0);
    
    // Try to include db.php but catch any fatal errors
    if (file_exists('db.php')) {
        include_once 'db.php';
    }
    
    // Restore error reporting
    error_reporting($old_error_reporting);
} catch (Exception $e) {
    // Database connection failed, we'll use file storage
    $pdo = null;
}

// Ensure data directory exists
if (!is_dir('data')) {
    mkdir('data', 0755, true);
}

// File-based user storage functions
function loadUsers() {
    $users_file = 'data/users.json';
    if (file_exists($users_file)) {
        $data = json_decode(file_get_contents($users_file), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveUsers($users_data) {
    $users_file = 'data/users.json';
    return file_put_contents($users_file, json_encode($users_data, JSON_PRETTY_PRINT)) !== false;
}

function getNextUserId($users_data) {
    $max_id = 0;
    foreach ($users_data as $user) {
        if ($user['id'] > $max_id) {
            $max_id = $user['id'];
        }
    }
    return $max_id + 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } else {
        $user_created = false;
        
        // Try database registration first (if available)
        if ($pdo) {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->fetch()) {
                    $error = 'Username or email already exists';
                } else {
                    // Create user in database
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, profile_image, bio) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    $profile_image = 'https://via.placeholder.com/150x150/e60023/ffffff?text=' . strtoupper(substr($username, 0, 1));
                    $bio = "Welcome to Pinterest Clone!";
                    
                    if ($stmt->execute([$username, $email, $full_name, $hashed_password, $profile_image, $bio])) {
                        $user_id = $pdo->lastInsertId();
                        
                        // Create default board
                        try {
                            $stmt = $pdo->prepare("INSERT INTO boards (user_id, name, description) VALUES (?, 'My Pins', 'My saved pins')");
                            $stmt->execute([$user_id]);
                        } catch (Exception $e) {
                            // Board creation failed, but user created successfully
                        }
                        
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $user_created = true;
                    }
                }
            } catch(Exception $e) {
                // Database error, fall back to file storage
                $error = 'Database temporarily unavailable. Using backup system.';
                $pdo = null;
            }
        }
        
        // File-based registration (fallback or primary)
        if (!$user_created) {
            $users_data = loadUsers();
            
            // Check if user already exists
            $user_exists = false;
            foreach ($users_data as $user) {
                if (strtolower($user['username']) === strtolower($username) || 
                    strtolower($user['email']) === strtolower($email)) {
                    $user_exists = true;
                    break;
                }
            }
            
            if ($user_exists) {
                $error = 'Username or email already exists';
            } else {
                // Create new user
                $new_user = [
                    'id' => getNextUserId($users_data),
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $full_name,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'profile_image' => 'https://via.placeholder.com/150x150/e60023/ffffff?text=' . strtoupper(substr($username, 0, 1)),
                    'bio' => 'Welcome to Pinterest Clone!',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $users_data[] = $new_user;
                
                if (saveUsers($users_data)) {
                    $_SESSION['user_id'] = $new_user['id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['using_file_storage'] = true;
                    $user_created = true;
                } else {
                    $error = 'Failed to create account. Please check file permissions.';
                }
            }
        }
        
        // Success redirect
        if ($user_created) {
            $success_message = $pdo ? 
                'Account created successfully! Welcome to Pinterest Clone!' : 
                'Account created successfully! (Using backup storage system)';
            
            echo "<script>
                alert('$success_message');
                window.location.href = 'index.php';
            </script>";
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - Pinterest Clone</title>
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
            margin-bottom: 20px;
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

        .form-input.error {
            border-color: #dc3545;
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

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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

        .success-message {
            background: #f0fff4;
            color: #22543d;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #22543d;
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

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-weak { color: #e53e3e; }
        .strength-medium { color: #dd6b20; }
        .strength-strong { color: #38a169; }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #e60023;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        .input-help {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
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
            <div class="auth-subtitle">Find new ideas to try</div>
        </div>

        <form class="auth-form" method="POST" id="signupForm">
            <?php if (!$pdo): ?>
                <div class="system-status">
                    ⚠️ Using backup registration system (database temporarily unavailable)
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                       required maxlength="50" pattern="[a-zA-Z0-9_]+" 
                       title="Username can only contain letters, numbers, and underscores">
                <div class="input-help">3-50 characters, letters, numbers, and underscores only</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       required maxlength="100">
            </div>

            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-input" 
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                       required maxlength="100">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required minlength="6">
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                Creating your account...
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">Sign up</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php" class="auth-link">Log in</a>
            <br><br>
            <a href="index.php" class="auth-link">← Back to Home</a>
        </div>
    </div>

    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength < 3) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthDiv.textContent = 'Medium password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });

        // Real-time password confirmation
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = e.target.value;
            
            if (confirmPassword && password !== confirmPassword) {
                e.target.classList.add('error');
            } else {
                e.target.classList.remove('error');
            }
        });

        // Username validation
        document.getElementById('username').addEventListener('input', function(e) {
            const username = e.target.value;
            const regex = /^[a-zA-Z0-9_]+$/;
            
            if (username && !regex.test(username)) {
                e.target.classList.add('error');
            } else {
                e.target.classList.remove('error');
            }
        });

        // Form validation and submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const fullName = document.getElementById('full_name').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Clear previous errors
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error');
            });
            
            let hasError = false;
            
            if (!username || username.length < 3) {
                document.getElementById('username').classList.add('error');
                hasError = true;
            }
            
            if (!email || !isValidEmail(email)) {
                document.getElementById('email').classList.add('error');
                hasError = true;
            }
            
            if (!fullName) {
                document.getElementById('full_name').classList.add('error');
                hasError = true;
            }
            
            if (!password || password.length < 6) {
                document.getElementById('password').classList.add('error');
                hasError = true;
            }
            
            if (password !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('error');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
                alert('Please fix the highlighted fields');
                return;
            }
            
            // Show loading
            document.getElementById('loading').style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = 'Creating Account...';
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Auto-focus first input
        document.getElementById('username').focus();

        // Prevent double submission
        let submitted = false;
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            if (submitted) {
                e.preventDefault();
                return false;
            }
            submitted = true;
        });
    </script>
</body>
</html>
