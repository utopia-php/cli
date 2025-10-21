<?php

namespace Utopia\CLI;

use Utopia\Servers\Hook;

class Task extends Hook
{
    /**
     * @var string
     */
    protected string $name = '';

    /**
     * Task constructor.
     *
     * @param  string  $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
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
