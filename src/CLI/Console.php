<?php

namespace Utopia\CLI;

class Console
{

    protected const KEY_UP = 'UP';
    protected const KEY_DOWN = 'DOWN';
    protected const KEY_RIGHT = 'RIGHT';
    protected const KEY_LEFT = 'LEFT';
    protected const KEY_CTRLA = 'CTRLA';
    protected const KEY_CTRLB = 'CTRLB';
    protected const KEY_CTRLC = 'CTRLC';
    protected const KEY_CTRLE = 'CTRLE';
    protected const KEY_CTRLF = 'CTRLF';
    protected const KEY_BACKSPACE = 'BACKSPACE';
    protected const KEY_CTRLW = 'CTRLW';
    protected const KEY_ENTER = 'ENTER';
    protected const KEY_TAB = 'TAB';
    protected const KEY_ESC = 'ESC';

    private static $controls = [
        "\033[A" => self::KEY_UP,
        "\033[B" => self::KEY_DOWN,
        "\033[C" => self::KEY_RIGHT,
        "\033[D" => self::KEY_LEFT,
        "\033OA" => self::KEY_UP,
        "\033OB" => self::KEY_DOWN,
        "\033OC" => self::KEY_RIGHT,
        "\033OD" => self::KEY_LEFT,
        "\001"   => self::KEY_CTRLA,
        "\002"   => self::KEY_CTRLB,
        "\003"   => self::KEY_CTRLC,
        "\005"   => self::KEY_CTRLE,
        "\006"   => self::KEY_CTRLF,
        "\010"   => self::KEY_BACKSPACE,
        "\177"   => self::KEY_BACKSPACE,
        "\027"   => self::KEY_CTRLW,
        "\n"     => self::KEY_ENTER,
        "\t"     => self::KEY_TAB,
        "\e"     => self::KEY_ESC,
    ];

    static protected $markerRadioSelected = '[●]';
    static protected $markerRadioUnselected = '[○]';
    static protected $markerCheckboxSelected = '[✔]';
    static protected $markerCheckboxUnselected = '[ ]';

    static protected bool $isBuffered = false;
    static protected array $buffer = [];

    static protected function addToBuffer(string $text)
    {
        self::$buffer[] = $text;
    }

    static protected function clearBuffer()
    {
        unset(self::$buffer);
    }

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
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDOUT, $message);
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
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDOUT, "\033[32m" . $message . "\033[0m");
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
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDERR, "\033[31m" . $message . "\033[0m");
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
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDOUT, "\033[34m" . $message . "\033[0m");
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
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDERR, "\033[1;33m" . $message . "\033[0m");
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

        self::addToBuffer($line);
        \fclose($handle);

        return $line;
    }


    static protected function draw(string $prompt, array $options, array $selections, int $numSelect, int $cursorPosition)
    {
        $keys = array_keys($options);
        $markerSelected = $numSelect == 1 ? self::$markerRadioSelected : self::$markerCheckboxSelected;
        $markerUnselected = $numSelect == 1 ? self::$markerRadioUnselected : self::$markerCheckboxUnselected;

        self::clear();
        self::moveCursorToTop();

        /** Start rendering */
        foreach (self::$buffer as $line) {
            fwrite(STDOUT, $line);
        }

        self::log($prompt);
        foreach ($options as $key => $value) {
            if ($keys[$cursorPosition] == $key && isset($selections[$key])) {
                self::success("$markerSelected $value ( $key ) <-\n");
            } else if (isset($selections[$key])) {
                self::success("$markerSelected $value ( $key )\n");
            } else if ($keys[$cursorPosition] == $key) {
                self::log("$markerUnselected $value ( $key ) <-\n");
            } else {
                self::log("$markerUnselected $value ( $key )\n");
            }
        }
    }

    /**
     * 
     * 
     */
    static public function select(string $prompt, array $options, int $numSelect)
    {
        /** Prepare the terminal*/
        if (!self::isInteractive()) {
            return [];
        }
        self::disableEchoBack();
        self::disableCanonical();
        self::disableCursor();

        /** Intercept signals */
        pcntl_async_signals(true);
        $handler = function() {
            self::restoreTerminalConfig();
            exit(1);
        };
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);

        /** Initialize the renderer */
        $cursorPosition = 0;
        $keys = array_keys($options);
        $selections = [];
        $numOptions = count($options);
        $input = '';

        /**Render */
        self::draw($prompt, $options, $selections, $numSelect, $cursorPosition);
        while (true) {
            if (count($selections) == $numSelect) {
                self::restoreTerminalConfig();
                return $selections;
            }

            /** Get and process Input ( Why 4 bytes ) */
            stream_set_blocking(STDIN, false);
            $input = fread(STDIN, 4);

            if (self::isControl($input)) {
                $pressed = self::getControl($input);
                switch ($pressed) {
                    case self::KEY_UP:
                        $cursorPosition = ($cursorPosition - 1) < 0 ? $numOptions - 1 : $cursorPosition - 1;
                        self::draw($prompt, $options, $selections, $numSelect, $cursorPosition);                        
                        break;
                    case self::KEY_DOWN:
                        $cursorPosition = ($cursorPosition + 1) > $numOptions - 1 ? 0 : $cursorPosition + 1;
                        self::draw($prompt, $options, $selections, $numSelect, $cursorPosition);
                        break;
                    case self::KEY_ENTER:
                        if (isset($selections[$keys[$cursorPosition]])) {
                            unset($selections[$keys[$cursorPosition]]);
                        } else if (count($selections) < $numSelect) {
                            $selections[$keys[$cursorPosition]] = $options[$keys[$cursorPosition]];
                        } 
                        self::draw($prompt, $options, $selections, $numSelect, $cursorPosition);
                        break;
                    default:
                        break;
                }
            }
            usleep(100);
        }
    }


    static protected function restoreTerminalConfig()
    {
        self::enableEchoBack();
        self::enableCanonical();
        self::enableCursor();
    }

    static protected function clear()
    {
        fwrite(STDOUT, "\033[2J");
    }

    static protected function moveCursorToTop()
    {
        fwrite(STDOUT, "\033[H");
    }

    static protected function enableEchoBack()
    {
        system("stty echo");
    }

    static protected function disableEchoBack()
    {
        system("stty -echo");
    }

    /**
     * @see https://www.gnu.org/software/libc/manual/html_node/Canonical-or-Not.html 
     */
    static protected function enableCanonical()
    {
        system('stty icanon');
    }

    static protected function disableCanonical()
    {
        system('stty -icanon');
    }

    static protected function enableCursor(): void
    {
        fwrite(STDOUT, "\033[?25h");
    }

    static protected function disableCursor(): void
    {
        fwrite(STDOUT, "\033[?25l");
    }

    /**
     * Is this character a control sequence?
     */
    static protected function isControl($char): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $char);
    }

    static protected function getControl(string $input): string
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
     * Execute a Command
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
