<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Serializer\Mapping\Loader\XmlFileLoader;
use Symfony\Component\Serializer\Mapping\Loader\YamlFileLoader;

class SerializerCacheWarmerTest extends TestCase
{
    public function testWarmUp()
    {
        $loaders = [
            new XmlFileLoader(__DIR__.'/../Fixtures/Serialization/Resources/person.xml'),
            new YamlFileLoader(__DIR__.'/../Fixtures/Serialization/Resources/author.yml'),
        ];

        $file = sys_get_temp_dir().'/cache-serializer.php';
        @unlink($file);

        $fallbackPool = new ArrayAdapter();

        $warmer = new SerializerCacheWarmer($loaders, $file, $fallbackPool);
        $warmer->warmUp(\dirname($file));

        $this->assertFileExists($file);

        $arrayPool = new PhpArrayAdapter($file, new NullAdapter());

        $this->assertTrue($arrayPool->getItem('Symfony_Bundle_FrameworkBundle_Tests_Fixtures_Serialization_Person')->isHit());
        $this->assertTrue($arrayPool->getItem('Symfony_Bundle_FrameworkBundle_Tests_Fixtures_Serialization_Author')->isHit());

        $values = $fallbackPool->getValues();

        $this->assertIsArray($values);
        $this->assertCount(2, $values);
        $this->assertArrayHasKey('Symfony_Bundle_FrameworkBundle_Tests_Fixtures_Serialization_Person', $values);
        $this->assertArrayHasKey('Symfony_Bundle_FrameworkBundle_Tests_Fixtures_Serialization_Author', $values);
    }

    public function testWarmUpWithoutLoader()
    {
        $file = sys_get_temp_dir().'/cache-serializer-without-loader.php';
        @unlink($file);

        $fallbackPool = new ArrayAdapter();

        $warmer = new SerializerCacheWarmer([], $file, $fallbackPool);
        $warmer->warmUp(\dirname($file));

        $this->assertFileExists($file);

        $values = $fallbackPool->getValues();

        $this->assertIsArray($values);
        $this->assertCount(0, $values);
    }

    /**
     * Test that the cache warming process is not broken if a class loader
     * throws an exception (on class / file not found for example).
     */
    public function testClassAutoloadException()
    {
        $this->assertFalse(class_exists($mappedClass = 'AClassThatDoesNotExist_FWB_CacheWarmer_SerializerCacheWarmerTest', false));

        $warmer = new SerializerCacheWarmer([new YamlFileLoader(__DIR__.'/../Fixtures/Serialization/Resources/does_not_exist.yaml')], tempnam(sys_get_temp_dir(), __FUNCTION__), new ArrayAdapter());

        spl_autoload_register($classLoader = function ($class) use ($mappedClass) {
            if ($class === $mappedClass) {
                throw new \DomainException('This exception should be caught by the warmer.');
            }
        }, true, true);

        $warmer->warmUp('foo');

        spl_autoload_unregister($classLoader);
    }

    /**
     * Test that the cache warming process is broken if a class loader throws an
     * exception but that is unrelated to the class load.
     */
    public function testClassAutoloadExceptionWithUnrelatedException()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This exception should not be caught by the warmer.');

        $this->assertFalse(class_exists($mappedClass = 'AClassThatDoesNotExist_FWB_CacheWarmer_SerializerCacheWarmerTest', false));

        $warmer = new SerializerCacheWarmer([new YamlFileLoader(__DIR__.'/../Fixtures/Serialization/Resources/does_not_exist.yaml')], tempnam(sys_get_temp_dir(), __FUNCTION__), new ArrayAdapter());

        spl_autoload_register($classLoader = function ($class) use ($mappedClass) {
            if ($class === $mappedClass) {
                eval('class '.$mappedClass.'{}');
                throw new \DomainException('This exception should not be caught by the warmer.');
            }
        }, true, true);

        $warmer->warmUp('foo');

        spl_autoload_unregister($classLoader);
    }
}
