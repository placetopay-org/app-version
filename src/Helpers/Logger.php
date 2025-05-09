<?php

namespace PlacetoPay\AppVersion\Helpers;

use Illuminate\Support\Facades\Log;

class Logger
{
    private static function log(string $status, string $message, string $level, array $context): void
    {
        $formattedMessage = "[$status - app-version] $message";

        Log::log($level, $formattedMessage, $context);
    }

    public static function success(string $message, ?array $context = []): void
    {
        self::log('SUCCESS', $message, 'info', $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, 'error', $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, 'warning', $context);
    }
}
