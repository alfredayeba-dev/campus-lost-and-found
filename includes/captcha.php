<?php
require_once __DIR__ . '/env.php';

// Put this in the <head> of any page that shows the widget
function turnstile_script(){
    return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
}

// Put this inside the <form>
function turnstile_widget(){
    $key = htmlspecialchars(env('TURNSTILE_SITE_KEY', ''));
    return '<div class="cf-turnstile" data-sitekey="' . $key . '" data-theme="light"></div>';
}

// Call this on the server when the form is submitted
function turnstile_verify(){

    $secret = env('TURNSTILE_SECRET_KEY', '');
    $token  = $_POST['cf-turnstile-response'] ?? '';

    // Fail closed: no key or no token means refuse
    if($secret === '' || !is_string($token) || $token === ''){
        return false;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8
    ]);

    $body = curl_exec($ch);

    if($body === false){
        error_log('Turnstile curl error: ' . curl_error($ch));
        return false;
    }

    $data = json_decode($body, true);

    return is_array($data) && !empty($data['success']);
}