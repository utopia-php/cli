<?php

namespace Utopia\Tests;

use Utopia\CLI\Task;
use Utopia\Validator\Text;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{

    public function setUp(): void
    {
        
    }

    public function tearDown(): void
    {
        
    }

    public function testName():void
    {
        $task = new Task('test');
        $this->assertEquals('test', $task->getName());
    }

    public function testDescription():void
    {
        $task = new Task('test');
        $task->desc('test task');

        $this->assertEquals('test task', $task->getDesc());
    }

    public function testAction():void
    {
        $task = new Task('test');
        $task->action(function () {
            return 'result';
        });

        $this->assertEquals('result', $task->getAction()());
    }

    public function testLabel():void
    {
        $task = new Task('test');
        $task->label('key', 'value');

        $this->assertEquals('value', $task->getLabel('key', 'default'));
        $this->assertEquals('default', $task->getLabel('unknown', 'default'));
    }

    public function testParam():void
    {
        $task = new Task('test');
        $task->param('email', 'me@example.com', new Text(0), 'Param with valid email address', false);

        $this->assertCount(1, $task->getParams());
    }

    public function testResources():void
    {
        $task = new Task('test');
        $this->assertEquals([], $task->getInjections());

        $task
            ->inject('user')
            ->inject('time')
            ->action(function () {
            })
        ;

        $this->assertCount(2, $task->getInjections());
        $this->assertEquals('user', $task->getInjections()['user']['name']);
        $this->assertEquals('time', $task->getInjections()['time']['name']);
    }
}
