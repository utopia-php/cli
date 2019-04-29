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
     * @var null|callback
     */
    protected $action = null;

    /**
     * Parameters
     *
     * List of route params names and validators
     *
     * @var array
     */
    protected $params = array();

    /**
     * Labels
     *
     * List of route label names
     *
     * @var array
     */
    protected $labels = array();

    /**
     * Task constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Add Description
     *
     * @param string $desc
     * @return $this
     */
    public function desc($desc)
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * Add Action
     *
     * @param $action
     * @return $this
     */
    public function action($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Add Param
     *
     * @param string $key
     * @param null $default
     * @param string $validator
     * @param string $description
     * @param bool $optional
     *
     * @return $this
     */
    public function param($key, $default, $validator, $description = '', $optional = false)
    {
        $this->params[$key] = array(
            'default'       => $default,
            'validator'     => $validator,
            'description'   => $description,
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
    public function label($key, $value)
    {
        $this->labels[$key] = $value;

        return $this;
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * Get Action
     *
     * @return callable|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams()
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
    public function getLabel($key, $default)
    {
        return (isset($this->labels[$key])) ? $this->labels[$key] : $default;
    }
}