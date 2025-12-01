<?php
/**
 * Admin - Analytics Dashboard
 * Optimized for fast loading
 */
$pageTitle = 'Analitika';
require_once 'includes/header.php';

require_once __DIR__ . '/../config/analytics-alternative.php';
require_once __DIR__ . '/../includes/umami-api.php';

// Initialize variables
$umamiConfigured = (!empty(UMAMI_SCRIPT_URL) && !empty(UMAMI_WEBSITE_ID));
$apiConfigured = (UMAMI_API_ENABLED && !empty(UMAMI_API_KEY) && !empty(UMAMI_WEBSITE_ID));
$apiError = null;

// Stats data
$todayStats = null;
$weekStats = null;
$monthStats = null;
$allTimeStats = null;
$detailedStats = null;

// Fetch stats if API is configured - optimized with parallel requests
if ($apiConfigured) {
    try {
        $umami = new UmamiAPI(UMAMI_API_URL, UMAMI_API_KEY, UMAMI_WEBSITE_ID);
        
        // Get basic stats in parallel (cached)
        $todayStats = $umami->getTodayStats();
        $weekStats = $umami->getThisWeekStats();
        $monthStats = $umami->getThisMonthStats();
        $allTimeStats = $umami->getAllTimeStats();
        
        // Get detailed stats in one parallel call (cached)
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-d');
        $detailedStats = $umami->getDetailedStats($startOfMonth, $endOfMonth);
        
        // Check for errors
        if (isset($todayStats['error'])) {
            $apiError = $todayStats['error'];
        }
        
    } catch (Exception $e) {
        $apiError = $e->getMessage();
    }
}

// Helper function to format numbers
function formatNumber($num) {
    if ($num === null || $num === '') return '0';
    return number_format((int)$num);
}

// Helper function to get value from stats array
function getStatValue($stats, $key) {
    if (isset($stats['error'])) return 0;
    return $stats[$key] ?? 0;
}

// Extract detailed stats
$topPages = $detailedStats['pages'] ?? [];
$topReferrers = $detailedStats['referrers'] ?? [];
$topCountries = $detailedStats['countries'] ?? [];
$topBrowsers = $detailedStats['browsers'] ?? [];
$topDevices = $detailedStats['devices'] ?? [];
$topOS = $detailedStats['os'] ?? [];
?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Umami Analytics - Status</h2>
    </div>
    <div class="card__body">
        <?php if ($umamiConfigured): ?>
            <div class="alert alert--success" id="umami-status-alert">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-7.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <strong>Umami Analytics je aktiviran</strong>
                    <p style="margin-top: 8px; color: var(--text-secondary);">
                        Website ID: <code><?php echo htmlspecialchars(UMAMI_WEBSITE_ID); ?></code>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert--warning">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <strong>Umami Analytics nije konfiguriran</strong>
                    <p style="margin-top: 8px; color: var(--text-secondary);">
                        Molimo konfigurirajte Umami Analytics u datoteci <code>config/analytics-alternative.php</code>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($apiError): ?>
            <div class="alert alert--error" style="margin-top: 16px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <strong>Greška pri dohvaćanju podataka</strong>
                    <p style="margin-top: 8px; color: var(--text-secondary);">
                        <?php echo htmlspecialchars($apiError); ?>
                    </p>
                </div>
            </div>
        <?php elseif (!$apiConfigured && $umamiConfigured): ?>
            <div class="alert alert--info" style="margin-top: 16px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    <line x1="12" y1="12" x2="12" y2="8"></line>
                </svg>
                <div>
                    <strong>API integracija nije konfigurirana</strong>
                    <p style="margin-top: 8px; color: var(--text-secondary);">
                        Za prikaz statistika u admin panelu, konfigurirajte Umami API ključ u <code>config/analytics-alternative.php</code>.
                        <br>Idite na Umami Dashboard > Settings > API Keys > Create key
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($apiConfigured && !$apiError): ?>
<!-- Statistics Overview -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Pregled statistika</h2>
    </div>
    <div class="card__body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <!-- Today -->
            <div style="padding: 20px; background: var(--bg-input); border-radius: 8px; text-align: center; border: 2px solid var(--primary);">
                <div style="font-size: 32px; font-weight: 700; color: var(--primary); margin-bottom: 8px;">
                    <?php echo formatNumber(getStatValue($todayStats, 'pageviews')); ?>
                </div>
                <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Pregleda danas</div>
                <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">
                    <?php echo formatNumber(getStatValue($todayStats, 'visitors')); ?> posjetitelja
                </div>
            </div>
            
            <!-- This Week -->
            <div style="padding: 20px; background: var(--bg-input); border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: var(--primary); margin-bottom: 8px;">
                    <?php echo formatNumber(getStatValue($weekStats, 'pageviews')); ?>
                </div>
                <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Pregleda ovaj tjedan</div>
                <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">
                    <?php echo formatNumber(getStatValue($weekStats, 'visitors')); ?> posjetitelja
                </div>
            </div>
            
            <!-- This Month -->
            <div style="padding: 20px; background: var(--bg-input); border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: var(--primary); margin-bottom: 8px;">
                    <?php echo formatNumber(getStatValue($monthStats, 'pageviews')); ?>
                </div>
                <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Pregleda ovaj mjesec</div>
                <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">
                    <?php echo formatNumber(getStatValue($monthStats, 'visitors')); ?> posjetitelja
                </div>
            </div>
            
            <!-- All Time (365 days) -->
            <div style="padding: 20px; background: var(--bg-input); border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: var(--primary); margin-bottom: 8px;">
                    <?php echo formatNumber(getStatValue($allTimeStats, 'pageviews')); ?>
                </div>
                <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Pregleda (365 dana)</div>
                <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">
                    <?php echo formatNumber(getStatValue($allTimeStats, 'visitors')); ?> posjetitelja
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Pages -->
<?php if (!empty($topPages) && !isset($topPages['error']) && is_array($topPages)): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Najpopularnije stranice (ovaj mjesec)</h2>
    </div>
    <div class="card__body">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <th style="text-align: left; padding: 12px; color: var(--text-secondary); font-weight: 500;">Stranica</th>
                        <th style="text-align: right; padding: 12px; color: var(--text-secondary); font-weight: 500;">Pregleda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    foreach ($topPages as $page): 
                        if ($count >= 10) break;
                        if (!isset($page['x']) || !isset($page['y'])) continue;
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 12px; color: var(--text-primary);">
                            <code style="background: var(--bg-input); padding: 4px 8px; border-radius: 4px; font-size: 13px;">
                                <?php echo htmlspecialchars($page['x']); ?>
                            </code>
                        </td>
                        <td style="text-align: right; padding: 12px; color: var(--text-primary); font-weight: 600;">
                            <?php echo formatNumber($page['y']); ?>
                        </td>
                    </tr>
                    <?php 
                        $count++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Top Referrers -->
