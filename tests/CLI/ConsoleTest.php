<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\CLI\Console;

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
        $output = '';
        $input = '';
        $code = Console::execute('php -r "echo \'hello world\';"', $input, $output, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(0, $code);
    }

    public function testExecuteArray()
    {
        $output = '';
        $input = '';
        $cmd = ['php', '-r', "echo 'hello world';"];
        $code = Console::execute($cmd, $input, $output, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(0, $code);
    }

    // Validate existing environment variables are passed down to the executed command.
    public function testExecuteEnvVariables()
    {
        $randomData = base64_encode(random_bytes(10));
        putenv("FOO={$randomData}");

        $output = '';
        $input = '';
        $cmd = ['printenv'];
        $code = Console::execute($cmd, $input, $output, 10);

        $this->assertEquals(0, $code);

        $data = [];
        foreach (explode("\n", $output) as $row) {
            if (empty($row)) {
                continue;
            }
            $kv = explode('=', $row, 2);
            $this->assertEquals(2, count($kv), $row);
            $data[$kv[0]] = $kv[1];
        }

        $this->assertArrayHasKey('FOO', $data);
        $this->assertEquals($randomData, $data['FOO']);
    }

    public function testExecuteStream()
    {
        $output = '';
        $input = '';

        $outputStream = '';
        $code = Console::execute('printf 1 && sleep 1 && printf 2 && sleep 1 && printf 3 && sleep 1 && printf 4 && sleep 1 && printf 5', $input, $output, 10, function ($output) use (&$outputStream) {
            $outputStream .= $output;
        });

        $this->assertEquals('12345', $output);
        $this->assertEquals('12345', $outputStream);
        $this->assertEquals(0, $code);
    }

    public function testExecuteStdOut()
    {
        $output = '';
        $input = '';
        $code = Console::execute('>&1 echo "success"', $input, $output, 3);

        $this->assertEquals("success\n", $output);
        $this->assertEquals(0, $code);
    }

    public function testExecuteStdErr()
    {
        $output = '';
        $input = '';
        $code = Console::execute('>&2 echo "error"', $input, $output, 3);

        $this->assertEquals("error\n", $output);
        $this->assertEquals(0, $code);
    }

    public function testExecuteExitCode()
    {
        $output = '';
        $input = '';
        $code = Console::execute('php -r "echo \'hello world\'; exit(2);"', $input, $output, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(2, $code);

        $output = '';
        $input = '';
        $code = Console::execute('php -r "echo \'hello world\'; exit(100);"', $input, $output, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(100, $code);
    }

    public function testExecuteTimeout()
    {
        $output = '';
        $input = '';
        $code = Console::execute('php -r "sleep(1); echo \'hello world\'; exit(0);"', $input, $output, 3);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(0, $code);

        $output = '';
        $input = '';
        $code = Console::execute('php -r "sleep(4); echo \'hello world\'; exit(0);"', $input, $output, 3);

        $this->assertEquals('', $output);
        $this->assertEquals(1, $code);
    }

    public function testLoop()
    {
        $file = __DIR__.'/../resources/loop.php';
        $input = '';
        $output = '';
        $code = Console::execute('php '.$file, $input, $output, 30);

        $this->assertGreaterThan(30, count(explode("\n", $output)));
        $this->assertLessThan(50, count(explode("\n", $output)));
        $this->assertEquals(1, $code);
    }
}
