<?php

declare(strict_types=1);

/**
 * WordPress Playground - Health Check Endpoint
 *
 * Returns a JSON response indicating the service status.
 * Used for container health checks and monitoring.
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$status = [
    'status' => 'ok',
    'service' => 'wp-playground',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
];

// Check database connection if environment variables are set
if (getenv('DB_HOST') && getenv('DB_NAME')) {
    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST'),
            getenv('DB_NAME')
        );
        $pdo = new PDO(
            $dsn,
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASS') ?: '',
            [PDO::ATTR_TIMEOUT => 5]
        );
        $status['database'] = 'connected';
    } catch (PDOException $e) {
        $status['database'] = 'disconnected';
        $status['database_error'] = $e->getMessage();
    }
}

// Check Redis connection if environment variables are set
if (getenv('REDIS_DB_HOST')) {
    $redis = @fsockopen(getenv('REDIS_DB_HOST'), 6379, $errno, $errstr, 2);
    if ($redis) {
        $status['redis'] = 'connected';
        fclose($redis);
    } else {
        $status['redis'] = 'disconnected';
    }
}

http_response_code(200);
echo json_encode($status, JSON_PRETTY_PRINT);
