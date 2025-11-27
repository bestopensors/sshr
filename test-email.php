<?php
/**
 * Email Configuration Diagnostic - Delete after testing!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<html><head><title>Email Diagnostic</title><style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
pre { background: #333; color: #0f0; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>📧 Email Configuration Diagnostic</h1>";

// 1. Check PHP mail function
echo "<h2>1. PHP Configuration</h2>";
echo "<div class='box'>";
echo "<p>PHP Version: " . phpversion() . "</p>";

if (function_exists('mail')) {
    echo "<p class='pass'>✓ PHP mail() function is available</p>";
} else {
    echo "<p class='fail'>✗ PHP mail() function is NOT available</p>";
}

$sendmail = ini_get('sendmail_path');
echo "<p>Sendmail path: " . ($sendmail ? $sendmail : '<em>not set</em>') . "</p>";

$smtp = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');
echo "<p>PHP SMTP: $smtp:$smtp_port</p>";
echo "</div>";

// 2. DNS Records Check
echo "<h2>2. DNS Records for startsmarthr.eu</h2>";
echo "<div class='box'>";

$domain = 'startsmarthr.eu';

// MX Records
$mx = dns_get_record($domain, DNS_MX);
if ($mx && count($mx) > 0) {
    echo "<p class='pass'>✓ MX Records found:</p><ul>";
    foreach ($mx as $record) {
        echo "<li>{$record['target']} (priority: {$record['pri']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='fail'>✗ No MX records found - emails cannot be received!</p>";
}

// SPF Record
$txt = dns_get_record($domain, DNS_TXT);
$spf_found = false;
if ($txt) {
    foreach ($txt as $record) {
        if (strpos($record['txt'], 'v=spf1') !== false) {
            $spf_found = true;
            echo "<p class='pass'>✓ SPF Record: {$record['txt']}</p>";
        }
    }
}
if (!$spf_found) {
    echo "<p class='warn'>⚠ No SPF record found - emails may go to spam</p>";
}

// DKIM (check for common selector)
$dkim = dns_get_record('default._domainkey.' . $domain, DNS_TXT);
if ($dkim && count($dkim) > 0) {
    echo "<p class='pass'>✓ DKIM record found</p>";
} else {
    echo "<p class='warn'>⚠ No DKIM record found (checked default._domainkey) - emails may go to spam</p>";
}

echo "</div>";

// 3. SMTP Connection Test
echo "<h2>3. SMTP Connection Test</h2>";
echo "<div class='box'>";

$host = 'cp7.infonet.hr';
$port = 465;
$user = 'contact@startsmarthr.eu';
$pass = '9v3)M2pv*tY4'; // PUT YOUR PASSWORD HERE
$from = 'contact@startsmarthr.eu';

echo "<p>Host: $host</p>";
echo "<p>Port: $port</p>";
echo "<p>User: $user</p>";
echo "<p>From: $from</p>";

echo "<h3>Connection Log:</h3><pre>";

$socket = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 30);

if (!$socket) {
    echo "FAILED: $errstr ($errno)\n";
    echo "</pre><p class='fail'>✗ Cannot connect to SMTP server</p></div>";
} else {
    echo "Connected to $host:$port\n\n";
    stream_set_timeout($socket, 30);
    
    // Read greeting
    while ($line = fgets($socket, 515)) {
        echo "← $line";
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // EHLO
    fputs($socket, "EHLO startsmarthr.eu\r\n");
    echo "→ EHLO startsmarthr.eu\n";
    while ($line = fgets($socket, 515)) {
        echo "← $line";
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // AUTH
    fputs($socket, "AUTH LOGIN\r\n");
    echo "→ AUTH LOGIN\n";
    $response = fgets($socket, 515);
    echo "← $response";
    
    fputs($socket, base64_encode($user) . "\r\n");
    echo "→ [username]\n";
    $response = fgets($socket, 515);
    echo "← $response";
    
    fputs($socket, base64_encode($pass) . "\r\n");
    echo "→ [password]\n";
    $response = fgets($socket, 515);
    echo "← $response";
    
    $auth_ok = (strpos($response, '235') !== false);
    
    echo "</pre>";
    
    if ($auth_ok) {
        echo "<p class='pass'>✓ SMTP Authentication successful</p>";
    } else {
        echo "<p class='fail'>✗ SMTP Authentication failed - check username/password</p>";
    }
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
}
echo "</div>";

// 4. Test sending email
echo "<h2>4. Send Test Email</h2>";
echo "<div class='box'>";

$test_to = isset($_GET['to']) ? $_GET['to'] : '';

if ($test_to) {
    echo "<p>Sending test email to: <strong>$test_to</strong></p>";
    
    $socket = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 30);
    if ($socket) {
        stream_set_timeout($socket, 30);
        
        // Read greeting
        while ($line = fgets($socket, 515)) {
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // EHLO
        fputs($socket, "EHLO startsmarthr.eu\r\n");
        while ($line = fgets($socket, 515)) {
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // AUTH
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 515);
        fputs($socket, base64_encode($user) . "\r\n");
        fgets($socket, 515);
        fputs($socket, base64_encode($pass) . "\r\n");
        $auth = fgets($socket, 515);
        
        if (strpos($auth, '235') !== false) {
            // MAIL FROM
            fputs($socket, "MAIL FROM:<$from>\r\n");
            fgets($socket, 515);
            
            // RCPT TO
            fputs($socket, "RCPT TO:<$test_to>\r\n");
            $rcpt = fgets($socket, 515);
            
            if (strpos($rcpt, '250') !== false) {
                // DATA
                fputs($socket, "DATA\r\n");
                fgets($socket, 515);
                
                $msg = "From: Start Smart HR <$from>\r\n";
                $msg .= "To: $test_to\r\n";
                $msg .= "Subject: Test Email - " . date('Y-m-d H:i:s') . "\r\n";
                $msg .= "MIME-Version: 1.0\r\n";
                $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $msg .= "\r\n";
                $msg .= "This is a test email from startsmarthr.eu\r\n";
                $msg .= "Sent at: " . date('Y-m-d H:i:s') . "\r\n";
                $msg .= "\r\n.\r\n";
                
                fputs($socket, $msg);
                $result = fgets($socket, 515);
                
                if (strpos($result, '250') !== false) {
                    echo "<p class='pass'>✓ Email accepted by server!</p>";
                    echo "<p>Server response: $result</p>";
                    echo "<p><strong>Check your inbox AND spam folder!</strong></p>";
                } else {
                    echo "<p class='fail'>✗ Server rejected email: $result</p>";
                }
            } else {
                echo "<p class='fail'>✗ Recipient rejected: $rcpt</p>";
            }
        }
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
    }
} else {
    echo "<form method='get'>";
    echo "<p>Enter email address to send test:</p>";
    echo "<input type='email' name='to' placeholder='your@email.com' style='padding:10px;width:300px;'>";
    echo "<button type='submit' style='padding:10px 20px;'>Send Test Email</button>";
    echo "</form>";
}

echo "</div>";

// 5. Check cPanel email routing
echo "<h2>5. Recommendations</h2>";
echo "<div class='box'>";
echo "<p>If emails are not being received:</p>";
echo "<ol>";
echo "<li>In cPanel, go to <strong>Email Routing</strong> and ensure it's set to <strong>Local Mail Exchanger</strong></li>";
echo "<li>Check <strong>Email Deliverability</strong> in cPanel for any issues</li>";
echo "<li>Make sure your domain has proper <strong>SPF</strong> and <strong>DKIM</strong> records</li>";
echo "<li>Check the mail server logs in cPanel → <strong>Track Delivery</strong></li>";
echo "<li>Try sending to an external email (Gmail) to test outbound</li>";
echo "</ol>";
echo "</div>";

echo "<p style='color:red;font-weight:bold;'>⚠️ DELETE THIS FILE AFTER TESTING!</p>";
echo "</body></html>";
?>

