<?php
/**
 * Umami Analytics API Integration
 * Optimized with caching and parallel requests
 */

class UmamiAPI {
    private $apiUrl;
    private $apiKey;
    private $websiteId;
    private $cacheDir;
    private $cacheTime = 300; // 5 minutes cache
    
    public function __construct($apiUrl, $apiKey, $websiteId) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->websiteId = $websiteId;
        $this->cacheDir = __DIR__ . '/../cache/umami/';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached data or fetch from API
     */
    private function getCached($key, $callback) {
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        // Check cache
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            if ($cacheData && (time() - $cacheData['time']) < $this->cacheTime) {
                return $cacheData['data'];
            }
        }
        
        // Fetch fresh data
        $data = $callback();
        
        // Cache it
        if ($data && !isset($data['error'])) {
            file_put_contents($cacheFile, json_encode([
                'time' => time(),
                'data' => $data
            ]));
        }
        
        return $data;
    }
    
    /**
     * Make API request to Umami
     */
    private function makeRequest($endpoint, $params = []) {
        $url = $this->apiUrl . '/' . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'x-umami-api-key: ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Reduced timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => 'Connection error: ' . $error];
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            return ['error' => 'API error (HTTP ' . $httpCode . '): ' . ($errorData['message'] ?? $response)];
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Make multiple parallel requests
     */
    private function makeParallelRequests($requests) {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];
        
        foreach ($requests as $key => $request) {
            $ch = curl_init($request['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'x-umami-api-key: ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        
        // Execute all requests
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        // Get results
        foreach ($handles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200) {
                $results[$key] = json_decode($response, true);
            } else {
                $results[$key] = ['error' => 'HTTP ' . $httpCode];
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        
        return $results;
    }
    
    /**
     * Get website stats for a date range
     */
    public function getStats($startDate, $endDate, $timezone = 'UTC') {
        $cacheKey = 'stats_' . $startDate . '_' . $endDate;
        
        return $this->getCached($cacheKey, function() use ($startDate, $endDate, $timezone) {
            $params = [
                'startAt' => strtotime($startDate) * 1000,
                'endAt' => strtotime($endDate . ' 23:59:59') * 1000,
                'timezone' => $timezone
            ];
            
            return $this->makeRequest('websites/' . $this->websiteId . '/stats', $params);
        });
    }
    
    /**
     * Get today's stats
     */
    public function getTodayStats() {
        $today = date('Y-m-d');
        return $this->getStats($today, $today);
    }
    
    /**
     * Get this week's stats
     */
    public function getThisWeekStats() {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d');
        return $this->getStats($startOfWeek, $endOfWeek);
    }
    
    /**
     * Get this month's stats
     */
    public function getThisMonthStats() {
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-d');
        return $this->getStats($startOfMonth, $endOfMonth);
    }
    
    /**
     * Get all-time stats (last 365 days)
     */
    public function getAllTimeStats() {
        $startDate = date('Y-m-d', strtotime('-365 days'));
        $endDate = date('Y-m-d');
        return $this->getStats($startDate, $endDate);
    }
    
    /**
     * Get page views and other metrics in parallel
     */
    public function getDetailedStats($startDate, $endDate) {
        $cacheKey = 'detailed_' . $startDate . '_' . $endDate;
        
        return $this->getCached($cacheKey, function() use ($startDate, $endDate) {
            $startAt = strtotime($startDate) * 1000;
            $endAt = strtotime($endDate . ' 23:59:59') * 1000;
            $baseUrl = $this->apiUrl . '/websites/' . $this->websiteId . '/pageviews';
            
            $requests = [
                'pages' => ['url' => $baseUrl . '?startAt=' . $startAt . '&endAt=' . $endAt . '&type=url'],
                'referrers' => ['url' => $baseUrl . '?startAt=' . $startAt . '&endAt=' . $endAt . '&type=referrer'],
                'countries' => ['url' => $baseUrl . '?startAt=' . $startAt . '&endAt=' . $endAt . '&type=country'],
                'browsers' => ['url' => $baseUrl . '?startAt=' . $startAt . '&endAt=' . $endAt . '&type=browser'],
                'devices' => ['url' => $baseUrl . '?startAt=' . $startAt . '&endAt=' . $endAt . '&type=device'],
                'os' => ['url' => $baseUrl . '?startAt=' . $startAt . '&endAt=' . $endAt . '&type=os']
            ];
            
            return $this->makeParallelRequests($requests);
        });
    }
    
    // Legacy methods for backward compatibility
    public function getPageViews($startDate, $endDate) {
        $detailed = $this->getDetailedStats($startDate, $endDate);
        return $detailed['pages'] ?? [];
    }
    
    public function getReferrers($startDate, $endDate) {
        $detailed = $this->getDetailedStats($startDate, $endDate);
        return $detailed['referrers'] ?? [];
    }
    
    public function getCountries($startDate, $endDate) {
        $detailed = $this->getDetailedStats($startDate, $endDate);
        return $detailed['countries'] ?? [];
    }
    
    public function getBrowsers($startDate, $endDate) {
        $detailed = $this->getDetailedStats($startDate, $endDate);
        return $detailed['browsers'] ?? [];
    }
    
    public function getDevices($startDate, $endDate) {
        $detailed = $this->getDetailedStats($startDate, $endDate);
        return $detailed['devices'] ?? [];
    }
    
    public function getOperatingSystems($startDate, $endDate) {
        $detailed = $this->getDetailedStats($startDate, $endDate);
        return $detailed['os'] ?? [];
    }
}
