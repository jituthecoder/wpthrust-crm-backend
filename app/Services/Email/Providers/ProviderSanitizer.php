<?php

namespace App\Services\Email\Providers;

class ProviderSanitizer
{
    /**
     * Sanitize error message or output by removing credentials, DSN passwords, OAuth tokens, and secrets.
     */
    public static function sanitizeMessage(?string $message): string
    {
        if (empty($message)) {
            return 'An unknown error occurred during email delivery.';
        }

        // Mask DSN credentials (e.g. smtp://username:password@host:port)
        $sanitized = preg_replace(
            '/([a-z0-9+\-.]+:\/\/)([^:]+):([^@]+)@/i',
            '$1$2:***@',
            $message
        );

        // Mask Bearer tokens
        $sanitized = preg_replace(
            '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i',
            'Bearer ***',
            $sanitized
        );

        // Mask access_token, refresh_token, client_secret, password values in JSON or key-value strings
        $sanitized = preg_replace(
            '/(access_token|refresh_token|client_secret|password|secret|authorization)\s*[:=]\s*["\']?[^"\'\s,;]+["\']?/i',
            '$1: "***"',
            $sanitized
        );

        return $sanitized;
    }
}
