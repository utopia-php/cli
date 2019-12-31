<?php

namespace Utopia\CLI;

class Console
{
    /**
     * Log
     *
     * Log messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function log(string $message)
    {
        return fwrite(STDOUT, $message . "\n");
    }

    /**
     * Success
     *
     * Log success messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function success(string $message)
    {
        return fwrite(STDERR, "\033[32m" . $message . "\033[0m\n");
    }

    /**
     * Error
     *
     * Log error messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function error(string $message)
    {
        return fwrite(STDERR, "\033[31m" . $message . "\033[0m\n");
    }

    /**
     * Info
     *
     * Log informative messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function info(string $message) {
        return fwrite(STDOUT, "\033[34m" . $message . "\033[0m\n");
    }

    /**
     * Warning
     *
     * Log warning messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function warning(string $message) {
        return fwrite(STDERR, "\033[1;33m" . $message . "\033[0m\n");
    }

    /**
     * Warning
     *
     * Log warning messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function confirm(string $question) {
        self::log($question);

        $handle = fopen('php://stdin', 'r');
        $line   = trim(fgets($handle));

        fclose($handle);
        
        return $line;
    }
}