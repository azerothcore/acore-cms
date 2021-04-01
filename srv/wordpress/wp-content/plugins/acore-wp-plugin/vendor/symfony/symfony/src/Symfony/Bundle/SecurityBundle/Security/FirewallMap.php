<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Security;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\FirewallMapInterface;

/**
 * This is a lazy-loading firewall map implementation.
 *
 * Listeners will only be initialized if we really need them.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FirewallMap extends _FirewallMap implements FirewallMapInterface
{
    /**
     * @deprecated since version 3.3, to be removed in 4.0 alongside with magic methods below
     */
    private $container;

    /**
     * @deprecated since version 3.3, to be removed in 4.0 alongside with magic methods below
     */
    private $map;

    public function __construct(ContainerInterface $container, $map)
    {
        parent::__construct($container, $map);
        $this->container = $container;
        $this->map = $map;
    }

    /**
     * @internal
     */
    public function __get($name)
    {
        if ('map' === $name || 'container' === $name) {
            @trigger_error(sprintf('Using the "%s::$%s" property is deprecated since Symfony 3.3 as it will be removed/private in 4.0.', __CLASS__, $name), \E_USER_DEPRECATED);

            if ('map' === $name && $this->map instanceof \Traversable) {
                $this->map = iterator_to_array($this->map);
            }
        }

        return $this->$name;
    }

    /**
     * @internal
     */
    public function __set($name, $value)
    {
        if ('map' === $name || 'container' === $name) {
            @trigger_error(sprintf('Using the "%s::$%s" property is deprecated since Symfony 3.3 as it will be removed/private in 4.0.', __CLASS__, $name), \E_USER_DEPRECATED);

            $set = \Closure::bind(function ($name, $value) { $this->$name = $value; }, $this, parent::class);
            $set($name, $value);
        }

        $this->$name = $value;
    }

    /**
     * @internal
     */
    public function __isset($name)
    {
        if ('map' === $name || 'container' === $name) {
            @trigger_error(sprintf('Using the "%s::$%s" property is deprecated since Symfony 3.3 as it will be removed/private in 4.0.', __CLASS__, $name), \E_USER_DEPRECATED);
        }

        return isset($this->$name);
    }

    /**
     * @internal
     */
    public function __unset($name)
    {
        if ('map' === $name || 'container' === $name) {
            @trigger_error(sprintf('Using the "%s::$%s" property is deprecated since Symfony 3.3 as it will be removed/private in 4.0.', __CLASS__, $name), \E_USER_DEPRECATED);

            $unset = \Closure::bind(function ($name) { unset($this->$name); }, $this, parent::class);
            $unset($name);
        }

        unset($this->$name);
    }
}

/**
 * @internal to be removed in 4.0
 */
class _FirewallMap
{
    private $container;
    private $map;

    public function __construct(ContainerInterface $container, $map)
    {
        $this->container = $container;
        $this->map = $map;
    }

    public function getListeners(Request $request)
    {
        $context = $this->getFirewallContext($request);

        if (null === $context) {
            return [[], null, null];
        }

        return [$context->getListeners(), $context->getExceptionListener(), $context->getLogoutListener()];
    }

    /**
     * @return FirewallConfig|null
     */
    public function getFirewallConfig(Request $request)
    {
        $context = $this->getFirewallContext($request);

        if (null === $context) {
            return null;
        }

        return $context->getConfig();
    }

    /**
     * @return FirewallContext|null
     */
    private function getFirewallContext(Request $request)
    {
        if ($request->attributes->has('_firewall_context')) {
            $storedContextId = $request->attributes->get('_firewall_context');
            foreach ($this->map as $contextId => $requestMatcher) {
                if ($contextId === $storedContextId) {
                    return $this->container->get($contextId);
                }
            }

            $request->attributes->remove('_firewall_context');
        }

        foreach ($this->map as $contextId => $requestMatcher) {
            if (null === $requestMatcher || $requestMatcher->matches($request)) {
                $request->attributes->set('_firewall_context', $contextId);

                return $this->container->get($contextId);
            }
        }

        return null;
    }
}
