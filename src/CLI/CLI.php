<?php

namespace Utopia\CLI;

use Exception;
use Utopia\Validator;

class CLI
{
    /**
     * Command
     *
     * The name of the command requested for this process
     *
     * @var string
     */
    protected $command = '';

    /**
     * Args
     *
     * List of arguments passed to this process
     *
     * @var array
     */
    protected $args = [];

    /**
     * Tasks
     *
     * List of commands tasks for this CLI process
     *
     * @var array
     */
    protected $tasks = [];

    /**
     * Error
     *
     * An error callback
     *
     * @var callable
     */
    protected $error;

    /**
     * Init
     *
     * A callback function that is initialized on application start
     *
     * @var callable[]
     */
    protected $init = [];

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var callable[]
     */
    protected $shutdown = [];

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var array
     */
    protected static $resourcesCallbacks = [];

    /**
     * CLI constructor.
     *
     * @param array $args
     * @throws Exception
     */
    public function __construct(array $args = [])
    {
        if (\php_sapi_name() !== "cli") {
            throw new Exception('CLI tasks can only work from the command line');
        }

        $this->args = $this->parse((!empty($args) || !isset($_SERVER['argv'])) ? $args : $_SERVER['argv']);

        $this->error = function (Exception $error): void {
            Console::error($error->getMessage()."\n");
        };

        @\cli_set_process_title($this->command);
    }

    /**
     * If a resource has been created return it, otherwise create it and then return it
     *
     * @param string $name
     * @param bool $fresh
     * @return mixed
     * @throws Exception
     */
    public function getResource(string $name, $fresh = false)
    {
        if ($name === 'utopia') {
            return $this;
        }

        if (!\array_key_exists($name, $this->resources) || $fresh || self::$resourcesCallbacks[$name]['reset']) {
            if (!\array_key_exists($name, self::$resourcesCallbacks)) {
                throw new Exception('Failed to find resource: "' . $name . '"');
            }

            $this->resources[$name] = \call_user_func_array(self::$resourcesCallbacks[$name]['callback'],
                $this->getResources(self::$resourcesCallbacks[$name]['injections']));
        }

        self::$resourcesCallbacks[$name]['reset'] = false;

        return $this->resources[$name];
    }

    /**
     * Get Resources By List
     *
     * @param array $list
     * @return array
     */
    public function getResources(array $list): array
    {
        $resources = [];
        
        foreach ($list as $name) {
            $resources[$name] = $this->getResource($name);
        }

        return $resources;
    }

    /**
     * Set a new resource callback
     *
     * @param string $name
     * @param callable $callback
     *
     * @throws Exception
     *
     * @return void
     */
    public static function setResource(string $name, callable $callback, array $injections = []): void
    {
        if ($name === 'utopia') {
            throw new Exception("'utopia' is a reserved keyword.", 500);
        }
        self::$resourcesCallbacks[$name] = ['callback' => $callback, 'injections' => $injections, 'reset' => true];
    }

    /**
     * Init
     *
     * Set a callback function that will be initialized on application start
     *
     * @param callable $callback
     * @param array $resources
     * @return $this
     */
    public function init(callable $callback, array $resources = []): self
    {
        $this->init[] = ['callback' => $callback, 'resources' => $resources];
        return $this;
    }

    /**
     * Shutdown
     *
     * Set a callback function that will be initialized on application end
     *
     * @param $callback
     * @return $this
     */
    public function shutdown(callable $callback): self
    {
        $this->shutdown[] = $callback;
        return $this;
    }

    /**
     * Error
     *
     * An error callback for failed or no matched requests
     *
     * @param $callback
     * @return $this
     */
    public function error(callable $callback): self
    {
        $this->error = $callback;
        return $this;
    }

    /**
     * Task
     * 
     * Add a new command task
     * 
     * @param string $name
     * 
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
     * @param array $args
     * @throws Exception
     * @return array
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
            $pair = explode("=", $arg);
            $key = $pair[0];
            $value = isset($pair[1]) ? $pair[1] : '';
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
    public function match()
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
                foreach ($this->init as $init) {
                    \call_user_func_array($init['callback'], $this->getResources($init['resources']));
                }

                $params = [];

                foreach ($command->getParams() as $key => $param) {
                    /** 
                     * Get the value for a param in the following precedence order 
                     * 1. Command line argument
                     * 2. Prompt
                     * 3. Default value
                    */
                    $value = '';
                    if (isset($this->args[$key])) {
                        $value = $this->args[$key];
                    } else if (isset($param['prompt']) && is_string($param['prompt']) && !empty($param['prompt'])) {
                        if (empty($param['options'])) {
                            Console::enableBuffer();
                            $value = Console::confirm($param['prompt']);
                            Console::disableBuffer();
                        } else {
                            $value = Console::select($param['prompt'], $param['options'], $param['max']);
                        }
                    }

                    if (empty($value)) {
                        $value = $param['default'];
                    } 

                    $this->validate($key, $param, $value);

                    $params[$param['order']] = $value;
                }

                foreach ($command->getInjections() as $key => $injection) {
                    $params[$injection['order']] = $this->getResource($injection['name']);
                }

                // Call the callback with the matched positions as params
                \call_user_func_array($command->getAction(), $params);

                foreach ($this->shutdown as $shutdown) {
                    \call_user_func_array($shutdown, []);
                }
            } else {
                throw new Exception('No command found');
            }
        } catch (Exception $e) {
            // Console::restoreTerminalConfig();
            \call_user_func_array($this->error, array($e));
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
     * @param string $key
     * @param array $param
     * @param mixed $value
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

            if (\is_callable($validator)) {
                $validator = \call_user_func_array($validator, $this->getResources($param['injections']));
            }

            // is the validator object an instance of the Validator class
            if (!$validator instanceof Validator) {
                throw new Exception('Validator object is not an instance of the Validator class', 500);
            }

            if (!$validator->isValid($value)) {
                throw new Exception('Invalid ' . $key . ': ' . $validator->getDescription(), 400);
            }
        } else {
            if (!$param['optional']) {
                throw new Exception('Param "' . $key . '" is not optional.', 400);
            }
        }
    }
}
