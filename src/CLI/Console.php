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
        return fwrite(STDOUT, "\033[32m" . $message . "\033[0m\n");
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

    /**
     * Execute a Commnad
     * 
     * This function was inspired by: https://stackoverflow.com/a/13287902/2299554
     */
    public function execute(string $cmd, string $stdin = null, string &$stdout, string &$stderr, int $timeout = -1):int
    {
        $pipes = [];
        $process = proc_open(
            $cmd,
            [['pipe','r'],['pipe','w'],['pipe','w']],
            $pipes
        );
        $start = time();
        $stdout = '';
        $stderr = '';

        if (is_resource($process)) {
            stream_set_blocking($pipes[0], 0);
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);

            fwrite($pipes[0], $stdin);
            fclose($pipes[0]);
        }

        while(is_resource($process))
        {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            if($timeout > 0 && time() - $start > $timeout)
            {
                proc_terminate($process, 9);
                return 1;
            }

            $status = proc_get_status($process);

            if(!$status['running']) {
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                return $status['exitcode'];
            }

            usleep(100000);
        }

        return 1;
    }
}