<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Mapping\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\Cache\Psr6Cache;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Tests\Fixtures\ConstraintA;
use Symfony\Component\Validator\Tests\Fixtures\PropertyGetter;
use Symfony\Component\Validator\Tests\Fixtures\PropertyGetterInterface;

class LazyLoadingMetadataFactoryTest extends TestCase
{
    const CLASS_NAME = 'Symfony\Component\Validator\Tests\Fixtures\Entity';
    const PARENT_CLASS = 'Symfony\Component\Validator\Tests\Fixtures\EntityParent';
    const INTERFACE_A_CLASS = 'Symfony\Component\Validator\Tests\Fixtures\EntityInterfaceA';
    const INTERFACE_B_CLASS = 'Symfony\Component\Validator\Tests\Fixtures\EntityInterfaceB';
    const PARENT_INTERFACE_CLASS = 'Symfony\Component\Validator\Tests\Fixtures\EntityParentInterface';

    public function testLoadClassMetadataWithInterface()
    {
        $factory = new LazyLoadingMetadataFactory(new TestLoader());
        $metadata = $factory->getMetadataFor(self::PARENT_CLASS);

        $constraints = [
            new ConstraintA(['groups' => ['Default', 'EntityParent']]),
            new ConstraintA(['groups' => ['Default', 'EntityInterfaceA', 'EntityParent']]),
        ];

        $this->assertEquals($constraints, $metadata->getConstraints());
    }

    public function testMergeParentConstraints()
    {
        $factory = new LazyLoadingMetadataFactory(new TestLoader());
        $metadata = $factory->getMetadataFor(self::CLASS_NAME);

        $constraints = [
            new ConstraintA(['groups' => [
                'Default',
                'Entity',
            ]]),
            new ConstraintA(['groups' => [
                'Default',
                'EntityParent',
                'Entity',
            ]]),
            new ConstraintA(['groups' => [
                'Default',
                'EntityInterfaceA',
                'EntityParent',
                'Entity',
            ]]),
            new ConstraintA(['groups' => [
                'Default',
                'EntityInterfaceB',
                'Entity',
            ]]),
            new ConstraintA(['groups' => [
                'Default',
                'EntityParentInterface',
                'Entity',
            ]]),
        ];

        $this->assertEquals($constraints, $metadata->getConstraints());
    }

    public function testWriteMetadataToCache()
    {
        $cache = new Psr6Cache(new ArrayAdapter());
        $factory = new LazyLoadingMetadataFactory(new TestLoader(), $cache);

        $parentClassConstraints = [
            new ConstraintA(['groups' => ['Default', 'EntityParent']]),
            new ConstraintA(['groups' => ['Default', 'EntityInterfaceA', 'EntityParent']]),
        ];

        $metadata = $factory->getMetadataFor(self::PARENT_CLASS);

        $this->assertEquals(self::PARENT_CLASS, $metadata->getClassName());
        $this->assertEquals($parentClassConstraints, $metadata->getConstraints());
        $this->assertInstanceOf(ClassMetadata::class, $cache->read(self::PARENT_CLASS));
        $this->assertInstanceOf(ClassMetadata::class, $cache->read(self::INTERFACE_A_CLASS));
    }

    public function testReadMetadataFromCache()
    {
        $loader = $this->getMockBuilder('Symfony\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $cache = $this->getMockBuilder('Symfony\Component\Validator\Mapping\Cache\CacheInterface')->getMock();
        $factory = new LazyLoadingMetadataFactory($loader, $cache);

        $metadata = new ClassMetadata(self::PARENT_CLASS);
        $metadata->addConstraint(new ConstraintA());

        $parentClass = self::PARENT_CLASS;
        $interfaceClass = self::INTERFACE_A_CLASS;

        $loader->expects($this->never())
               ->method('loadClassMetadata');

        $cache->expects($this->never())
              ->method('has');
        $cache->expects($this->exactly(2))
              ->method('read')
              ->withConsecutive(
                  [self::PARENT_CLASS],
                  [self::INTERFACE_A_CLASS]
              )
              ->willReturnCallback(function ($name) use ($metadata, $parentClass, $interfaceClass) {
                  if ($parentClass == $name) {
                      return $metadata;
                  }

                  return new ClassMetadata($interfaceClass);
              });

        $this->assertEquals($metadata, $factory->getMetadataFor(self::PARENT_CLASS));
    }

    public function testNonClassNameStringValues()
    {
        $this->expectException('Symfony\Component\Validator\Exception\NoSuchMetadataException');
        $testedValue = 'error@example.com';
        $loader = $this->getMockBuilder('Symfony\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $cache = $this->getMockBuilder('Symfony\Component\Validator\Mapping\Cache\CacheInterface')->getMock();
        $factory = new LazyLoadingMetadataFactory($loader, $cache);
        $cache
            ->expects($this->never())
            ->method('read');
        $factory->getMetadataFor($testedValue);
    }

    public function testMetadataCacheWithRuntimeConstraint()
    {
        $cache = $this->getMockBuilder('Symfony\Component\Validator\Mapping\Cache\CacheInterface')->getMock();
        $factory = new LazyLoadingMetadataFactory(new TestLoader(), $cache);

        $cache
            ->expects($this->any())
            ->method('write')
            ->willReturnCallback(function ($metadata) { serialize($metadata); })
        ;

        $cache->expects($this->any())
            ->method('read')
            ->willReturn(false);

        $metadata = $factory->getMetadataFor(self::PARENT_CLASS);
        $metadata->addConstraint(new Callback(function () {}));

        $this->assertCount(3, $metadata->getConstraints());

        $metadata = $factory->getMetadataFor(self::CLASS_NAME);

        $this->assertCount(6, $metadata->getConstraints());
    }

    public function testGroupsFromParent()
    {
        $reader = new \Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader();
        $factory = new LazyLoadingMetadataFactory($reader);
        $metadata = $factory->getMetadataFor('Symfony\Component\Validator\Tests\Fixtures\EntityStaticCarTurbo');
        $groups = [];

        foreach ($metadata->getPropertyMetadata('wheels') as $propertyMetadata) {
            $constraints = $propertyMetadata->getConstraints();
            $groups = array_replace($groups, $constraints[0]->groups);
        }

        $this->assertCount(4, $groups);
        $this->assertContains('Default', $groups);
        $this->assertContains('EntityStaticCarTurbo', $groups);
        $this->assertContains('EntityStaticCar', $groups);
        $this->assertContains('EntityStaticVehicle', $groups);
    }

    public function testMultipathInterfaceConstraint()
    {
        $factory = new LazyLoadingMetadataFactory(new PropertyGetterInterfaceConstraintLoader());
        $metadata = $factory->getMetadataFor(PropertyGetter::class);
        $constraints = $metadata->getPropertyMetadata('property');

        $this->assertCount(1, $constraints);
    }
}

class TestLoader implements LoaderInterface
{
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(new ConstraintA());
    }
}

class PropertyGetterInterfaceConstraintLoader implements LoaderInterface
{
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        if (PropertyGetterInterface::class === $metadata->getClassName()) {
            $metadata->addGetterConstraint('property', new NotBlank());
        }

        return true;
    }
}
