<?php
/**
 * Database Connection & Environment Configuration
 * OFBL Policy Management System
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = getenv('DB_HOST') ?: 'localhost';
$user     = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$database = getenv('DB_NAME') ?: 'ofb_policy_db';
$port     = (int)(getenv('DB_PORT') ?: 3306);

$conn = mysqli_init();
if (!$conn) {
    die("Database initialization error");
}

mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

$connected = @mysqli_real_connect($conn, $host, $user, $password, $database, $port);

if (!$connected) {
    $db_connection_error = mysqli_connect_error();
} else {
    mysqli_set_charset($conn, 'utf8mb4');
}
?>