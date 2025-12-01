<?php
/**
 * Umami Analytics Loader
 */
require_once __DIR__ . '/../config/analytics-alternative.php';

if (ANALYTICS_ENABLED && !empty(UMAMI_SCRIPT_URL) && !empty(UMAMI_WEBSITE_ID)) {
    echo '<!-- Umami Analytics -->' . "\n";
    echo '<script defer src="' . htmlspecialchars(UMAMI_SCRIPT_URL) . '" data-website-id="' . htmlspecialchars(UMAMI_WEBSITE_ID) . '"></script>' . "\n";
}
?>
