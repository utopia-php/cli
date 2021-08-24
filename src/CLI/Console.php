<?php

namespace Utopia\CLI;

class Console
{
    /**
     * Names of the supported control keys
     * 
     * @var string 
     */
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
    protected const KEY_SPACE = 'SPACE';
    protected const KEY_TAB = 'TAB';
    protected const KEY_ESC = 'ESC';

    /**
     * Mappings of key codes to key names.
     * 
     * @var array
     */
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
        " "     => self::KEY_SPACE,
        "\t"     => self::KEY_TAB,
        "\e"     => self::KEY_ESC,
    ];

    /**
     * Marker for a selected radio button 
     * 
     * @var string 
     */
    static protected $markerRadioSelected = '[●]';

    /**
     * Marker for an unselected radio button 
     * 
     * @var string 
     */
    static protected $markerRadioUnselected = '[○]';

    /**
     * Marker for a selected check box 
     * 
     * @var string 
     */
    static protected $markerCheckboxSelected = '[✔]';

    /**
     * Marker for an unselected check box 
     * 
     * @var string 
     */
    static protected $markerCheckboxUnselected = '[ ]';


    /**
     * Global flag to enable / disable buffering 
     * 
     * @var bool 
     */
    static bool $isBuffered = false;

    /**
     * Buffer to store all the output 
     * 
     * @var array 
     */
    static array $buffer = [];

    /**
     * Toggle to enable output buffering 
     * 
     * @return void
     */
    static public function enableBuffer() {
        self::$isBuffered = true;
    }

    /**
     * Toggle to disable output buffering
     *
     * @return void
     */
    static public function disableBuffer() {
        self::$isBuffered = false;
    }

    /**
     * Add a string to the buffer
     *
     * @param string $text
     * @return void
     */
    static protected function addToBuffer(string $text)
    {
        self::$buffer[] = $text;
    }

    /**
     * Clears the output buffer
     *
     * @return void
     */
    static protected function clearBuffer()
    {
        self::$buffer = [];
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
        $message = "\033[32m" . $message . "\033[0m";
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDOUT, $message);
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
        $message = "\033[31m" . $message . "\033[0m";
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDERR, $message);
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
        $message = "\033[34m" . $message . "\033[0m";
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDOUT, $message);
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
        $message = "\033[1;33m" . $message . "\033[0m";
        if (self::$isBuffered) self::addToBuffer($message);
        return \fwrite(STDERR, $message);
    }

    /**
     * Confirm
     *
     * Displays a prompt on the console
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

        if (self::$isBuffered) {
            self::addToBuffer($line . "\n");
        }

        \fclose($handle);

        return $line;
    }


    /**
     * Draw 
     * 
     * Draws a prompt onto the console
     * 
     * @param string $prompt
     * @param array $options
     * @param array $selections
     * @param int $max
     * @param int $cursorPosition
     * @param bool $isBuffered
     *  
     * @return void
     */
    static protected function draw(string $prompt, array $options, array $selections, int $max, int $cursorPosition, bool $isBuffered = false)
    {
        $keys = array_keys($options);
        $markerSelected = $max == 1 ? self::$markerRadioSelected : self::$markerCheckboxSelected;
        $markerUnselected = $max == 1 ? self::$markerRadioUnselected : self::$markerCheckboxUnselected;

        /** Start rendering */
        self::clear();
        self::moveCursorToTop();
        
        foreach (self::$buffer as $line) {
            self::log($line);
        }

        if($isBuffered) self::enableBuffer(); 

        self::log($prompt);
        foreach ($options as $key => $value) {
            if ($keys[$cursorPosition] == $key && isset($selections[$key])) {
                self::log("\033[36;1m$markerSelected $value ( $key ) <-\n\033[0m");
            } else if (isset($selections[$key])) {
                self::log("\033[36;1m$markerSelected $value ( $key )\n\033[0m");
            } else if ($keys[$cursorPosition] == $key) {
                self::log("$markerUnselected $value ( $key ) <-\n");
            } else {
                self::log("$markerUnselected $value ( $key )\n");
            }
        }

        if ($isBuffered) self::disableBuffer();
    }

    /**
     * Select
     * 
     * Creates a single or multi-select prompt
     * 
     * @param string $prompt
     * @param array $options
     * @param int $max
     *  
     * @return array
     */
    static public function select(string $prompt, array $options, int $max)
    {
        if (!self::isInteractive()) {
            return [];
        }
   
        /** Intercept signals */
        pcntl_async_signals(true);
        $handler = function () {
            self::restoreTerminal();
            self::exit(1);
        };
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);

        self::prepareTerminal();

        /** Initialize all the values */
        $cursorPosition = 0;
        $keys = array_keys($options);
        $selections = [];
        $numOptions = count($options);
        $input = '';
        $confirm = false;

        self::draw($prompt, $options, $selections, $max, $cursorPosition);

        while (true) {
            /** Set stream to non-blocking to allow POSIX signals to be intercepted. 
             * Get and process the standard input ( Why 4 bytes? ) 
             */
            stream_set_blocking(STDIN, false);
            $input = fread(STDIN, 4);

            if (isset(self::$controls[$input])) {
                $pressed = self::getControl($input);
                switch ($pressed) {
                    case self::KEY_UP:
                        $cursorPosition = ($cursorPosition - 1) < 0 ? $numOptions - 1 : $cursorPosition - 1;
                        self::draw($prompt, $options, $selections, $max, $cursorPosition);
                        break;
                    case self::KEY_DOWN:
                        $cursorPosition = ($cursorPosition + 1) > $numOptions - 1 ? 0 : $cursorPosition + 1;
                        self::draw($prompt, $options, $selections, $max, $cursorPosition);
                        break;
                    case self::KEY_SPACE:
                        if (isset($selections[$keys[$cursorPosition]])) {
                            unset($selections[$keys[$cursorPosition]]);
                        } else {
                            if ($max <= 1) {
                                foreach ($selections as $key => $value) {
                                    unset($selections[$key]);
                                }
                            }
                            $selections[$keys[$cursorPosition]] = $options[$keys[$cursorPosition]];
                        }
                        self::draw($prompt, $options, $selections, $max, $cursorPosition);
                        break;
                    case self::KEY_ENTER:
                        if (!empty($selections)) {
                            $confirm = true;
                            self::draw($prompt, $options, $selections, $max, $cursorPosition);
                        }
                }
            }

            if (count($selections) == $max || $confirm) {
                self::restoreTerminal();
                $selection = Console::confirm("Confirm selection? [Y/N] ");
                self::prepareTerminal();
                if ($selection == 'Y') {
                    self::draw($prompt, $options, $selections, $max, $cursorPosition, true);
                    self::restoreTerminal();
                    return $selections;
                } else {
                    $selections = [];
                    $confirm = false;
                    self::draw($prompt, $options, $selections, $max, $cursorPosition);
                }
            }

            usleep(100);
        }
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

    /**
     * Prepare the terminal for select mode.
     * 
     * @return void
     */
    static function prepareTerminal() 
    {
        self::disableEchoBack();
        self::disableCanonical();
        self::disableCursor();
    }

    /**
     * Restore the terminal to its original state.
     * 
     * @return void
     */
    static function restoreTerminal()
    {
        self::enableEchoBack();
        self::enableCanonical();
        self::enableCursor();
        stream_set_blocking(STDIN, true);
    }

    /**
     * Clear contents of the screen
     * 
     * @return void
     */
    static protected function clear()
    {
        fwrite(STDOUT, "\033[2J");
    }

    /**
     * Erase screen from the current line down to the bottom of the screen
     */
    static protected function clearDown(): void
    {
        fwrite(STDOUT, "\033[J");
    }

    /**
     * Move the cursor to the top of the screen
     * 
     * @return void
     */
    static protected function moveCursorToTop()
    {
        fwrite(STDOUT, "\033[H");
    }

    /**
     * Echo characters back to the screen
     * 
     * @return void
     */
    static protected function enableEchoBack()
    {
        system("stty echo");
    }

    /**
     * Disable the echo back of characters to the screen
     * 
     * @return void
     */
    static protected function disableEchoBack()
    {
        system("stty -echo");
    }

    /**
     * Enable canonical mode 
     * 
     * @see https://www.gnu.org/software/libc/manual/html_node/Canonical-or-Not.html 
     */
    static protected function enableCanonical()
    {
        system('stty icanon');
    }

    /**
     * Disable canonical mode
     * 
     * @return void
     */
    static protected function disableCanonical()
    {
        system('stty -icanon');
    }

    /**
     * Enable the cursor
     * 
     * @return void
     */
    static protected function enableCursor(): void
    {
        fwrite(STDOUT, "\033[?25h");
    }

    /**
     * Disable the cursor
     * 
     * @return void
     */
    static protected function disableCursor(): void
    {
        fwrite(STDOUT, "\033[?25l");
    }

    /**
     * Get the mapping from a control code to it's respective character
     * 
     * @param string $input
     * @return string
     */
    static protected function getControl(string $input): string
    {
        if (!isset(static::$controls[$input])) {
            throw new \RuntimeException(sprintf('Character "%s" is not a control', $input));
        }

        return static::$controls[$input];
    }
}
