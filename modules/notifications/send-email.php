<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['sent' => false, 'message' => 'Invalid request method'], 405);
}

function buildEmailHtml(string $subject, string $messageContent, string $toName): string {
    $appName = APP_NAME;
    $year    = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body { margin:0; padding:0; background:#f4f4f4; font-family:'Segoe UI',Arial,sans-serif; }
  .wrapper { max-width:600px; margin:0 auto; padding:20px; }
  .header { background:#1a237e; padding:25px 30px; text-align:center; border-radius:8px 8px 0 0; }
  .header h1 { color:#fff; margin:0; font-size:22px; font-weight:600; }
  .content { background:#fff; padding:30px; border-radius:0 0 8px 8px; }
  .footer { text-align:center; padding:20px; color:#888; font-size:12px; }
  .footer a { color:#1a237e; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
<div class="header"><h1>{$appName}</h1></div>
<div class="content">
<p>Dear <strong>{$toName}</strong>,</p>
{$messageContent}
</div>
<div class="footer">
<p>&copy; {$year} {$appName}. All rights reserved.</p>
</div>
</div>
</body>
</html>
HTML;
}

try {
    $db = getDB();

    $to_email = trim($_POST['to_email'] ?? '');
    $to_name  = trim($_POST['to_name'] ?? '');
    $subject  = trim($_POST['subject'] ?? '');
    $message  = $_POST['message'] ?? '';
    $attachments = $_POST['attachments'] ?? [];

    if (empty($to_email) || empty($subject) || empty($message)) {
        jsonResponse(['sent' => false, 'message' => 'To email, subject, and message are required']);
    }

    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['sent' => false, 'message' => 'Invalid recipient email address']);
    }

    $htmlBody = buildEmailHtml($subject, $message, $to_name);

    $boundary = md5(uniqid(time()));
    $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $message));

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
    $body .= $plainText . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--{$boundary}--";

    $sent = mail($to_email, $subject, $body, $headers);

    if ($sent) {
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_type, reference_id) VALUES (?, ?, ?, 'email', ?, ?)");
        $notifStmt->execute([
            $_SESSION['user_id'] ?? 0,
            $subject,
            $message,
            'email_send',
            0,
        ]);

        jsonResponse(['sent' => true, 'message' => 'Email sent successfully']);
    } else {
        jsonResponse(['sent' => false, 'message' => 'Failed to send email. Check mail server configuration.']);
    }
} catch (PDOException $e) {
    error_log('Send email error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Send email error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'An unexpected error occurred'], 500);
}
