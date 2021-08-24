<?php

namespace Utopia\CLI;

use Exception;

class Task
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * Description
     *
     * @var string
     */
    protected $desc = '';

    /**
     * Action Callback
     *
     * @var callable
     */
    protected $action;

    /**
     * Parameters
     *
     * List of route params names and validators
     *
     * @var array
     */
    protected $params = [];

    /**
     * Labels
     *
     * List of route label names
     *
     * @var array
     */
    protected $labels = [];

    /**
     * Injections
     *
     * List of route required injections for action callback
     *
     * @var array
     */
    protected $injections = [];

    /**
     * Task constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->action = function (): void {
        };
    }

    /**
     * Add Description
     *
     * @param string $desc
     * @return $this
     */
    public function desc($desc): self
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * Add Action
     *
     * @param callable $action
     * @return $this
     */
    public function action(callable $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Add Param
     *
     * @param string $key
     * @param mixed $default
     * @param string $validator
     * @param string $description
     * @param string $prompt
     * @param string $options
     * @param int $numSelect
     * @param bool $optional
     * @param array $injections
     *
     * @return $this
     */
    public function param(string $key, $default, $validator, string $description = '', $prompt = '', array $options = [], int $max = 0, bool $optional = false, array $injections = []): self
    {
        if ($max < 0) {
            throw new \Exception('$max must be >= 0');
        }

        if (count($options) > 0 && $max < 1) {
            throw new \Exception('$max must be at least 1 when options are passed.');
        }

        if ($max > count($options)) {
            throw new \Exception('$max cannot be greater than the number of options');
        }

        $this->params[$key] = array(
            'default'       => $default,
            'validator'     => $validator,
            'description'   => $description,
            'prompt'        => $prompt,
            'options'       => $options,
            'max'           => $max,
            'optional'      => $optional,
            'value'         => null,
            'injections'    => $injections,
            'order'         => count($this->params) + count($this->injections),
        );

        return $this;
    }

    /**
     * Add Label
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function label(string $key, $value): self
    {
        $this->labels[$key] = $value;

        return $this;
    }

    /**
     * Inject
     *
     * @param string $injection
     *
     * @return $this
     */
    public function inject($injection): self
    {
        if (array_key_exists($injection, $this->injections)) {
            throw new Exception('Injection already declared for ' . $injection);
        }

        $this->injections[$injection] = [
            'name' => $injection,
            'order' => count($this->params) + count($this->injections),
        ];

        return $this;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDesc(): string
    {
        return $this->desc;
    }

    /**
     * Get Action
     *
     * @return callable
     */
    public function getAction(): callable
    {
        return $this->action;
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get Label
     *
     * Return given label value or default value if label doesn't exists
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getLabel(string $key, $default)
    {
        return (isset($this->labels[$key])) ? $this->labels[$key] : $default;
    }

    /**
     * Get Injections
     *
     * @return array
     */
    public function getInjections(): array
    {
        return $this->injections;
    }

    /**
     * Set Options
     * 
     * Set the options for the prompt
     */
    public function setOptions(array $options)
    {
        $this->params['options'] = $options;
        return $this;
    }
}
