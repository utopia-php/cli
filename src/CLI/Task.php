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
     * Get Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
