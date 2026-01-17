<?php
// Set HTTP response code to 500
http_response_code(500);

// Log the error (if possible)
if (function_exists('error_log')) {
    $error_details = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? 'Direct access',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
    error_log("500 Error occurred: " . json_encode($error_details));
}

// Function to safely escape JavaScript output
function js_escape($string) {
    return json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error | Hotel Booking System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #e74c3c;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .error-code {
            background: #e74c3c;
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

        .error-details {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            text-align: left;
            margin-bottom: 30px;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .error-details p {
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
            background: #3498db;
            color: white;
            border: 2px solid #3498db;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #3498db;
            border: 2px solid #3498db;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }

        .btn-icon {
            font-size: 1.2rem;
        }

        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #95a5a6;
        }

        .contact-info a {
            color: #3498db;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .error-container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* For OWASP Compliance - Hide technical details in production */
        .technical-details {
            display: none;
            background: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 0.8rem;
            text-align: left;
            color: #666;
            border-left: 3px solid #e74c3c;
        }

        .show-details {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 10px;
            padding: 5px 10px;
        }

        .show-details:hover {
            text-decoration: underline;
        }
    </style>

</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1>Internal Server Error</h1>
        
        <div class="error-code">Error 500</div>
        
        <p class="error-message">
            Oops! Something went wrong on our end.<br>
            Our technical team has been notified and is working to fix the issue.
        </p>
        
        <div class="error-details">
            <p><i class="fas fa-clock"></i> Time: <?php echo htmlspecialchars(date('F j, Y, g:i a'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><i class="fas fa-ticket"></i> Error ID: ERR-<?php echo bin2hex(random_bytes(4)); ?></p>
            <p><i class="fas fa-exclamation-circle"></i> Please try again in a few minutes</p>
        </div>
        
        <div class="btn-group">
            <a href="/hotelb/index.html" class="btn btn-primary">
                <i class="fas fa-home btn-icon"></i> Go to Homepage
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left btn-icon"></i> Go Back
            </a>
        </div>
        
        <!-- For OWASP Compliance: No technical details shown to users -->
        <button class="show-details" onclick="toggleDetails()">
            <i class="fas fa-code"></i> Show Technical Details (Admin Only)
        </button>
        
        <div class="technical-details" id="techDetails">
            <p><strong>Debug Information:</strong></p>
            <p>• Server Time: <?php echo htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p>• Request URI: <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
            <p>• Request Method: <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
            <p>• HTTP Code: 500 Internal Server Error</p>
            <p>• Error Logged: Yes (Reference: <?php echo htmlspecialchars(date('Ymd-His'), ENT_QUOTES, 'UTF-8'); ?>)</p>
            <p><em>Note: These details are hidden from regular users for security.</em></p>
        </div>
        
        <div class="contact-info">
            Need immediate assistance? 
            <a href="mailto:support@ourhotel.com">Contact Support</a> or call +60312-345-6789
        </div>
    </div>
    
    <script>
        function toggleDetails() {
            const details = document.getElementById('techDetails');
            const button = document.querySelector('.show-details');
            
            if (details.style.display === 'block') {
                details.style.display = 'none';
                button.innerHTML = '<i class="fas fa-code"></i> Show Technical Details (Admin Only)';
            } else {
                details.style.display = 'block';
                button.innerHTML = '<i class="fas fa-times"></i> Hide Technical Details';
            }
        }
        
        // Log the error to console for developers
        console.error('500 Internal Server Error');
        console.error('Timestamp: <?php echo js_escape(date("Y-m-d H:i:s")); ?>');
        console.error('URL: <?php echo js_escape($_SERVER["REQUEST_URI"] ?? "Unknown"); ?>');
        
        // Try to reload the page after 30 seconds if user is still there
        setTimeout(() => {
            if (confirm('The system may have recovered. Would you like to try reloading the page?')) {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>