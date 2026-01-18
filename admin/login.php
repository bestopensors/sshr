<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$ip = getClientIp();
$isBanned = isIpBanned($ip);
$remainingAttempts = getRemainingLoginAttempts($ip);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!dbAvailable()) {
        $error = 'Prijava nije dostupna jer baza podataka nije povezana.';
    } elseif ($isBanned) {
        $error = 'Previše neuspješnih pokušaja. Pokušajte ponovno kasnije.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Molimo unesite korisničko ime i lozinku.';
        } elseif (attemptLogin($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $isBanned = isIpBanned($ip);
            $remainingAttempts = getRemainingLoginAttempts($ip);
            if ($isBanned) {
                $error = 'Previše neuspješnih pokušaja. Pokušajte ponovno kasnije.';
            } else {
                $suffix = $remainingAttempts === 1 ? 'Preostao je 1 pokušaj.' : 'Preostalo je ' . $remainingAttempts . ' pokušaja.';
                $error = 'Pogrešno korisničko ime ili lozinka. ' . $suffix;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Start Smart HR</title>
    <link rel="icon" type="image/png" href="../images/SSHR.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-logo p {
            color: rgba(255, 255, 255, 0.6);
            margin-top: 8px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.1);
        }

        .form-group input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.5);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>Start Smart HR</h1>
            <p>Admin Panel</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Korisničko ime</label>
                <input type="text" id="username" name="username" placeholder="Unesite korisničko ime" required autofocus <?php echo ($isBanned || !dbAvailable()) ? 'disabled' : ''; ?>>
            </div>
            
            <div class="form-group">
                <label for="password">Lozinka</label>
                <input type="password" id="password" name="password" placeholder="Unesite lozinku" required <?php echo ($isBanned || !dbAvailable()) ? 'disabled' : ''; ?>>
            </div>
            
            <button type="submit" class="btn-login" <?php echo ($isBanned || !dbAvailable()) ? 'disabled' : ''; ?>>Prijava</button>
        </form>
        
        <a href="../index.php" class="back-link">← Povratak na stranicu</a>
    </div>
</body>
</html>

