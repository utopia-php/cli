<?php

namespace Utopia\CLI;

use Utopia\Validator;
use Exception;
use Utopia\Hook;

class Task extends Hook
{
    /**
     * @var string
     */
    protected string $name = '';

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
