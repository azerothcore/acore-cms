<?php

namespace Doctrine\Bundle\DoctrineBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\Bundle\DoctrineBundle\Twig\DoctrineExtension;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Bridge\Twig\Extension\CodeExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class ProfilerTest extends BaseTestCase
{
    /** @var DebugStack */
    private $logger;

    /** @var Environment */
    private $twig;

    /** @var DoctrineDataCollector */
    private $collector;

    public function setUp()
    {
        $this->logger = new DebugStack();
        $registry     = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registry->expects($this->once())->method('getManagers')->willReturn([]);
        $this->collector = new DoctrineDataCollector($registry);
        $this->collector->addLogger('foo', $this->logger);

        $twigLoaderFilesystem = new FilesystemLoader(__DIR__ . '/../Resources/views/Collector');
        $twigLoaderFilesystem->addPath(__DIR__ . '/../vendor/symfony/web-profiler-bundle/Resources/views', 'WebProfiler');
        $this->twig = new Environment($twigLoaderFilesystem, ['debug' => true, 'strict_variables' => true]);

        $fragmentHandler = $this->getMockBuilder(FragmentHandler::class)->disableOriginalConstructor()->getMock();
        $fragmentHandler->method('render')->willReturn('');

        $kernelRuntime = new HttpKernelRuntime($fragmentHandler);

        $urlGenerator = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();
        $urlGenerator->method('generate')->willReturn('');

        $this->twig->addExtension(new CodeExtension('', '', ''));
        $this->twig->addExtension(new RoutingExtension($urlGenerator));
        $this->twig->addExtension(new HttpKernelExtension($fragmentHandler));
        $this->twig->addExtension(new WebProfilerExtension());
        $this->twig->addExtension(new DoctrineExtension());

        $loader = $this->getMockBuilder(RuntimeLoaderInterface::class)->getMock();
        $loader->method('load')->willReturn($kernelRuntime);
        $this->twig->addRuntimeLoader($loader);
    }

    public function testRender()
    {
        $this->logger->queries = [
            [
                'sql' => 'SELECT * FROM foo WHERE bar IN (?, ?)',
                'params' => ['foo', 'bar'],
                'types' => null,
                'executionMS' => 1,
            ],
        ];

        $this->collector->collect($request = new Request(['group' => '0']), $response = new Response());

        $profile = new Profile('foo');

        $output = $this->twig->render('db.html.twig', [
            'request' => $request,
            'token' => 'foo',
            'page' => 'foo',
            'profile' => $profile,
            'collector' => $this->collector,
            'queries' => $this->logger->queries,
        ]);

        $output = str_replace(["\e[37m", "\e[0m", "\e[32;1m", "\e[34;1m"], '', $output);
        $this->assertContains("SELECT * FROM foo WHERE bar IN ('foo', 'bar');", $output);
    }
}
