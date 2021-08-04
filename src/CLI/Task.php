<?php

namespace Utopia\CLI;

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
     * @param bool $optional
     *
     * @return $this
     */
    public function param(string $key, $default, $validator, string $description = '', string $prompt = '', array $options = [], int $numSelect, bool $optional = false): self
    {
        $this->params[$key] = array(
            'default'       => $default,
            'validator'     => $validator,
            'description'   => $description,
            'prompt'        => $prompt,
            'options'       => $options,
            'numSelect'     => $numSelect,
            'optional'      => $optional,
            'value'         => null,
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
