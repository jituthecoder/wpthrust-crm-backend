<?php

/**
 * Hostinger Standalone Auto-Deployment Webhook
 */

$secretToken = 'wpthrust_crm_deploy_secret_2026';

$providedToken = $_GET['secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '';

if (empty($providedToken) || !hash_equals($secretToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized deploy secret.',
    ]);
    exit;
}

$baseDir = dirname(__DIR__);
chdir($baseDir);

$gitOutput = [];
$gitCode = 0;
exec('git pull origin main 2>&1', $gitOutput, $gitCode);

$migrateOutput = [];
$migrateCode = 0;
exec('/opt/alt/php83/usr/bin/php artisan migrate --force 2>&1', $migrateOutput, $migrateCode);

$cacheOutput = [];
exec('/opt/alt/php83/usr/bin/php artisan config:clear 2>&1', $cacheOutput);
exec('/opt/alt/php83/usr/bin/php artisan cache:clear 2>&1', $cacheOutput);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'git' => implode("\n", $gitOutput),
    'migrate' => implode("\n", $migrateOutput),
]);
