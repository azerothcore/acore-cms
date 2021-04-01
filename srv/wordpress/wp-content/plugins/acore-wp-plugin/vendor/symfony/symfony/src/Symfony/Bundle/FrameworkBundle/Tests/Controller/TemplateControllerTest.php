<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class TemplateControllerTest extends TestCase
{
    public function testTwig()
    {
        $twig = $this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())->method('render')->willReturn('bar');

        $controller = new TemplateController($twig);

        $this->assertEquals('bar', $controller->templateAction('mytemplate')->getContent());
    }

    public function testTemplating()
    {
        $templating = $this->getMockBuilder(EngineInterface::class)->getMock();
        $templating->expects($this->once())->method('render')->willReturn('bar');

        $controller = new TemplateController(null, $templating);

        $this->assertEquals('bar', $controller->templateAction('mytemplate')->getContent());
    }

    /**
     * @group legacy
     */
    public function testLegacyTwig()
    {
        $twig = $this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())->method('render')->willReturn('bar');

        $container = new ContainerBuilder();
        $container->set('twig', $twig);

        $controller = new TemplateController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->templateAction('mytemplate')->getContent());
    }

    /**
     * @group legacy
     */
    public function testLegacyTemplating()
    {
        $templating = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface')->getMock();
        $templating->expects($this->once())->method('render')->willReturn('bar');

        $container = new ContainerBuilder();
        $container->set('templating', $templating);

        $controller = new TemplateController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->templateAction('mytemplate')->getContent());
    }

    public function testNoTwigNorTemplating()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('You can not use the TemplateController if the Templating Component or the Twig Bundle are not available.');
        $controller = new TemplateController();

        $controller->templateAction('mytemplate')->getContent();
    }
}
