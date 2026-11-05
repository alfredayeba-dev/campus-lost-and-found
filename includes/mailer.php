<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_mail($to, $subject, $html, $text){

    $user = env('MAIL_USER', '');
    $pass = env('MAIL_PASS', '');

    // No SMTP configured: write the email to a log file (local testing)
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

    $mail = new PHPMailer(true);

    try{
        $mail->isSMTP();
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