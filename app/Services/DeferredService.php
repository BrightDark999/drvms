<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

/**
 * Run heavy work after the HTTP response is flushed (notifications, webhooks, etc.).
 */
class DeferredService
{
    /** @var list<callable> */
    private static array $tasks = [];

    private static bool $registered = false;

    public static function afterResponse(callable $task): void
    {
        self::$tasks[] = $task;

        if (self::$registered) {
            return;
        }

        self::$registered = true;
        register_shutdown_function([self::class, 'run']);
    }

    public static function run(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        } else {
            @ini_set('zlib.output_compression', '0');
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            @flush();
        }

        $tasks = self::$tasks;
        self::$tasks      = [];
        self::$registered = false;

        foreach ($tasks as $task) {
            try {
                $task();
            } catch (Throwable $e) {
                log_message('error', 'Deferred task failed: ' . $e->getMessage());
            }
        }
    }
}
