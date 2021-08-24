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

use Utopia\CLI\CLI;
use PHPUnit\Framework\TestCase;
use Utopia\Validator\ArrayList;
use Utopia\Validator\Text;


CLI::setResource('rand', function () {return rand();});
CLI::setResource('first', function ($second) {return 'first-'.$second;}, ['second']);
CLI::setResource('second', function () {return 'second';});
class CLITest extends TestCase
{
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testAppSuccess()
    {
        ob_start();

        $cli = new CLI(['test.php', 'build', '--email=me@example.com']); // Mock command request

        $cli
            ->task('build')
            ->param('email', null, new Text(0), 'Valid email address')
            ->action(function ($email) {
                echo $email;
            });

        $cli->run();

        $result = ob_get_clean();

        $this->assertEquals('me@example.com', $result);
    }

    public function testAppFailure()
    {
        ob_start();

        $cli = new CLI(['test.php', 'build', '--email=me.example.com']); // Mock command request

        $cli
            ->task('build')
            ->param('email', null, new Text(10), 'Valid email address')
            ->action(function ($email) {
                echo $email;
            });

        $cli->run();

        $result = ob_get_clean();

        $this->assertEquals('', $result);
    }

    public function testAppArray()
    {
        ob_start();

        $cli = new CLI(['test.php', 'build', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

        $cli
            ->task('build')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $cli->run();

        $result = ob_get_clean();

        $this->assertEquals('me@example.com-item1-item2', $result);
    }

    public function testGetTasks()
    {
        $cli = new CLI(['test.php', 'build', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

        $cli
            ->task('build1')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $cli
            ->task('build2')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $this->assertCount(2, $cli->getTasks());
    }

    public function testGetArgs()
    {
        $cli = new CLI(['test.php', 'build', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

        $cli
            ->task('build1')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $cli
            ->task('build2')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $this->assertCount(2, $cli->getArgs());
        $this->assertEquals(['email' => 'me@example.com', 'list' => ['item1', 'item2']], $cli->getArgs());
    }

    public function testMatch()
    {
        $cli = new CLI(['test.php', 'build2', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

        $cli
            ->task('build1')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $cli
            ->task('build2')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $this->assertEquals('build2', $cli->match()->getName());

        $cli = new CLI(['test.php', 'buildx', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

        $cli
            ->task('build1')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $cli
            ->task('build2')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        $this->assertEquals(null, $cli->match());
    }

    public function testResources()
    {
        $cli = new CLI(['test.php', 'build1']);

        $second = $cli->getResource('second');
        $first = $cli->getResource('first');
        $this->assertEquals('second', $second);
        $this->assertEquals('first-second', $first);

        $resource = $cli->getResource('rand');

        $this->assertNotEmpty($resource);
        $this->assertEquals($resource, $cli->getResource('rand'));
        $this->assertEquals($resource, $cli->getResource('rand'));
        $this->assertEquals($resource, $cli->getResource('rand'));

        $cli
            ->task('build1')
            ->inject('rand')
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function($x, $y, $rand) {
                echo $x.'-'.$y.'-'.$rand;
            })
        ;

        \ob_start();
        
        $cli->run();
        $result = ob_get_clean();

        $this->assertEquals('x-def-y-def-'.$resource, $result);
    }
}