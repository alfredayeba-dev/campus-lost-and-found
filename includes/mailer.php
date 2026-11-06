<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Three modes, chosen by what's set in .env:
//   BREVO_API_KEY set     -> Brevo HTTPS API (use this on Render)
//   MAIL_USER/PASS set    -> SMTP via PHPMailer (works on XAMPP)
//   neither               -> write the email to logs/mail.log
function send_mail($to, $subject, $html, $text){

    // ---------- Mode 1: Brevo HTTPS API ----------
    $apiKey = env('BREVO_API_KEY', '');

    if($apiKey !== ''){

        $fromEmail = env('MAIL_FROM', '');

        if($fromEmail === ''){
            error_log('Mail error: MAIL_FROM must be your verified Brevo sender.');
            return false;
        }

        $payload = [
            'sender'      => ['name' => env('MAIL_FROM_NAME', 'Campus Lost and Found'), 'email' => $fromEmail],
            'to'          => [['email' => $to]],
            'subject'     => $subject,
            'htmlContent' => $html,
            'textContent' => $text
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if($body === false){
            error_log('Brevo curl error: ' . curl_error($ch));
            return false;
        }

        if($code >= 200 && $code < 300){
            return true;
        }

        error_log("Brevo error $code: $body");
        return false;
    }

    // ---------- Mode 3: no email configured, log it ----------
    $user = env('MAIL_USER', '');
    $pass = env('MAIL_PASS', '');

    if($user === '' || $pass === ''){

        $dir = __DIR__ . '/../logs';

        if(!is_dir($dir)){
            mkdir($dir, 0755, true);
            file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }

        file_put_contents(
            $dir . '/mail.log',
            "[" . date('c') . "]\nTo: $to\nSubject: $subject\n$text\n----------\n",
            FILE_APPEND
        );

        return true;
    }

    // ---------- Mode 2: SMTP ----------
    $mail = new PHPMailer(true);

    try{
        $mail->isSMTP();

        if(env('MAIL_DEBUG', '0') === '1'){
            $mail->SMTPDebug   = 2;
            $mail->Debugoutput = function($str){
                error_log('SMTP: ' . trim($str));
            };
        }

        $mail->Host       = env('MAIL_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) env('MAIL_PORT', 587);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(env('MAIL_FROM', $user) ?: $user, env('MAIL_FROM_NAME', 'Campus Lost and Found'));
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;

        $mail->send();
        return true;

    }catch(Exception $e){
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}