<?php

namespace Utopia\CLI;

use Exception;
use Utopia\Validator;

class CLI {

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
    protected $error = null;

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

        $this->args = $this->parse((!empty($args) || !isset($_SERVER['argv'])) ? $args: $_SERVER['argv']);

        $this->error = function (Exception $error) {
            Console::error($error->getMessage());
        };

        @\cli_set_process_title($this->command);
    }

    /**
     * Init
     *
     * Set a callback function that will be initialized on application start
     *
     * @param $callback
     * @return $this
     */
    public function init($callback)
    {
        $this->init[] = $callback;
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
    public function shutdown($callback)
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
    public function error($callback)
    {
        $this->error = $callback;
        return $this;
    }

    public function task($name)
    {
        $task = new Task($name);

        $this->tasks[$name] = $task;

        return $task;
    }

    /**
     *
     * task-name --foo=test
     *
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public function parse(array $args)
    {
        \array_shift($args); // Remove script path from args

        if(isset($args[0])) {
            $this->command = \array_shift($args);
        }
        else {
            throw new Exception('Missing command');
        }

        $output = [];

        foreach ($args as &$arg) {
            if(\substr($arg, 0, 2) === '--') {
                $arg = \substr($arg, 2);
            }
        }

        parse_str(implode('&', $args), $output); // Parse like PHP query string to support PHP arrays

        return $output;
    }

    /**
     * Run
     */
    public function run()
    {
        $command = isset($this->tasks[$this->command]) ? $this->tasks[$this->command] : null;

        try {
            if($command) {
                foreach($this->init as $init) {
                    \call_user_func_array($init, []);
                }

                $params = [];

                foreach($command->getParams() as $key => $param) {
                    // Get value from route or request object
                    $value = (isset($this->args[$key])) ? $this->args[$key] : $param['default'];

                    $this->validate($key, $param, $value);

                    $params[$key] = $value;
                }

                // Call the callback with the matched positions as params
                \call_user_func_array($command->getAction(), $params);

                foreach($this->shutdown as $shutdown) {
                    \call_user_func_array($shutdown, []);
                }
            }
            else {
                throw new Exception('No command found');
            }
        }
        catch (Exception $e) {
            \call_user_func_array($this->error, array($e));
        }

        return $this;
    }

    /**
     * Validate Param
     *
     * Creates an validator instance and validate given value with given rules.
     *
     * @param $key
     * @param $param
     * @param $value
     * @throws Exception
     */
    protected function validate($key, $param, $value)
    {
        if ('' !== $value) {
            // checking whether the class exists
            $validator = $param['validator'];

            if(\is_callable($validator)) {
                $validator = $validator();
            }

            // is the validator object an instance of the Validator class
            if (!$validator instanceof Validator) {
                throw new Exception('Validator object is not an instance of the Validator class', 500);
            }

            if (!$validator->isValid($value)) {
                throw new Exception('Invalid ' .$key . ': ' . $validator->getDescription(), 400);
            }
        }
        else if (!$param['optional']) {
            throw new Exception('Param "' . $key . '" is not optional.', 400);
        }
    }
}