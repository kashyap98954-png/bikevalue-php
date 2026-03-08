<?php
// ═══════════════════════════════════════════════
//  BikeValue — Database Configuration
//  Railway MySQL (auto-loaded from environment)
// ═══════════════════════════════════════════════

// Railway injects these environment variables automatically
// when you add a MySQL plugin to your Railway project
$host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
$port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';
$dbname   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'bikevalue';
$username = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')     ?: '';

// ML API URL — Railway will inject this once you deploy the Flask service
$ml_api_url = getenv('ML_API_URL') ?: 'http://localhost:5000';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Return JSON error if called via API, else show message
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    die('<p style="color:red;font-family:sans-serif;padding:2rem">
        ⚠ Database connection failed.<br>
        Make sure your Railway MySQL plugin is added and environment variables are set.<br>
        <small>' . htmlspecialchars($e->getMessage()) . '</small>
    </p>');
}
