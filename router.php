<?php
/**
 * Router for PHP Built-in Server
 * Use: php -S localhost:8000 router.php
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Normalize path
$path = '/' . trim($path, '/');

// List of valid pages
$validPages = [
    '/' => '/index.php',
    '/index' => '/index.php',
    '/index.php' => '/index.php',
    '/about' => '/about.php',
    '/about.php' => '/about.php',
    '/privacy' => '/privacy.php',
    '/privacy.php' => '/privacy.php',
    '/terms' => '/terms.php',
    '/terms.php' => '/terms.php',
    '/contact-handler.php' => '/contact-handler.php',
    '/404' => '/404.php',
    '/404.php' => '/404.php',
];

// Check for static files (css, js, images, etc.)
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if (in_array($ext, $staticExtensions)) {
    $filePath = __DIR__ . $path;
    if (file_exists($filePath)) {
        return false; // Let PHP serve the static file
    }
    // Static file not found - still 404
    include __DIR__ . '/404.php';
    return true;
}

// Check for admin pages
if (strpos($path, '/admin') === 0) {
    $adminPath = $path;
    
    // Handle /admin or /admin/
    if ($adminPath === '/admin' || $adminPath === '/admin/') {
        include __DIR__ . '/admin/index.php';
        return true;
    }
    
    // Try exact match
    $filePath = __DIR__ . $adminPath;
    if (file_exists($filePath) && is_file($filePath)) {
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
            include $filePath;
            return true;
        }
        return false;
    }
    
    // Try adding .php
    $phpFile = __DIR__ . rtrim($adminPath, '/') . '.php';
    if (file_exists($phpFile)) {
        include $phpFile;
        return true;
    }
    
    // Admin 404
    include __DIR__ . '/404.php';
    return true;
}

// Check valid pages
if (isset($validPages[$path])) {
    include __DIR__ . $validPages[$path];
    return true;
}

// Handle .html to .php redirect
if (substr($path, -5) === '.html') {
    $phpPath = substr($path, 0, -5) . '.php';
    if (isset($validPages[$phpPath])) {
        header('Location: ' . $phpPath, true, 301);
        return true;
    }
}

// Check if it's a directory with index
$dirPath = __DIR__ . $path;
if (is_dir($dirPath)) {
    $indexFile = rtrim($dirPath, '/') . '/index.php';
    if (file_exists($indexFile)) {
        include $indexFile;
        return true;
    }
}

// Nothing matched - 404
http_response_code(404);
include __DIR__ . '/404.php';
return true;
