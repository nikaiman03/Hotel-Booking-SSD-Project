<?php
// Set HTTP response code to 404
http_response_code(404);

// Log the missing page request for the admin to fix broken links
if (function_exists('error_log')) {
    $error_details = [
        'timestamp' => date('Y-m-d H:i:s'),
        'requested_url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? 'Direct Access',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    error_log("404 Page Not Found: " . json_encode($error_details));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Luxury Stay Hotel</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* Using a slightly different gradient to distinguish 404 from 403 while staying on brand */
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
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
            color: #3498db;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 15px;
        }

        .error-code {
            background: #3498db;
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
            border-left: 4px solid #3498db;
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
            .error-container { padding: 30px 20px; }
            h1 { font-size: 1.8rem; }
            .btn-group { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-compass"></i>
        </div>
        
        <h1>Page Not Found</h1>
        
        <div class="error-code">Error 404 - Not Found</div>
        
        <p class="error-message">
            It seems you've wandered into an uncharted area of our hotel.<br>
            The page you are looking for doesn't exist or has been moved.
        </p>
        
        <div class="access-details">
            <p><i class="fas fa-link"></i> Path: <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
            <p><i class="fas fa-search"></i> Suggestion: Check the URL for typos</p>
        </div>
        
        <div class="btn-group">
            <a href="/hotelb/index.html" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="contact.php" class="btn btn-secondary">
                <i class="fas fa-envelope"></i> Contact Support
            </a>
        </div>
        
        <div class="contact-info">
            Need help finding something? <a href="mailto:support@ourhotel.com">Email Us</a>
            <br>
            <small>Session ID: <?php echo strtoupper(bin2hex(random_bytes(4))); ?></small>
        </div>
    </div>

    <script>
        console.log('404 Error: The requested resource was not found on this server.');
    </script>
</body>
</html>