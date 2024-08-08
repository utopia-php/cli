<?php

namespace Utopia\CLI;

use Exception;
use Utopia\CLI\Adapters\Generic;
use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Http\Hook;
use Utopia\Http\Validator;

class CLI
{
    /**
     * Adapter
     *
     * Type of adapter to pass the callable to
     *
     * @var Adapter
     */
    protected Adapter $adapter;

    /**
     * Command
     *
     * The name of the command requested for this process
     *
     * @var string
     */
    protected string $command = '';

    /**
     * @var Container
     */
    protected Container $container;

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
     * Error
     *
     * An error callback
     *
     * @var Hook[]
     */
    protected $errors = [];

    /**
     * Init
     *
     * A callback function that is initialized on application start
     *
     * @var Hook[]
     */
    protected array $init = [];

    /**
     * Shutdown
     *
     * A callback function that is initialized on application end
     *
     * @var Hook[]
     */
    protected array $shutdown = [];

    /**
     * CLI constructor.
     *
     * @param  Adapter|null  $adapter
     * @param  array  $args
     *
     * @throws Exception
     */
    public function __construct(Adapter $adapter = null, array $args = [])
    {
        if (\php_sapi_name() !== 'cli') {
            throw new Exception('CLI tasks can only work from the command line');
        }

        $this->args = $this->parse((! empty($args) || ! isset($_SERVER['argv'])) ? $args : $_SERVER['argv']);

        @\cli_set_process_title($this->command);

        $this->adapter = $adapter ?? new Generic();
        $this->container = new Container();
    }

    /**
     * Init
     *
     * Set a callback function that will be initialized on application start
     *
     * @return Hook
     */
    public function init(): Hook
    {
        $hook = new Hook();
        $this->init[] = $hook;

        return $hook;
    }

    /**
     * Shutdown
     *
     * Set a callback function that will be initialized on application end
     *
     * @return Hook
     */
    public function shutdown(): Hook
    {
        $hook = new Hook();
        $this->shutdown[] = $hook;

        return $hook;
    }

    /**
     * Error
     *
     * An error callback for failed or no matched requests
     *
     * @return Hook
     */
    public function error(): Hook
    {
        $hook = new Hook();
        $this->errors[] = $hook;

        return $hook;
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
     * If a resource has been created return it, otherwise create it and then return it
     *
     * @param  string  $name
     * @return mixed
     *
     * @throws Exception
     */
    public function getResource(string $name): mixed
    {
        if (! $this->container->has($name)) {
            throw new Exception('Failed to find resource: "'.$name.'"');
        }

        return $this->container->get($name);
    }

    /**
     * Get Resources By List
     *
     * @param  array  $list
     * @return array
     *
     * @throws Exception
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
     * @param  Dependency  $dependency
     * @return void
     *
     * @throws Exception
     */
    public function setResource(Dependency $dependency): void
    {
        $this->container->set($dependency);
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
        return $this->tasks[$this->command] ?? null;
    }

    /**
     * Get Params
     * Get runtime params for the provided Hook
     *
     * @param  Hook  $hook
     * @return array
     *
     * @throws Exception
     */
    protected function getParams(Hook $hook): array
    {
        $params = [];

        foreach ($hook->getParams() as $key => $param) {
            $value = (isset($this->args[$key])) ? $this->args[$key] : $param['default'];

            $this->validate($key, $param, $value);

            $params[$this->camelCaseIt($key)] = $value;
        }

        foreach ($hook->getDependencies() as $dependency) {
            if (array_key_exists($this->camelCaseIt($dependency), $params)) {
                continue;
            }

            $params[$this->camelCaseIt($dependency)] = $this->getResource($dependency);
        }

        return $params;
    }

    /**
     * Run
     *
     * @return $this
     *
     * @throws Exception
     */
    public function run(): self
    {
        $this->adapter->start(function () {
            $command = $this->match();

            try {
                if ($command) {
                    foreach ($this->init as $hook) {
                        \call_user_func_array($hook->getAction(), $this->getParams($hook));
                    }

                    // Call the callback with the matched positions as params
                    \call_user_func_array($command->getAction(), $this->getParams($command));

                    foreach ($this->shutdown as $hook) {
                        \call_user_func_array($hook->getAction(), $this->getParams($hook));
                    }
                } else {
                    throw new Exception('No command found');
                }
            } catch (Exception $e) {
                foreach ($this->errors as $hook) {
                    $error = new Dependency();
                    $error->setName('error')->setCallback(fn () => $e);

                    $this->setResource($error);
                    \call_user_func_array($hook->getAction(), $this->getParams($hook));
                }
            }
        });

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

    public function setContainer($container): self
    {
        $this->container = $container;

        return $this;
    }

    public function reset(): void
    {
        $this->container = new Container();
    }

    private function camelCaseIt($key): string
    {
        $key = str_replace('-', '_', $key);
        $camelCase = \lcfirst(\str_replace('_', '', \ucwords($key, '_')));

        return  $camelCase;
    }
}
