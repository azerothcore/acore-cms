<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Command\LintCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class LintCommandTest extends TestCase
{
    private $files;

    public function testLintCorrectFile()
    {
        $tester = $this->createCommandTester();
        $filename = $this->createFile('{{ foo }}');

        $ret = $tester->execute(['filename' => [$filename]], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'decorated' => false]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('OK in', trim($tester->getDisplay()));
    }

    public function testLintIncorrectFile()
    {
        $tester = $this->createCommandTester();
        $filename = $this->createFile('{{ foo');

        $ret = $tester->execute(['filename' => [$filename]], ['decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertMatchesRegularExpression('/ERROR  in \S+ \(line /', trim($tester->getDisplay()));
    }

    public function testLintFileNotReadable()
    {
        $this->expectException('RuntimeException');
        $tester = $this->createCommandTester();
        $filename = $this->createFile('');
        unlink($filename);

        $tester->execute(['filename' => [$filename]], ['decorated' => false]);
    }

    public function testLintFileCompileTimeException()
    {
        $tester = $this->createCommandTester();
        $filename = $this->createFile("{{ 2|number_format(2, decimal_point='.', ',') }}");

        $ret = $tester->execute(['filename' => [$filename]], ['decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of error');
        $this->assertMatchesRegularExpression('/ERROR  in \S+ \(line /', trim($tester->getDisplay()));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing a command name as the first argument of "Symfony\Bridge\Twig\Command\LintCommand::__construct()" is deprecated since Symfony 3.4 and support for it will be removed in 4.0. If the command was registered by convention, make it a service instead.
     */
    public function testLegacyLintCommand()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The Twig environment needs to be set.');
        $command = new LintCommand();

        $application = new Application();
        $application->add($command);
        $command = $application->find('lint:twig');

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester()
    {
        $command = new LintCommand(new Environment(new FilesystemLoader()));

        $application = new Application();
        $application->add($command);
        $command = $application->find('lint:twig');

        return new CommandTester($command);
    }

    /**
     * @return string Path to the new file
     */
    private function createFile($content)
    {
        $filename = tempnam(sys_get_temp_dir(), 'sf-');
        file_put_contents($filename, $content);

        $this->files[] = $filename;

        return $filename;
    }

    protected function setUp()
    {
        $this->files = [];
    }

    protected function tearDown()
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
