<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerResolver extends ContainerControllerResolver
{
    protected $parser;

    public function __construct(ContainerInterface $container, ControllerNameParser $parser, LoggerInterface $logger = null)
    {
        $this->parser = $parser;

        parent::__construct($container, $logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function createController($controller)
    {
        if (false === strpos($controller, '::') && 2 === substr_count($controller, ':')) {
            // controller in the a:b:c notation then
            $controller = $this->parser->parse($controller);
        }

        $resolvedController = parent::createController($controller);

        if (1 === substr_count($controller, ':') && \is_array($resolvedController)) {
            $resolvedController[0] = $this->configureController($resolvedController[0]);
        }

        return $resolvedController;
    }

    /**
     * {@inheritdoc}
     */
    protected function instantiateController($class)
    {
        return $this->configureController(parent::instantiateController($class));
    }

    private function configureController($controller)
    {
        if ($controller instanceof ContainerAwareInterface) {
            // @deprecated switch, to be removed in 4.0 where these classes
            // won't implement ContainerAwareInterface anymore
            switch (\get_class($controller)) {
                case RedirectController::class:
                case TemplateController::class:
                    return $controller;
            }
            $controller->setContainer($this->container);
        }
        if ($controller instanceof AbstractController && null !== $previousContainer = $controller->setContainer($this->container)) {
            $controller->setContainer($previousContainer);
        }

        return $controller;
    }
}
