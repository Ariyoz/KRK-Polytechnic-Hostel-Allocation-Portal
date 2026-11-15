<?php
// ============================================================
// KRK Polytechnic Hostel Portal — Email Utility (PHPMailer)
// ============================================================

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── SMTP CREDENTIALS ─────────────────────────────────────────
// Use a Gmail account (or any SMTP provider).
// For Gmail: enable 2FA on your Google account, then generate
// an App Password at https://myaccount.google.com/apppasswords
// and paste it as MAIL_PASS below.
// ─────────────────────────────────────────────────────────────
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'your_email@gmail.com');   // ← change this
define('MAIL_PASS',       'your_app_password');       // ← change this
define('MAIL_FROM_EMAIL', 'your_email@gmail.com');   // ← change this
define('MAIL_FROM_NAME',  'KRK Polytechnic Portal');


function sendEmail(string $to, string $subject, string $htmlMessage): bool
{
    global $conn;

    // ── Log to database first (always, even if mail fails) ──
    if (isset($conn)) {
        $stmt = $conn->prepare(
            "INSERT INTO email_logs (recipient_email, subject, message) VALUES (?, ?, ?)"
        );
        if ($stmt) {
            $stmt->bind_param("sss", $to, $subject, $htmlMessage);
            $stmt->execute();
            $stmt->close();
        }
    }

    // ── Send via PHPMailer ───────────────────────────────────
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPDebug  = SMTP::DEBUG_OFF; // change to DEBUG_SERVER to see SMTP logs

        // Sender & recipient
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlMessage;
        $mail->AltBody = strip_tags($htmlMessage);

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log failure silently — do not crash the page
        error_log("Mailer error to $to: " . $mail->ErrorInfo);
        return false;
    }
}


function emailTemplate(string $title, string $body): string
{
    $year = date('Y');
    return "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .hdr{background:#052e16;padding:22px 30px}
  .hdr h2{margin:0;color:#fff;font-size:19px}
  .hdr p{margin:4px 0 0;color:rgba(255,255,255,.55);font-size:13px}
  .bdy{padding:30px;color:#333;line-height:1.65}
  .bdy h3{color:#052e16;margin-bottom:12px}
  .ftr{background:#f0f0f0;padding:14px 30px;font-size:12px;color:#888;text-align:center;border-top:1px solid #e0e0e0}
  .btn{display:inline-block;padding:11px 26px;background:#059669;color:#fff;
       text-decoration:none;border-radius:6px;margin-top:16px;font-weight:bold;font-size:14px}
</style>
</head>
<body>
  <div class='wrap'>
    <div class='hdr'>
      <h2>KRK Polytechnic &mdash; Hostel Portal</h2>
      <p>Official Student Accommodation System</p>
    </div>
    <div class='bdy'>
      <h3>$title</h3>
      $body
    </div>
    <div class='ftr'>&copy; $year KRK Polytechnic, Lagos. All rights reserved.</div>
  </div>
</body>
</html>";
}
