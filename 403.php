<?php
// Set HTTP response code to 403
http_response_code(403);

// Log the unauthorized access attempt
if (function_exists('error_log')) {
    $auth_details = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not logged in'
    ];
    error_log("403 Forbidden Access: " . json_encode($auth_details));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | Luxury Stay Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f39c12 0%, #d35400 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error-icon {
            font-size: 80px;
            color: #e67e22;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 15px;
        }

        .error-code {
            background: #e67e22;
            color: white;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 25px;
        }

        .error-message {
            color: #7f8c8d;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .access-details {
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            text-align: left;
            margin-bottom: 30px;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .access-details p {
            margin-bottom: 8px;
            color: #555;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #1e90ff;
            color: white;
            border: 2px solid #1e90ff;
        }

        .btn-primary:hover {
            background: #0077e6;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 144, 255, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #2c3e50;
            border: 2px solid #bdc3c7;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #856404;
        }

        .security-notice i {
            margin-right: 8px;
        }

        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.85rem;
            color: #95a5a6;
        }

        .contact-info a {
            color: #1e90ff;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .error-container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-user-shield"></i>
        </div>
        
        <h1>Access Denied</h1>
        
        <div class="error-code">Error 403 - Forbidden</div>
        
        <p class="error-message">
            You don't have permission to access this page.<br>
            Please ensure you're logged in with the correct account.
        </p>
        
        <div class="access-details">
            <p><i class="fas fa-clock"></i> Time: <?php echo date('F j, Y, g:i a'); ?></p>
            <p><i class="fas fa-location-dot"></i> IP: <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></p>
            <p><i class="fas fa-shield-alt"></i> This attempt has been logged</p>
        </div>
        
        <div class="btn-group">
            <a href="index.html" class="btn btn-primary">
                <i class="fas fa-home"></i> Home Page
            </a>
            <a href="login.php" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
        
        <div class="security-notice">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Security Notice:</strong> Unauthorized access attempts are monitored and logged.
        </div>
        
        <div class="contact-info">
            Is this an error? <a href="mailto:admin@ourhotel.com">Contact Administrator</a>
            <br>
            <small>Reference ID: 403-<?php echo substr(md5(uniqid()), 0, 8); ?></small>
        </div>
    </div>
    
    <script>
        // Security: Prevent caching of this error page
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Log to console for debugging (admin only)
        console.warn('403 Access Denied: User attempted to access restricted resource');
        console.info('Timestamp:', '<?php echo date("Y-m-d H:i:s"); ?>');
        console.info('Path:', window.location.pathname);
    </script>
</body>
</html>