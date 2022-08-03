<?php

namespace Utopia\CLI;

use Utopia\Validator;
use Exception;

class Task
{
    /**
     * @var string
     */
    protected string $name = '';

    /**
     * Description
     *
     * @var string
     */
    protected string $desc = '';

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
    protected array $params = [];

    /**
     * Injections
     *
     * List of route required injections for action callback
     *
     * @var array
     */
    protected array $injections = [];

    /**
     * Labels
     *
     * List of route label names
     *
     * @var array
     */
    protected array $labels = [];

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
     * @param Validator $validator
     * @param string $description
     * @param bool $optional
     *
     * @return $this
     */
    public function param(string $key, $default, Validator $validator, string $description = '', bool $optional = false): self
    {
        $this->params[$key] = array(
            'default'       => $default,
            'validator'     => $validator,
            'description'   => $description,
            'optional'      => $optional,
            'value'         => null,
            'order' => count($this->params) + count($this->injections),
        );

        return $this;
    }

    /**
     * Inject
     *
     * @param string $injection
     *
     * @throws Exception
     *
     * @return static
     */
    public function inject(string $injection): static
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
     * Get Injections
     *
     * @return array
     */
    public function getInjections(): array
    {
        return $this->injections;
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
    public function getLabel(string $key, $default): mixed
    {
        return (isset($this->labels[$key])) ? $this->labels[$key] : $default;
    }
}
