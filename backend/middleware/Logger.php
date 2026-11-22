<?php

// Appends info and error lines to the log files; logging must never break a request.
class Logger
{
    // Appends one INFO line to backend/logs/info.log.
    public static function info(string $message): void
    {
        self::write('info.log', 'INFO', $message);
    }

    // Appends one ERROR line to backend/logs/error.log.
    public static function error(string $message): void
    {
        self::write('error.log', 'ERROR', $message);
    }

    // Writes a timestamped line, creating the logs folder when needed; never throws.
    private static function write(string $fileName, string $level, string $message): void
    {
        try {
            $folder = __DIR__ . '/../logs';

            if (!is_dir($folder) && !@mkdir($folder, 0777, true) && !is_dir($folder)) {
                return;
            }

            $line = '[' . date('Y-m-d H:i:s') . '] ' . $level . ' ' . $message . "\n";

            @file_put_contents($folder . '/' . $fileName, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable $exception) {
            // Logging failures are ignored on purpose so they never change the response.
        }
    }
}
