<?php

namespace Utopia\CLI;

use Exception;
use Utopia\Hook;
use Utopia\Traits\Hooks;
use Utopia\Traits\Resources;
use Utopia\Validator;

class CLI
{
    use Resources, Hooks;

    /**
     * Command
     *
     * The name of the command requested for this process
     *
     * @var string
     */
    protected string $command = '';

    /**
     * Args
     *
     * List of arguments passed to this process
     *
     * @var array
     */
    protected array $args = [];

    /**
     * Tasks
     *
     * List of commands tasks for this CLI process
     *
     * @var array
     */
    protected array $tasks = [];

    /**
     * CLI constructor.
     *
     * @param  array  $args
     *
     * @throws Exception
     */
    public function __construct(array $args = [])
    {
        if (\php_sapi_name() !== 'cli') {
            throw new Exception('CLI tasks can only work from the command line');
        }

        $this->args = $this->parse((! empty($args) || ! isset($_SERVER['argv'])) ? $args : $_SERVER['argv']);

        @\cli_set_process_title($this->command);
    }

    /**
     * Task
     *
     * Add a new command task
     *
     * @param  string  $name
     * @return Task
     */
    public function task(string $name): Task
    {
        $task = new Task($name);

        $this->tasks[$name] = $task;

        return $task;
    }

    /**
     * task-name --foo=test
     *
     * @param  array  $args
     * @return array
     *
     * @throws Exception
     */
    public function parse(array $args): array
    {
        \array_shift($args); // Remove script path from args

        if (isset($args[0])) {
            $this->command = \array_shift($args);
        } else {
            throw new Exception('Missing command');
        }

        $output = [];

        foreach ($args as &$arg) {
            if (\substr($arg, 0, 2) === '--') {
                $arg = \substr($arg, 2);
            }
        }

        /**
         * Refer to this answer
         * https://stackoverflow.com/questions/18669499/php-issue-with-looping-over-an-array-twice-using-foreach-and-passing-value-by-re/18669732
         */
        unset($arg);

        foreach ($args as $arg) {
            $pair = explode('=', $arg);
            $key = $pair[0];
            $value = $pair[1];
            $output[$key][] = $value;
        }

        foreach ($output as $key => $value) {
            /**
             * If there is only one element in a particular key
             * unshift the value out of the array
             */
            if (count($value) == 1) {
                $output[$key] = array_shift($output[$key]);
            }
        }

        return $output;
    }

    /**
     * Find the command that should be triggered
     *
     * @return Task|null
     */
    public function match(): ?Task
    {
        return isset($this->tasks[$this->command]) ? $this->tasks[$this->command] : null;
    }

    /**
     * Run
     *
     * @return $this
     */
    public function run(): self
    {
        $command = $this->match();

        try {
            if ($command) {
                /**
                 * Call init hooks
                 */
                $this->callHooks(self::$init, params: $this->args);

                /**
                 * Call the command hook
                 */
                $this->callHook($command, params: $this->args);

                /**
                 * Call shutdown hooks
                 */
                $this->callHooks(self::$shutdown, params: $this->args);
            } else {
                throw new Exception('No command found');
            }
        } catch (Exception $e) {
            self::setResource('error', fn () => $e);
            $this->callHooks(self::$errors, params: $this->args);
        }

        return $this;
    }

    /**
     * Get list of all tasks
     *
     * @return Task[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Get list of all args
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Validate Param
     *
     * Creates an validator instance and validate given value with given rules.
     *
     * @param  string  $key
     * @param  array  $param
     * @param  mixed  $value
     *
     * @throws Exception
     */
    protected function validate(string $key, array $param, $value): void
    {
        if ('' !== $value) {
            // checking whether the class exists
            $validator = $param['validator'];

            if (\is_callable($validator)) {
                $validator = $validator();
            }

            // is the validator object an instance of the Validator class
            if (! $validator instanceof Validator) {
                throw new Exception('Validator object is not an instance of the Validator class', 500);
            }

            if (! $validator->isValid($value)) {
                throw new Exception('Invalid '.$key.': '.$validator->getDescription(), 400);
            }
        } else {
            if (! $param['optional']) {
                throw new Exception('Param "'.$key.'" is not optional.', 400);
            }
        }
    }

    public static function reset(): void
    {
        self::$resourcesCallbacks = [];
        self::$init = [];
        self::$shutdown = [];
        self::$errors = [];
    }
}
