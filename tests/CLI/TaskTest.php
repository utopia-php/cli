<?php

namespace Utopia\Tests;

use Utopia\CLI\Task;
use Utopia\Validator\Text;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    /**
     * @var Task
     */
    protected $task;

    public function setUp(): void
    {
        $this->task = new Task('test');
    }

    public function tearDown(): void
    {
        unset($this->task);
    }

    public function testName(): void
    {
        $this->assertEquals('test', $this->task->getName());
    }

    public function testDescription(): void
    {
        $this->task->desc('test task');

        $this->assertEquals('test task', $this->task->getDesc());
    }

    public function testAction(): void
    {
        $this->task->action(function () {
            return 'result';
        });

        $this->assertEquals('result', $this->task->getAction()());
    }

    public function testLabel(): void
    {
        $this->task->label('key', 'value');

        $this->assertEquals('value', $this->task->getLabel('key', 'default'));
        $this->assertEquals('default', $this->task->getLabel('unknown', 'default'));
    }

    public function testParam(): void
    {
        $this->task->param('email', 'me@example.com', new Text(0), 'Param with valid email address', false);

        $this->assertCount(1, $this->task->getParams());
    }

    public function testResources(): void
    {
        $this->assertEquals([], $this->task->getInjections());

        $this->task
            ->inject('user')
            ->inject('time')
            ->action(function () {
            })
        ;

        $this->assertCount(2, $this->task->getInjections());
        $this->assertEquals('user', $this->task->getInjections()['user']['name']);
        $this->assertEquals('time', $this->task->getInjections()['time']['name']);
    }
}
