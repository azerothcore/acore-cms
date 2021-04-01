<?php

namespace Doctrine\Bundle\DoctrineBundle\Tests\Mapping;

use Doctrine\Bundle\DoctrineBundle\Mapping\ContainerEntityListenerResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Container\ContainerInterface;

class ContainerEntityListenerResolverTest extends TestCase
{
    /** @var ContainerEntityListenerResolver */
    private $resolver;

    /** @var ContainerInterface|PHPUnit_Framework_MockObject_MockObject */
    private $container;

    public static function setUpBeforeClass()
    {
        if (interface_exists(EntityManagerInterface::class)) {
            return;
        }

        self::markTestSkipped('This test requires ORM');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->resolver  = new ContainerEntityListenerResolver($this->container);
    }

    public function testResolveClass()
    {
        $className = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener1';
        $object    = $this->resolver->resolve($className);

        $this->assertInstanceOf($className, $object);
        $this->assertSame($object, $this->resolver->resolve($className));
    }

    public function testRegisterClassAndResolve()
    {
        $className = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener1';
        $object    = new $className();

        $this->resolver->register($object);

        $this->assertSame($object, $this->resolver->resolve($className));
    }

    public function testRegisterServiceAndResolve()
    {
        $className = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener1';
        $serviceId = 'app.entity_listener';
        $object    = new $className();

        $this->resolver->registerService($className, $serviceId);
        $this->container
            ->expects($this->any())
            ->method('has')
            ->with($serviceId)
            ->will($this->returnValue(true));
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with($serviceId)
            ->will($this->returnValue($object));

        $this->assertInstanceOf($className, $this->resolver->resolve($className));
        $this->assertSame($object, $this->resolver->resolve($className));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage There is no service named
     */
    public function testRegisterMissingServiceAndResolve()
    {
        $className = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener1';
        $serviceId = 'app.entity_listener';

        $this->resolver->registerService($className, $serviceId);
        $this->container
            ->expects($this->any())
            ->method('has')
            ->with($serviceId)
            ->will($this->returnValue(false));

        $this->resolver->resolve($className);
    }

    public function testClearOne()
    {
        $className1 = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener1';
        $className2 = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener2';

        $obj1 = $this->resolver->resolve($className1);
        $obj2 = $this->resolver->resolve($className2);

        $this->assertInstanceOf($className1, $obj1);
        $this->assertInstanceOf($className2, $obj2);

        $this->assertSame($obj1, $this->resolver->resolve($className1));
        $this->assertSame($obj2, $this->resolver->resolve($className2));

        $this->resolver->clear($className1);

        $this->assertInstanceOf($className1, $this->resolver->resolve($className1));
        $this->assertInstanceOf($className2, $this->resolver->resolve($className2));

        $this->assertNotSame($obj1, $this->resolver->resolve($className1));
        $this->assertSame($obj2, $this->resolver->resolve($className2));
    }

    public function testClearAll()
    {
        $className1 = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener1';
        $className2 = '\Doctrine\Bundle\DoctrineBundle\Tests\Mapping\EntityListener2';

        $obj1 = $this->resolver->resolve($className1);
        $obj2 = $this->resolver->resolve($className2);

        $this->assertInstanceOf($className1, $obj1);
        $this->assertInstanceOf($className2, $obj2);

        $this->assertSame($obj1, $this->resolver->resolve($className1));
        $this->assertSame($obj2, $this->resolver->resolve($className2));

        $this->resolver->clear();

        $this->assertInstanceOf($className1, $this->resolver->resolve($className1));
        $this->assertInstanceOf($className2, $this->resolver->resolve($className2));

        $this->assertNotSame($obj1, $this->resolver->resolve($className1));
        $this->assertNotSame($obj2, $this->resolver->resolve($className2));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage An object was expected, but got "string".
     */
    public function testRegisterStringException()
    {
        $this->resolver->register('CompanyContractListener');
    }
}

class EntityListener1
{
}

class EntityListener2
{
}
