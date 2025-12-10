<?php

namespace App\Services;

class UrlValidator
{
    /**
     * Validate and sanitize URL to prevent SSRF attacks
     */
    public static function validate(string $url): bool
    {
        // Check if URL is valid format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);

        // Only allow HTTP and HTTPS protocols
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            return false;
        }

        // Block private/internal IP addresses
        $host = $parsed['host'] ?? '';
        if (self::isPrivateIp($host)) {
            return false;
        }

        return true;
    }

    /**
     * Check if host is a private/internal IP address
     */
    private static function isPrivateIp(string $host): bool
    {
        // Resolve hostname to IP if needed
        $ip = gethostbyname($host);
        
        if ($ip === $host) {
            // If resolution failed, check if it's already an IP
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return false;
            }
        }

        // Check for private IP ranges
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}

