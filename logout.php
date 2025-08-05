<?php
// Simple logout without database dependency
session_start();

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to homepage with success message
echo "<!DOCTYPE html>
<html>
<head>
    <title>Logged Out - Pinterest Clone</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .logout-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #e60023;
            margin-bottom: 20px;
        }
        .message {
            font-size: 18px;
            color: #333;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #e60023;
            color: white;
            text-decoration: none;
            border-radius: 24px;
            font-weight: 600;
            margin: 5px;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #d50020;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class='logout-container'>
        <div class='logo'>Pinterest</div>
        <div class='message'>✅ You have been logged out successfully!</div>
        <a href='index.php' class='btn'>🏠 Go to Homepage</a>
        <a href='login.php' class='btn btn-secondary'>🔐 Login Again</a>
    </div>
    
    <script>
        // Auto redirect after 3 seconds
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 3000);
    </script>
</body>
</html>";
?>
