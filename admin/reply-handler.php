<?php
/**
 * Reply Handler - Sends email reply and marks message as replied
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
$replyMessage = trim($_POST['reply_message'] ?? '');
$replyLanguage = isset($_POST['reply_language']) ? $_POST['reply_language'] : 'hr';
$replyGender = isset($_POST['reply_gender']) ? $_POST['reply_gender'] : 'poštovani';
$replySubject = isset($_POST['reply_subject']) ? trim($_POST['reply_subject']) : '';
$recipientName = isset($_POST['recipient_name']) ? $_POST['recipient_name'] : '';

if ($messageId <= 0 || empty($replyMessage)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Set default subject if empty
if (empty($replySubject)) {
    $replySubject = $replyLanguage === 'en' ? 'Re: Your message from the website' : 'Re: Vaša poruka s web stranice';
}

if (!dbAvailable()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not available']);
    exit;
}

/**
 * Send email via SMTP with SSL
 */
if (!function_exists('sendSmtpEmail')) {
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
}

try {
    // Get the original message
    $stmt = db()->prepare("SELECT * FROM contact_submissions WHERE id = ?");
    $stmt->execute([$messageId]);
    $msg = $stmt->fetch();
    
    if (!$msg) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Message not found']);
        exit;
    }
    
    // Send email reply
    $to = $msg['email'];
    $subject = $replySubject;
    
    // Determine greeting based on language and gender
    $greetings = [
        'hr' => [
            'poštovani' => 'Poštovani',
            'poštovana' => 'Poštovana'
        ],
        'en' => [
            'poštovani' => 'Dear',
            'poštovana' => 'Dear'
        ]
    ];
    
    $closings = [
        'hr' => [
            'text' => 'Srdačan pozdrav,',
            'footer' => 'Ova poruka je odgovor na vašu poruku poslanu'
        ],
        'en' => [
            'text' => 'Best regards,',
            'footer' => 'This message is a reply to your message sent on'
        ]
    ];
    
    $headerTexts = [
        'hr' => [
            'title' => '📧 Odgovor na vašu poruku',
            'subtitle' => 'Start Smart HR'
        ],
        'en' => [
            'title' => '📧 Reply to your message',
            'subtitle' => 'Start Smart HR'
        ]
    ];
    
    $greeting = $greetings[$replyLanguage][$replyGender] ?? $greetings['hr']['poštovani'];
    $closing = $closings[$replyLanguage] ?? $closings['hr'];
    $header = $headerTexts[$replyLanguage] ?? $headerTexts['hr'];
    $recipientNameDisplay = $recipientName ?: $msg['name'];
    
    // Format date based on language
    if ($replyLanguage === 'en') {
        $formattedDate = date('F j, Y \a\t g:i A', strtotime($msg['created_at']));
    } else {
        $formattedDate = date('d.m.Y H:i', strtotime($msg['created_at']));
    }
    
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
                            <h1 style="color:#ffffff;margin:0;font-size:24px;">' . htmlspecialchars($header['title']) . '</h1>
                            <p style="color:rgba(255,255,255,0.9);margin:10px 0 0 0;font-size:14px;">' . htmlspecialchars($header['subtitle']) . '</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:30px;">
                            <p style="margin:0 0 20px 0;font-size:16px;color:#374151;line-height:1.6;">' . htmlspecialchars($greeting) . ' ' . htmlspecialchars($recipientNameDisplay) . ',</p>
                            
                            <div style="padding:20px;background-color:#f8f9fa;border-radius:6px;border-left:4px solid #6366f1;margin-bottom:20px;">
                                <p style="margin:0;font-size:15px;color:#374151;line-height:1.6;white-space:pre-wrap;">' . nl2br(htmlspecialchars($replyMessage)) . '</p>
                            </div>
                            
                            <p style="margin:20px 0 0 0;font-size:14px;color:#6b7280;line-height:1.6;">
                                ' . htmlspecialchars($closing['text']) . '<br>
                                <strong>Start Smart HR</strong>
                            </p>
                            
                            <!-- Company Information -->
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <h3 style="margin: 0 0 10px 0; color: #6366f1; font-size: 18px;">Start Smart HR</h3>
                                    <p style="margin: 0; color: #6b7280; font-size: 12px;">Start Smart, zajednički obrt za izradu i optimizaciju web stranica, vl. Mihael Kovačić i Roko Nevistić</p>
                                </div>
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <p style="margin: 5px 0; color: #374151; font-size: 13px;"><strong>Email:</strong> <a href="mailto:contact@startsmarthr.eu" style="color: #6366f1; text-decoration: none;">contact@startsmarthr.eu</a></p>
                                    <p style="margin: 5px 0; color: #374151; font-size: 13px;"><strong>Telefon:</strong> <a href="tel:+385996105673" style="color: #6366f1; text-decoration: none;">+385 99 610 5673</a> | <a href="tel:+385958374220" style="color: #6366f1; text-decoration: none;">+385 95 837 4220</a></p>
                                    <p style="margin: 5px 0; color: #374151; font-size: 13px;"><strong>Adresa:</strong> Seljine Brigade 72, Velika Gorica, Hrvatska</p>
                                </div>
                                <div style="text-align: center;">
                                    <div style="display: inline-block;">
                                        <a href="https://www.facebook.com/people/Start-Smart-HR/61581505773838/" target="_blank" style="color: #6366f1; text-decoration: none; display: inline-block; margin: 0 10px; vertical-align: middle;">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                            <span style="font-size: 12px; vertical-align: middle;">Facebook</span>
                                        </a>
                                        <a href="https://www.instagram.com/startsmarthr.eu/" target="_blank" style="color: #6366f1; text-decoration: none; display: inline-block; margin: 0 10px; vertical-align: middle;">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 5px;">
                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                            </svg>
                                            <span style="font-size: 12px; vertical-align: middle;">Instagram</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 30px;background-color:#f8f9fa;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
                                ' . htmlspecialchars($closing['footer']) . ' ' . $formattedDate . '
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:20px 0 0 0;font-size:12px;color:#9ca3af;text-align:center;">© ' . date('Y') . ' Start Smart HR</p>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    $emailSent = sendSmtpEmail($to, $subject, $emailBody, SMTP_FROM, SMTP_FROM_NAME, true);
    
    if ($emailSent) {
        // Mark as replied in database
        // First, check if is_replied column exists, if not, add it
        try {
            $stmt = db()->prepare("UPDATE contact_submissions SET is_replied = 1 WHERE id = ?");
            $stmt->execute([$messageId]);
        } catch (Exception $e) {
            // Column might not exist, try to add it
            try {
                db()->exec("ALTER TABLE contact_submissions ADD COLUMN is_replied TINYINT(1) DEFAULT 0");
                $stmt = db()->prepare("UPDATE contact_submissions SET is_replied = 1 WHERE id = ?");
                $stmt->execute([$messageId]);
            } catch (Exception $e2) {
                error_log("Failed to add is_replied column: " . $e2->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Odgovor uspješno poslan!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Greška pri slanju emaila. Molimo pokušajte ponovno.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Reply handler error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri obradi zahtjeva.'
    ]);
}