<?php if (!empty($topReferrers) && !isset($topReferrers['error']) && is_array($topReferrers)): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Top referreri (ovaj mjesec)</h2>
    </div>
    <div class="card__body">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <th style="text-align: left; padding: 12px; color: var(--text-secondary); font-weight: 500;">Izvor</th>
                        <th style="text-align: right; padding: 12px; color: var(--text-secondary); font-weight: 500;">Pregleda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    foreach ($topReferrers as $ref): 
                        if ($count >= 10) break;
                        if (!isset($ref['x']) || !isset($ref['y'])) continue;
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 12px; color: var(--text-primary);">
                            <?php echo htmlspecialchars($ref['x'] ?: 'Direktni pristup'); ?>
                        </td>
                        <td style="text-align: right; padding: 12px; color: var(--text-primary); font-weight: 600;">
                            <?php echo formatNumber($ref['y']); ?>
                        </td>
                    </tr>
                    <?php 
                        $count++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Top Countries -->
<?php if (!empty($topCountries) && !isset($topCountries['error']) && is_array($topCountries)): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Top zemlje (ovaj mjesec)</h2>
    </div>
    <div class="card__body">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">
            <?php 
            $count = 0;
            foreach ($topCountries as $country): 
                if ($count >= 12) break;
                if (!isset($country['x']) || !isset($country['y'])) continue;
            ?>
            <div style="padding: 12px; background: var(--bg-input); border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($country['x']); ?></span>
                <span style="color: var(--primary); font-weight: 600;"><?php echo formatNumber($country['y']); ?></span>
            </div>
            <?php 
                $count++;
            endforeach; 
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Browsers & Devices -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
    <?php if (!empty($topBrowsers) && !isset($topBrowsers['error']) && is_array($topBrowsers)): ?>
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Preglednici (ovaj mjesec)</h2>
        </div>
        <div class="card__body">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php 
                $count = 0;
                foreach ($topBrowsers as $browser): 
                    if ($count >= 8) break;
                    if (!isset($browser['x']) || !isset($browser['y'])) continue;
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-primary);"><?php echo htmlspecialchars($browser['x']); ?></span>
                    <span style="color: var(--primary); font-weight: 600;"><?php echo formatNumber($browser['y']); ?></span>
                </div>
                <?php 
                    $count++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($topDevices) && !isset($topDevices['error']) && is_array($topDevices)): ?>
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Uređaji (ovaj mjesec)</h2>
        </div>
        <div class="card__body">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php 
                $count = 0;
                foreach ($topDevices as $device): 
                    if ($count >= 8) break;
                    if (!isset($device['x']) || !isset($device['y'])) continue;
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-primary);"><?php echo htmlspecialchars($device['x']); ?></span>
                    <span style="color: var(--primary); font-weight: 600;"><?php echo formatNumber($device['y']); ?></span>
                </div>
                <?php 
                    $count++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($topOS) && !isset($topOS['error']) && is_array($topOS)): ?>
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Operacijski sustavi (ovaj mjesec)</h2>
        </div>
        <div class="card__body">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php 
                $count = 0;
                foreach ($topOS as $os): 
                    if ($count >= 8) break;
                    if (!isset($os['x']) || !isset($os['y'])) continue;
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--text-primary);"><?php echo htmlspecialchars($os['x']); ?></span>
                    <span style="color: var(--primary); font-weight: 600;"><?php echo formatNumber($os['y']); ?></span>
                </div>
                <?php 
                    $count++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Pristup Umami Dashboardu</h2>
    </div>
    <div class="card__body">
        <p style="color: var(--text-secondary); margin-bottom: 20px;">
            Za detaljne analitike i izvještaje, pristupite vašem Umami dashboardu:
        </p>
        <a href="https://cloud.umami.is" target="_blank" class="btn btn--primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            Otvori Umami Dashboard
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
