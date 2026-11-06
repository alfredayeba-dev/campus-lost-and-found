<?php
require_once __DIR__ . '/../includes/env.php';

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$name = env('DB_NAME', 'campus_lost_found');
$port = (int) env('DB_PORT', 3306);
$ssl  = env('DB_SSL', '0') === '1';

$conn = mysqli_init();

if($ssl){

    // Use DB_CA if you set it, otherwise look in the usual places
    $ca = env('DB_CA', '');

    if($ca === ''){
        $candidates = [
            '/etc/ssl/certs/ca-certificates.crt',        // Linux / Docker / Render
            'C:/xampp/apache/bin/curl-ca-bundle.crt',    // XAMPP on Windows
            'C:/xampp/php/extras/ssl/cacert.pem'         // XAMPP on Windows (other versions)
        ];

        foreach($candidates as $path){
            if(is_file($path)){
                $ca = $path;
                break;
            }
        }
    }

    mysqli_ssl_set($conn, null, null, ($ca !== '' ? $ca : null), null, null);
}

$connected = false;

try{
    $connected = mysqli_real_connect(
        $conn, $host, $user, $pass, $name, $port, null,
        $ssl ? MYSQLI_CLIENT_SSL : 0
    );
}catch(mysqli_sql_exception $e){
    error_log('DB connection failed: ' . $e->getMessage());
}

if(!$connected){
    http_response_code(500);
    die("Database connection failed.");
}

mysqli_set_charset($conn, "utf8mb4");