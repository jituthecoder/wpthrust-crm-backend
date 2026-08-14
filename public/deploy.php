<?php

/**
 * Hostinger Standalone Auto-Deployment Webhook
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $secretToken = 'wpthrust_crm_deploy_secret_2026';
    $providedToken = (string) ($_GET['secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '');

    if (empty($providedToken) || $secretToken !== $providedToken) {
        header('Content-Type: application/json', true, 403);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized deploy secret.',
        ]);
        exit;
    }

    $baseDir = dirname(__DIR__);
    @chdir($baseDir);

    $gitOutput = [];
    $gitCode = 0;
    if (function_exists('exec')) {
        @exec('git pull origin main 2>&1', $gitOutput, $gitCode);
    } else {
        $gitOutput[] = 'exec() is disabled in PHP configuration.';
    }

    $migrateOutput = [];
    $migrateCode = 0;
    if (function_exists('exec')) {
        @exec('/opt/alt/php83/usr/bin/php artisan migrate --force 2>&1', $migrateOutput, $migrateCode);
        @exec('/opt/alt/php83/usr/bin/php artisan config:clear 2>&1');
        @exec('/opt/alt/php83/usr/bin/php artisan cache:clear 2>&1');
    }

    header('Content-Type: application/json', true, 200);
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'git' => implode("\n", $gitOutput),
        'migrate' => implode("\n", $migrateOutput),
    ]);
} catch (\Throwable $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
