<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\PhpUnit\Legacy;

/**
 * Collects and replays skipped tests.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
class SymfonyTestsListenerForV5 extends \PHPUnit_Framework_BaseTestListener
{
    private $trait;

    public function __construct(array $mockedNamespaces = array())
    {
        $this->trait = new SymfonyTestsListenerTrait($mockedNamespaces);
    }

    public function globalListenerDisabled()
    {
        $this->trait->globalListenerDisabled();
    }

    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->trait->startTestSuite($suite);
    }

    public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $this->trait->addSkippedTest($test, $e, $time);
    }

    public function startTest(\PHPUnit_Framework_Test $test)
    {
        $this->trait->startTest($test);
    }

    public function addWarning(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_Warning $e, $time)
    {
        $this->trait->addWarning($test, $e, $time);
    }

    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        $this->trait->endTest($test, $time);
    }
}
