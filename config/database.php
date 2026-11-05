<?php
// Read settings from the server's environment.
// The values after ?: are fallbacks for local XAMPP only.
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'campus_lost_found';

$conn = mysqli_connect($host, $user, $pass, $name);

if(!$conn){
    // Never print mysqli_connect_error() on a live site, it can leak details
    die("Database connection failed.");
}

mysqli_set_charset($conn, "utf8mb4");