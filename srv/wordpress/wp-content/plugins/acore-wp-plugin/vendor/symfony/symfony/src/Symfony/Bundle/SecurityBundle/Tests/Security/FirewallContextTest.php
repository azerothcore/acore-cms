<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\Firewall\LogoutListener;

class FirewallContextTest extends TestCase
{
    public function testGetters()
    {
        $config = new FirewallConfig('main', 'user_checker', 'request_matcher');
        $exceptionListener = $this->getExceptionListenerMock();
        $logoutListener = $this->getLogoutListenerMock();
        $listeners = [
            $this
                ->getMockBuilder(ListenerInterface::class)
                ->disableOriginalConstructor()
                ->getMock(),
        ];

        $context = new FirewallContext($listeners, $exceptionListener, $logoutListener, $config);

        $this->assertEquals($listeners, $context->getListeners());
        $this->assertEquals($exceptionListener, $context->getExceptionListener());
        $this->assertEquals($logoutListener, $context->getLogoutListener());
        $this->assertEquals($config, $context->getConfig());
    }

    /**
     * @expectedDeprecation The "Symfony\Bundle\SecurityBundle\Security\FirewallContext::getContext()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use Symfony\Bundle\SecurityBundle\Security\FirewallContext::getListeners/getExceptionListener() instead.
     * @group legacy
     */
    public function testGetContext()
    {
        $exceptionListener = $this->getExceptionListenerMock();
        $logoutListener = $this->getLogoutListenerMock();
        $context = (new FirewallContext($listeners = [], $exceptionListener, $logoutListener, new FirewallConfig('main', 'request_matcher', 'user_checker')))
            ->getContext();

        $this->assertEquals([$listeners, $exceptionListener, $logoutListener], $context);
    }

    private function getExceptionListenerMock()
    {
        return $this
            ->getMockBuilder(ExceptionListener::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getLogoutListenerMock()
    {
        return $this
            ->getMockBuilder(LogoutListener::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
