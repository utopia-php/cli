<?php
/**
 * Utopia PHP Framework
 *
 * @package CLI
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

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
        $this->task = null;
    }

    public function testName()
    {
        $this->assertEquals('test', $this->task->getName());
    }

    public function testDescription()
    {
        $this->task->desc('test task');

        $this->assertEquals('test task', $this->task->getDesc());
    }

    public function testAction()
    {
        $this->task->action(function () {
            return 'result';
        });

        $this->assertEquals('result', $this->task->getAction()());
    }

    public function testLabel()
    {
        $this->task->label('key', 'value');

        $this->assertEquals('value', $this->task->getLabel('key', 'default'));
        $this->assertEquals('default', $this->task->getLabel('unknown', 'default'));
    }

    public function testParam()
    {
        $this->task->param('email', 'me@example.com', new Text(0), 'Param with valid email address', false);

        $this->assertCount(1, $this->task->getParams());
    }
}
