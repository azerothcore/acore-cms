<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Command\RouterDebugCommand;
use Symfony\Bundle\FrameworkBundle\Command\RouterMatchCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouterMatchCommandTest extends TestCase
{
    public function testWithMatchPath()
    {
        $tester = $this->createCommandTester();
        $ret = $tester->execute(['path_info' => '/foo', 'foo'], ['decorated' => false]);

        $this->assertEquals(0, $ret, 'Returns 0 in case of success');
        $this->assertStringContainsString('Route Name   | foo', $tester->getDisplay());
    }

    public function testWithNotMatchPath()
    {
        $tester = $this->createCommandTester();
        $ret = $tester->execute(['path_info' => '/test', 'foo'], ['decorated' => false]);

        $this->assertEquals(1, $ret, 'Returns 1 in case of failure');
        $this->assertStringContainsString('None of the routes match the path "/test"', $tester->getDisplay());
    }

    /**
     * @group legacy
     * @expectedDeprecation Symfony\Bundle\FrameworkBundle\Command\RouterMatchCommand::__construct() expects an instance of "Symfony\Component\Routing\RouterInterface" as first argument since Symfony 3.4. Not passing it is deprecated and will throw a TypeError in 4.0.
     * @expectedDeprecation Symfony\Bundle\FrameworkBundle\Command\RouterDebugCommand::__construct() expects an instance of "Symfony\Component\Routing\RouterInterface" as first argument since Symfony 3.4. Not passing it is deprecated and will throw a TypeError in 4.0.
     */
    public function testLegacyMatchCommand()
    {
        $application = new Application($this->getKernel());
        $application->add(new RouterMatchCommand());
        $application->add(new RouterDebugCommand());

        $tester = new CommandTester($application->find('router:match'));

        $tester->execute(['path_info' => '/']);

        $this->assertStringContainsString('None of the routes match the path "/"', $tester->getDisplay());
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester()
    {
        $application = new Application($this->getKernel());
        $application->add(new RouterMatchCommand($this->getRouter()));
        $application->add(new RouterDebugCommand($this->getRouter()));

        return new CommandTester($application->find('router:match'));
    }

    private function getRouter()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('foo', new Route('foo'));
        $requestContext = new RequestContext();
        $router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')->getMock();
        $router
            ->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);
        $router
            ->expects($this->any())
            ->method('getContext')
            ->willReturn($requestContext);

        return $router;
    }

    private function getKernel()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')->getMock();
        $container
            ->expects($this->atLeastOnce())
            ->method('has')
            ->willReturnCallback(function ($id) {
                return 'console.command_loader' !== $id;
            })
        ;
        $container
            ->expects($this->any())
            ->method('get')
            ->with('router')
            ->willReturn($this->getRouter())
        ;

        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        $kernel
            ->expects($this->any())
            ->method('getContainer')
            ->willReturn($container)
        ;
        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->willReturn([])
        ;

        return $kernel;
    }
}
