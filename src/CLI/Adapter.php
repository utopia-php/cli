<?php

namespace Utopia\CLI;

abstract class Adapter
{
    public int $workerNum;

    public function __construct(int $workerNum = 0)
    {
        $this->workerNum = $workerNum;
    }

    /**
     * Starts the Server.
     *
     * @param $callback
     * @return self
     */
    abstract public function start($callback): self;

    /**
     * Stops the Server.
     *
     * @return self
     */
    abstract public function stop(): self;

    /**
     * Is called when a Worker starts.
     *
     * @param  callable  $callback
     * @return self
     */
    abstract public function onWorkerStart(callable $callback): self;

    /**
     * Is called when a Worker stops.
     *
     * @param  callable  $callback
     * @return self
     */
    abstract public function onWorkerStop(callable $callback): self;

    /**
     * Is called when a job is processed.
     *
     * @param  callable  $callback
     * @return self
     */
    abstract public function onJob(callable $callback): self;
}
