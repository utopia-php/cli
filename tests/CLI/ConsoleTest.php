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

use Utopia\CLI\Console;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testLogs()
    {
        // Use vars to resolve adapter key
        $this->assertEquals(4, Console::log('log'));
        $this->assertEquals(17, Console::success('success'));
        $this->assertEquals(14, Console::info('info'));
        $this->assertEquals(19, Console::warning('warning'));
        $this->assertEquals(15, Console::error('error'));
        $this->assertEquals('this is an answer', Console::confirm('this is a question'));
    }

    public function testExecuteBasic()
    {
        $stdout = '';
        $stderr = '';
        $stdin = '';
        $code = Console::execute('php -r "echo \'hello world\';"', $stdin, $stdout, $stderr, 10);

        $this->assertEquals('', $stderr);
        $this->assertEquals('hello world', $stdout);
        $this->assertEquals(0, $code);
    }

    public function testExecuteStdOut()
    {
        $stdout = '';
        $stderr = '';
        $stdin = '';
        $code = Console::execute('>&1 echo "success"', $stdin, $stdout, $stderr, 3);

        $this->assertEquals('', $stderr);
        $this->assertEquals("success\n", $stdout);
        $this->assertEquals(0, $code);
    }

    public function testExecuteStdErr()
    {
        $stdout = '';
        $stderr = '';
        $stdin = '';
        $code = Console::execute('>&2 echo "error"', $stdin, $stdout, $stderr, 3);

        $this->assertEquals("error\n", $stderr);
        $this->assertEquals('', $stdout);
        $this->assertEquals(0, $code);
    }

    public function testExecuteExitCode()
    {
        $stdout = '';
        $stderr = '';
        $stdin = '';
        $code = Console::execute('php -r "echo \'hello world\'; exit(2);"', $stdin, $stdout, $stderr, 10);

        $this->assertEquals('', $stderr);
        $this->assertEquals('hello world', $stdout);
        $this->assertEquals(2, $code);

        $stdout = '';
        $stderr = '';
        $stdin = '';
        $code = Console::execute('php -r "echo \'hello world\'; exit(100);"', $stdin, $stdout, $stderr, 10);

        $this->assertEquals('', $stderr);
        $this->assertEquals('hello world', $stdout);
        $this->assertEquals(100, $code);
    }

    public function testExecuteTimeout()
    {
        $stdout = '';
        $stderr = '';
        $stdin = '';
        $code = Console::execute('php -r "sleep(1); echo \'hello world\'; exit(0);"', $stdin, $stdout, $stderr, 3);

        $this->assertEquals('', $stderr);
        $this->assertEquals('hello world', $stdout);
        $this->assertEquals(0, $code);

        $stdout = '';
        $stderr = '';
        $stdin = '';
        $code = Console::execute('php -r "sleep(4); echo \'hello world\'; exit(0);"', $stdin, $stdout, $stderr, 3);

        $this->assertEquals('', $stderr);
        $this->assertEquals('', $stdout);
        $this->assertEquals(1, $code);
    }

    public function testLoop()
    {
        $file = __DIR__.'/../resources/loop.php';
        $stdin = '';
        $stdout = '';
        $stderr = '';
        $code = Console::execute('php '.$file, $stdin, $stdout, $stderr, 30);

        $this->assertEquals('', $stderr);
        $this->assertGreaterThan(30, count(explode("\n", $stdout)));
        $this->assertLessThan(50, count(explode("\n", $stdout)));
        $this->assertEquals(1, $code);
    }
}
