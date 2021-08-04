<?php

namespace Utopia\CLI;

class Console
{

    public const UP = 'UP';
    public const DOWN = 'DOWN';
    public const RIGHT = 'RIGHT';
    public const LEFT = 'LEFT';
    public const CTRLA = 'CTRLA';
    public const CTRLB = 'CTRLB';
    public const CTRLE = 'CTRLE';
    public const CTRLF = 'CTRLF';
    public const BACKSPACE = 'BACKSPACE';
    public const CTRLW = 'CTRLW';
    public const ENTER = 'ENTER';
    public const TAB = 'TAB';
    public const ESC = 'ESC';

    private static $controls = [
        "\033[A" => self::UP,
        "\033[B" => self::DOWN,
        "\033[C" => self::RIGHT,
        "\033[D" => self::LEFT,
        "\033OA" => self::UP,
        "\033OB" => self::DOWN,
        "\033OC" => self::RIGHT,
        "\033OD" => self::LEFT,
        "\001"   => self::CTRLA,
        "\002"   => self::CTRLB,
        "\005"   => self::CTRLE,
        "\006"   => self::CTRLF,
        "\010"   => self::BACKSPACE,
        "\177"   => self::BACKSPACE,
        "\027"   => self::CTRLW,
        "\n"     => self::ENTER,
        "\t"     => self::TAB,
        "\e"     => self::ESC,
    ];

    /**
     * Title
     *
     * Sets the process title visible in tools such as top and ps. 
     *
     * @param string $title
     * @return bool
     */
    static public function title(string $title)
    {
        return @\cli_set_process_title($title);
    }

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
        return \fwrite(STDOUT, $message . "\n");
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
        return \fwrite(STDOUT, "\033[32m" . $message . "\033[0m\n");
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
        return \fwrite(STDERR, "\033[31m" . $message . "\033[0m\n");
    }

    /**
     * Info
     *
     * Log informative messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function info(string $message)
    {
        return \fwrite(STDOUT, "\033[34m" . $message . "\033[0m\n");
    }

    /**
     * Warning
     *
     * Log warning messages to console
     *
     * @param string $message
     * @return bool|int
     */
    static public function warning(string $message)
    {
        return \fwrite(STDERR, "\033[1;33m" . $message . "\033[0m\n");
    }

    /**
     * Warning
     *
     * Log warning messages to console
     *
     * @param string $question
     * @return string
     */
    static public function confirm(string $question)
    {
        if (!self::isInteractive()) {
            return '';
        }

        self::log($question);

        $handle = \fopen('php://stdin', 'r');
        $line   = \trim(\fgets($handle));

        \fclose($handle);

        return $line;
    }

    /**
     * 
     * 
     */

    static public function select(string $prompt, array $options, int $numSelect)
    {
        if (!self::isInteractive()) {
            return '';
        }

        // $existingContents = ob_get_contents();
        // $existingContents = fgets(STDOUT);
        // Console::success($existingContents);

        // Disable echo 
        self::disableEchoBack();
        // Disable canonical mode 
        self::disableCanonical();

        $cursorPosition = 0;
        $selections = [];
        $numOptions = count($options);
        $input = '';

        while (true) {
            //Clean everything 
            fwrite(STDOUT, "\033[2J");
            fwrite(STDOUT, "\033[H");

            // Render stuff
            self::log($prompt);
            foreach ($options as $key => $value) {
                if ($cursorPosition == $key && isset($selections[$key])) {
                    self::success($key . ': ' . '[  ' . $value.'  ]');
                } else if (isset($selections[$key])) {
                    self::success($key . ': ' . $value);
                } else if ($cursorPosition == $key) {
                    self::log($key . ': ' . '[  ' . $value.'  ]');
                } else {
                    self::log($key . ': ' . $value);
                }
            }

            if (count($selections) == $numSelect) {
                return $selections;
            }
            // Get input
            $input = fread(STDIN, 4);

            // Process input 
            if (self::isControl($input)) {
                $pressed = self::getControl($input);
                switch ($pressed) {
                    case self::UP:
                        $cursorPosition = ($cursorPosition - 1) < 0 ? $numOptions - 1 : $cursorPosition - 1;
                        break;
                    case self::DOWN:
                        $cursorPosition = ($cursorPosition + 1) > $numOptions - 1 ? 0 : $cursorPosition + 1;
                        break;
                    case self::ENTER:
                        if (count($selections) < $numSelect) {
                            $selections[$cursorPosition] = $options[$cursorPosition];
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        // Enable echo
        self::enableEchoBack();
        // Enable canonical mode
        self::enableCanonical();

        return "Selected";
    }

    static public function enableEchoBack()
    {
        system("stty echo");
    }

    static public function disableEchoBack()
    {
        system("stty -echo");
    }

    /**
     * @see https://www.gnu.org/software/libc/manual/html_node/Canonical-or-Not.html 
     */
    static public function enableCanonical()
    {
        system('stty icanon');
    }

    static public function disableCanonical()
    {
        system('stty -icanon');
    }

    /**
     * Is this character a control sequence?
     */
    static public function isControl($char): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $char);
    }

    static public function getControl(string $input): string
    {
        if (!isset(static::$controls[$input])) {
            throw new \RuntimeException(sprintf('Character "%s" is not a control', $input));
        }

        return static::$controls[$input];
    }


    /**
     * Exit
     *
     * Log warning messages to console
     *
     * @param string $message
     * @return void
     */
    static public function exit(int $status = 0): void
    {
        exit($status);
    }

    /**
     * Execute a Commnad
     * 
     * This function was inspired by: https://stackoverflow.com/a/13287902/2299554
     * 
     * @param string $cmd
     * @param string $stdin
     * @param string $stdout
     * @param string $stderr
     * @param int $timeout
     * @return int
     */
    static public function execute(string $cmd, string $stdin, string &$stdout, string &$stderr, int $timeout = -1): int
    {
        $pipes = [];
        $process = \proc_open(
            $cmd,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes
        );
        $start = \time();
        $stdout = '';
        $stderr = '';

        if (\is_resource($process)) {
            \stream_set_blocking($pipes[0], false);
            \stream_set_blocking($pipes[1], false);
            \stream_set_blocking($pipes[2], false);

            \fwrite($pipes[0], $stdin);
            \fclose($pipes[0]);
        }

        while (\is_resource($process)) {
            $stdout .= \stream_get_contents($pipes[1]);
            $stderr .= \stream_get_contents($pipes[2]);

            if ($timeout > 0 && \time() - $start > $timeout) {
                \proc_terminate($process, 9);
                return 1;
            }

            $status = \proc_get_status($process);

            if (!$status['running']) {
                \fclose($pipes[1]);
                \fclose($pipes[2]);
                \proc_close($process);

                return (int)$status['exitcode'];
            }

            \usleep(10000);
        }

        return 1;
    }

    /**
     * Is Interactive Mode?
     * 
     * @return bool
     */
    static public function isInteractive(): bool
    {
        return ('cli' === PHP_SAPI && defined('STDOUT'));
    }

    /**
     * @param callable $callback
     * @param int $sleep in seconds
     */
    static public function loop(callable $callback, $sleep = 1 /* 1 second */): void
    {
        gc_enable();

        $time = 0;

        while (!connection_aborted() || PHP_SAPI == "cli") {

            $callback();

            sleep($sleep);

            $time = $time + $sleep;

            if (PHP_SAPI == "cli") {
                if ($time >= (1000000 * 300)) { // Every 5 minutes
                    $time = 0;
                    gc_collect_cycles(); //Forces collection of any existing garbage cycles
                }
            }
        }
    }
}
