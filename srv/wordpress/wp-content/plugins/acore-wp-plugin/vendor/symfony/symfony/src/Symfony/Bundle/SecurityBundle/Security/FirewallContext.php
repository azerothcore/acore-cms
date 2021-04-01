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

use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\LogoutListener;

/**
 * This is a wrapper around the actual firewall configuration which allows us
 * to lazy load the context for one specific firewall only when we need it.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FirewallContext
{
    private $listeners;
    private $exceptionListener;
    private $logoutListener;
    private $config;

    /**
     * @param \Traversable|array  $listeners
     * @param LogoutListener|null $logoutListener
     */
    public function __construct($listeners, ExceptionListener $exceptionListener = null, $logoutListener = null, FirewallConfig $config = null)
    {
        $this->listeners = $listeners;
        $this->exceptionListener = $exceptionListener;
        if ($logoutListener instanceof FirewallConfig) {
            $this->config = $logoutListener;
        } elseif (null === $logoutListener || $logoutListener instanceof LogoutListener) {
            $this->logoutListener = $logoutListener;
            $this->config = $config;
        } else {
            throw new \InvalidArgumentException(sprintf('Argument 3 passed to "%s()" must be instance of "%s" or null, "%s" given.', __METHOD__, LogoutListener::class, \is_object($logoutListener) ? \get_class($logoutListener) : \gettype($logoutListener)));
        }
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @deprecated since version 3.3, will be removed in 4.0. Use {@link getListeners()} and/or {@link getExceptionListener()} instead.
     */
    public function getContext()
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use %s::getListeners/getExceptionListener() instead.', __METHOD__, __CLASS__), \E_USER_DEPRECATED);

        return [$this->getListeners(), $this->getExceptionListener(), $this->getLogoutListener()];
    }

    /**
     * @return \Traversable|array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    public function getExceptionListener()
    {
        return $this->exceptionListener;
    }

    public function getLogoutListener()
    {
        return $this->logoutListener;
    }
}
