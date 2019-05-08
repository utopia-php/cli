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
use Utopia\Validator\Email;
use PHPUnit\Framework\TestCase;

class CLITest extends TestCase
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    public function testAppSuccess()
    {
        ob_start();

        $cli = new CLI(['test.php', 'build', '--email=me@example.com']); // Mock command request

        $cli
            ->task('build')
            ->param('email', null, new Email(), 'Valid email address')
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
            ->param('email', null, new Email(), 'Valid email address')
            ->action(function ($email) {
                echo $email;
            });

        $cli->run();

        $result = ob_get_clean();

        $this->assertEquals('', $result);
    }
}