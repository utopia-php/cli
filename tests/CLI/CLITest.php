<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\CLI\Adapters\Generic;
use Utopia\CLI\CLI;
use Utopia\DI\Container;
use Utopia\Validator\ArrayList;
use Utopia\Validator\Text;

class CLITest extends TestCase
{
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testResources()
    {
        $cli = new CLI(new Generic(), ['test.php', 'build']);

        $cli->setResource('rand', fn () => rand());
        $cli->setResource('first', fn ($second) => 'first-'.$second, ['second']);
        $cli->setResource('second', fn () => 'second');

        $second = $cli->getResource('second');
        $first = $cli->getResource('first');
        $this->assertEquals('second', $second);
        $this->assertEquals('first-second', $first);

        $resource = $cli->getResource('rand');

        $this->assertNotEmpty($resource);
        $this->assertEquals($resource, $cli->getResource('rand'));
        $this->assertEquals($resource, $cli->getResource('rand'));
        $this->assertEquals($resource, $cli->getResource('rand'));
    }

    public function testAppSuccess()
    {
        ob_start();

        $cli = new CLI(new Generic(), ['test.php', 'build', '--email=me@example.com']); // Mock command request

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

        $cli = new CLI(new Generic(), ['test.php', 'build', '--email=me.example.com']); // Mock command request

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

        $cli = new CLI(new Generic(), ['test.php', 'build', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

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
        $cli = new CLI(new Generic(), ['test.php', 'build', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

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
        $cli = new CLI(new Generic(), ['test.php', 'build', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

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

    public function testHook()
    {
        $cli = new CLI(new Generic(), ['test.php', 'build', '--email=me@example.com', '--list=item1', '--list=item2']);

        $cli
            ->init()
            ->action(function () {
                echo '(init)-';
            });

        $cli
            ->shutdown()
            ->action(function () {
                echo '-(shutdown)';
            });

        $cli
            ->task('build')
            ->param('email', null, new Text(0), 'Valid email address')
            ->param('list', null, new ArrayList(new Text(256)), 'List of strings')
            ->action(function ($email, $list) {
                echo $email.'-'.implode('-', $list);
            });

        \ob_start();

        $cli->run();
        $result = \ob_get_clean();

        $this->assertEquals('(init)-me@example.com-item1-item2-(shutdown)', $result);
    }

    public function testInjection()
    {
        ob_start();

        $cli = new CLI(new Generic(), ['test.php', 'build', '--email=me@example.com']);

        $cli->setResource('test', fn () => 'test-value');

        $cli->task('build')
            ->inject('test')
            ->param('email', null, new Text(15), 'valid email address')
            ->action(function ($test, $email) {
                echo $test.'-'.$email;
            });

        $cli->run();

        $result = ob_get_clean();

        $this->assertEquals('test-value-me@example.com', $result);
    }

    public function testProvidedContainer()
    {
        ob_start();

        $container = new Container();
        $container->set('test', fn () => 'test-value');

        $cli = new CLI(new Generic(), ['test.php', 'build'], $container);

        $this->assertNotSame($container, $cli->getContainer());
        $this->assertEquals('test-value', $cli->getResource('test'));

        $cli->task('build')
            ->inject('test')
            ->action(function ($test) {
                echo $test;
            });

        $cli->run();

        $result = ob_get_clean();

        $this->assertEquals('test-value', $result);
    }

    public function testResetPreservesInjectedContainer()
    {
        $container = new Container();
        $container->set('base', fn () => 'base-value');

        $cli = new CLI(new Generic(), ['test.php', 'build'], $container);
        $cli->setResource('runtime', fn () => 'runtime-value');

        $this->assertEquals('base-value', $cli->getResource('base'));
        $this->assertEquals('runtime-value', $cli->getResource('runtime'));

        $cli->reset();

        $this->assertEquals('base-value', $cli->getResource('base'));

        $this->expectException(\Exception::class);
        $cli->getResource('runtime');
    }

    public function testMatch()
    {
        $cli = new CLI(new Generic(), ['test.php', 'build2', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

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

        $cli = new CLI(new Generic(), ['test.php', 'buildx', '--email=me@example.com', '--list=item1', '--list=item2']); // Mock command request

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

    public function testEscaping()
    {
        ob_start();

        $database = 'appwrite://database_db_fra1_self_hosted_0_0?database=appwrite&namespace=_1';

        $cli = new CLI(new Generic(), ['test.php', 'connect', '--database='.$database]);

        $cli
            ->task('connect')
            ->param('database', null, new Text(2048), 'Database DSN')
            ->action(function ($database) {
                echo $database;
            });

        $cli->run();

        $result = ob_get_clean();

        $this->assertEquals($database, $result);
    }
}
