<?php
/**
 * Contact Form Handler
 * Processes contact form submissions
 */

header('Content-Type: application/json');

require_once 'config/database.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$turnstileToken = $_POST['cf-turnstile-response'] ?? '';

// Validate required fields
$errors = [];

if (empty($name)) {
    $errors[] = 'Ime je obavezno';
}

if (empty($email)) {
    $errors[] = 'Email je obavezan';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email adresa nije ispravna';
}

if (empty($message)) {
    $errors[] = 'Poruka je obavezna';
}

// Verify Turnstile token
if (!empty($turnstileToken)) {
    $turnstileSecret = '0x4AAAAAACAsbam0KqzsMjxQ9thDQnn0e8U'; // Move to settings in production
    
    $verifyData = [
        'secret' => $turnstileSecret,
        'response' => $turnstileToken,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($verifyData));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!$result['success']) {
        $errors[] = 'Verifikacija nije uspjela. Molimo pokušajte ponovno.';
    }
}

// Return errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$dbSaved = false;
$emailSent = false;

// Save to database if available
if (dbAvailable()) {
    try {
        $stmt = db()->prepare("
            INSERT INTO contact_submissions (name, email, phone, message, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $name,
            $email,
            $phone,
            $message,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        $dbSaved = true;
    } catch (Exception $e) {
        error_log('Contact form DB error: ' . $e->getMessage());
    }
}

// Send email notification via SMTP
$to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'contact@startsmarthr.eu';
$subject = 'Nova poruka s web stranice - ' . $name;

$emailBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:30px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;">📧 Nova Poruka</h1>
                            <p style="color:rgba(255,255,255,0.9);margin:10px 0 0 0;font-size:14px;">Primljena s kontakt forme</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:30px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:15px;background-color:#f8f9fa;border-radius:6px;margin-bottom:15px;">
                                        <p style="margin:0 0 5px 0;font-size:12px;color:#6b7280;text-transform:uppercase;">Ime</p>
                                        <p style="margin:0;font-size:16px;color:#1f2937;font-weight:600;">' . htmlspecialchars($name) . '</p>
                                    </td>
                                </tr>
                                <tr><td style="height:15px;"></td></tr>
                                <tr>
                                    <td style="padding:15px;background-color:#f8f9fa;border-radius:6px;">
                                        <p style="margin:0 0 5px 0;font-size:12px;color:#6b7280;text-transform:uppercase;">Email</p>
                                        <p style="margin:0;font-size:16px;color:#6366f1;"><a href="mailto:' . htmlspecialchars($email) . '" style="color:#6366f1;text-decoration:none;">' . htmlspecialchars($email) . '</a></p>
                                    </td>
                                </tr>
                                <tr><td style="height:15px;"></td></tr>
                                <tr>
                                    <td style="padding:15px;background-color:#f8f9fa;border-radius:6px;">
                                        <p style="margin:0 0 5px 0;font-size:12px;color:#6b7280;text-transform:uppercase;">Telefon</p>
                                        <p style="margin:0;font-size:16px;color:#1f2937;">' . ($phone ? htmlspecialchars($phone) : '<em style="color:#9ca3af;">Nije uneseno</em>') . '</p>
                                    </td>
                                </tr>
                                <tr><td style="height:15px;"></td></tr>
                                <tr>
                                    <td style="padding:20px;background-color:#f8f9fa;border-radius:6px;border-left:4px solid #6366f1;">
                                        <p style="margin:0 0 10px 0;font-size:12px;color:#6b7280;text-transform:uppercase;">Poruka</p>
                                        <p style="margin:0;font-size:15px;color:#374151;line-height:1.6;white-space:pre-wrap;">' . htmlspecialchars($message) . '</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 30px;background-color:#f8f9fa;border-top:1px solid #e5e7eb;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:12px;color:#9ca3af;">
                                        <p style="margin:0;">IP: ' . $_SERVER['REMOTE_ADDR'] . '</p>
                                        <p style="margin:5px 0 0 0;">Vrijeme: ' . date('d.m.Y H:i:s') . '</p>
                                    </td>
                                    <td align="right">
                                        <a href="https://startsmarthr.eu/admin/messages.php" style="display:inline-block;padding:10px 20px;background-color:#6366f1;color:#ffffff;text-decoration:none;border-radius:6px;font-size:14px;">Pogledaj u adminu</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <p style="margin:20px 0 0 0;font-size:12px;color:#9ca3af;">© ' . date('Y') . ' Start Smart HR</p>
            </td>
        </tr>
    </table>
</body>
</html>';

$emailSent = sendSmtpEmail($to, $subject, $emailBody, $email, $name, true);

// Return success (message saved to DB is what matters most)
echo json_encode([
    'success' => true,
    'message' => 'Poruka uspješno poslana! Javit ćemo vam se uskoro.'
]);

/**
 * Send email via SMTP with SSL
 */
function sendSmtpEmail($to, $subject, $body, $replyToEmail, $replyToName, $isHtml = false) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $from = SMTP_FROM;
    $fromName = SMTP_FROM_NAME;
    
    try {
        // Connect with SSL
        $socket = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }
        
        stream_set_timeout($socket, 30);
        
        // Read all greeting lines
        while ($line = fgets($socket, 515)) {
            if (substr($line, 3, 1) == ' ' || substr($line, 3, 1) == '') break;
        }
        
        // EHLO
        fputs($socket, "EHLO startsmarthr.eu\r\n");
        while ($line = fgets($socket, 515)) {
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 515); // 334 Username
        
        fputs($socket, base64_encode($user) . "\r\n");
        fgets($socket, 515); // 334 Password
        
        fputs($socket, base64_encode($pass) . "\r\n");
        $response = fgets($socket, 515);
        
        if (strpos($response, '235') === false) {
            error_log("SMTP auth failed: $response");
            fclose($socket);
            return false;
        }
        
        // MAIL FROM
        fputs($socket, "MAIL FROM:<$from>\r\n");
        fgets($socket, 515);
        
        // RCPT TO
        fputs($socket, "RCPT TO:<$to>\r\n");
        fgets($socket, 515);
        
        // DATA
        fputs($socket, "DATA\r\n");
        fgets($socket, 515);
        
        // Email headers and body
        $msg = "From: $fromName <$from>\r\n";
        $msg .= "To: $to\r\n";
        $msg .= "Reply-To: $replyToName <$replyToEmail>\r\n";
        $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $msg .= "\r\n";
        $msg .= $body;
        $msg .= "\r\n.\r\n";
        
        fputs($socket, $msg);
        $response = fgets($socket, 515);
        
        $success = (strpos($response, '250') !== false);
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return $success;
        
    } catch (Exception $e) {
        error_log("SMTP error: " . $e->getMessage());
        return false;
    }
}

