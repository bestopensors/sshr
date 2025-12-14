<?php
/**
 * Sitemap Handler
 * Serves sitemap.xml with proper content-type headers
 */

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

// Prevent caching issues
header('Cache-Control: public, max-age=3600');

// Read and output the sitemap
$sitemapFile = __DIR__ . '/sitemap.xml';
if (file_exists($sitemapFile)) {
    readfile($sitemapFile);
} else {
    http_response_code(404);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>Sitemap not found</error>';
}
exit;

