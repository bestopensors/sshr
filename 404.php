<?php
/**
 * 404 Error Page
 */
http_response_code(404);
$currentLang = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'hr';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 - Stranica nije pronađena | Start Smart HR</title>
  <link rel="icon" type="image/png" href="images/SSHR.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    :root {
      --color-primary: #6366f1;
      --color-accent: #a855f7;
      --color-bg: #0f172a;
      --color-card: #1e293b;
      --color-text: #f8fafc;
      --color-text-secondary: #94a3b8;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--color-bg);
      color: var(--color-text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }
    
    /* Background effects */
    body::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle at 30% 30%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 70% 70%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
      animation: rotate 30s linear infinite;
    }
    
    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    
    .container {
      text-align: center;
      position: relative;
      z-index: 1;
      max-width: 600px;
    }
    
    .error-code {
      font-size: clamp(120px, 25vw, 200px);
      font-weight: 800;
      background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      line-height: 1;
      margin-bottom: 20px;
      text-shadow: 0 0 80px rgba(99, 102, 241, 0.5);
    }
    
    .error-title {
      font-size: clamp(24px, 5vw, 36px);
      font-weight: 700;
      margin-bottom: 16px;
    }
    
    .error-message {
      font-size: 18px;
      color: var(--color-text-secondary);
      margin-bottom: 40px;
      line-height: 1.6;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 16px 32px;
      background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
      color: white;
      text-decoration: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }
    
    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.5);
    }
    
    .btn svg {
      transition: transform 0.3s ease;
    }
    
    .btn:hover svg {
      transform: translateX(-4px);
    }
    
    .logo {
      position: absolute;
      top: 30px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 20px;
      font-weight: 700;
    }
    
    .logo-start {
      color: var(--color-text);
    }
    
    .logo-smart {
      background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    /* Floating shapes */
    .shapes {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      pointer-events: none;
      overflow: hidden;
    }
    
    .shape {
      position: absolute;
      border-radius: 50%;
      opacity: 0.1;
    }
    
    .shape-1 {
      width: 300px;
      height: 300px;
      background: var(--color-primary);
      top: 10%;
      left: -100px;
      animation: float 8s ease-in-out infinite;
    }
    
    .shape-2 {
      width: 200px;
      height: 200px;
      background: var(--color-accent);
      bottom: 10%;
      right: -50px;
      animation: float 6s ease-in-out infinite reverse;
    }
    
    .shape-3 {
      width: 150px;
      height: 150px;
      background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
      top: 50%;
      right: 20%;
      animation: float 10s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-30px) rotate(10deg); }
    }
    
    /* Light theme */
    [data-theme="light"] {
      --color-bg: #f8fafc;
      --color-card: #ffffff;
      --color-text: #1e293b;
      --color-text-secondary: #64748b;
    }
  </style>
</head>
<body>
  <div class="shapes">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
  </div>
  
  <a href="index.php" class="logo">
    <span class="logo-start">Start</span><span class="logo-smart">Smart</span>
  </a>
  
  <div class="container">
    <div class="error-code">404</div>
    <h1 class="error-title" data-translate="error-404-title">Stranica nije pronađena</h1>
    <p class="error-message" data-translate="error-404-message">
      Ups! Stranica koju tražite ne postoji ili je premještena. 
      Provjerite URL ili se vratite na početnu stranicu.
    </p>
    <a href="index.php" class="btn">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
      </svg>
      <span data-translate="error-back-home">Povratak na početnu</span>
    </a>
  </div>
  
  <script>
    // Check for saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      document.documentElement.setAttribute('data-theme', savedTheme);
    }
  </script>
</body>
</html>

