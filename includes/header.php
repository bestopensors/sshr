<?php
/**
 * Header Include
 * Contains the HTML head and navigation
 */

// Get current language
$currentLang = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'hr';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Primary Meta Tags -->
  <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Start Smart HR - Profesionalna izrada web stranica u Zagrebu. Moderan web dizajn, brza izrada, SEO optimizacija.'; ?>">
  <meta name="keywords" content="Start Smart HR, web dizajn Zagreb, izrada web stranica, web development Hrvatska, SEO optimizacija, responzivni web dizajn">
  <meta name="author" content="Start Smart HR">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="https://startsmarthr.eu/<?php echo basename($_SERVER['PHP_SELF']); ?>">
  
  <title><?php echo isset($pageTitle) ? $pageTitle . ' | Start Smart HR - Web Dizajn Zagreb' : 'Start Smart HR - Profesionalni Web Dizajn Zagreb'; ?></title>
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://startsmarthr.eu/<?php echo basename($_SERVER['PHP_SELF']); ?>">
  <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' | Start Smart HR' : 'Start Smart HR'; ?>">
  <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Start Smart HR - Profesionalna izrada web stranica u Zagrebu.'; ?>">
  <meta property="og:image" content="https://startsmarthr.eu/images/first.png">
  <meta property="og:site_name" content="Start Smart HR">
  
  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' | Start Smart HR' : 'Start Smart HR'; ?>">
  <meta property="twitter:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Start Smart HR - Profesionalna izrada web stranica u Zagrebu.'; ?>">
  <meta property="twitter:image" content="https://startsmarthr.eu/images/first.png">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="images/SSHR.png">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Styles -->
  <link rel="stylesheet" href="style.css">
  
  <!-- Cloudflare Turnstile -->
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  
  <!-- Analytics -->
  <?php require_once __DIR__ . '/analytics-loader.php'; ?>
</head>
<body>
  <!-- Navigation -->
  <nav class="nav">
    <div class="nav__container">
      <a href="index.php" class="nav__logo">
        <span class="nav__logo-text">
          <span class="nav__logo-start">Start</span>
          <span class="nav__logo-smart">Smart</span>
        </span>
      </a>

      <div class="nav__menu" id="nav-menu">
        <div class="nav__mobile-header">
          <span class="nav__mobile-title">Menu</span>
          <button class="nav__close" id="nav-close" aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        
        <ul class="nav__list">
          <li class="nav__item">
            <a href="index.php#hero" class="nav__link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" data-translate="nav-home">Početna</a>
          </li>
          <li class="nav__item">
            <a href="index.php#packages" class="nav__link" data-translate="nav-packages">Paketi</a>
          </li>
          <li class="nav__item">
            <a href="about.php" class="nav__link <?php echo $currentPage === 'about' ? 'active' : ''; ?>" data-translate="nav-about">O nama</a>
          </li>
          <li class="nav__item">
            <a href="index.php#contact" class="nav__link" data-translate="nav-contact">Kontakt</a>
          </li>
        </ul>
      </div>

      <div class="nav__actions">
        <!-- Theme Toggle -->
        <button class="theme-toggle" id="theme-toggle" aria-label="Toggle theme">
          <svg class="theme-toggle__icon theme-toggle__icon--sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
          </svg>
          <svg class="theme-toggle__icon theme-toggle__icon--moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
        </button>

        <!-- Language Toggle -->
        <button class="nav__lang" id="lang-toggle" aria-label="Toggle language">
          <img src="images/<?php echo $currentLang === 'hr' ? 'croatian' : 'english'; ?>-flag.png" alt="<?php echo $currentLang === 'hr' ? 'Croatian' : 'English'; ?>" class="nav__lang-flag" id="lang-flag">
        </button>

        <!-- Mobile Menu Toggle -->
        <button class="nav__toggle" id="nav-toggle" aria-label="Toggle menu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>
  </nav>

