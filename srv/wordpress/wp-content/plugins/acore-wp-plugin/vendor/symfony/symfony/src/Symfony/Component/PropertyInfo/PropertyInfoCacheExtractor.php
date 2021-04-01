<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Adds a PSR-6 cache layer on top of an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final since version 3.3
 */
class PropertyInfoCacheExtractor implements PropertyInfoExtractorInterface
{
    private $propertyInfoExtractor;
    private $cacheItemPool;
    private $arrayCache = [];

    public function __construct(PropertyInfoExtractorInterface $propertyInfoExtractor, CacheItemPoolInterface $cacheItemPool)
    {
        $this->propertyInfoExtractor = $propertyInfoExtractor;
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($class, $property, array $context = [])
    {
        return $this->extract('isReadable', [$class, $property, $context]);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($class, $property, array $context = [])
    {
        return $this->extract('isWritable', [$class, $property, $context]);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortDescription($class, $property, array $context = [])
    {
        return $this->extract('getShortDescription', [$class, $property, $context]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLongDescription($class, $property, array $context = [])
    {
        return $this->extract('getLongDescription', [$class, $property, $context]);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = [])
    {
        return $this->extract('getProperties', [$class, $context]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = [])
    {
        return $this->extract('getTypes', [$class, $property, $context]);
    }

    /**
     * Retrieves the cached data if applicable or delegates to the decorated extractor.
     *
     * @param string $method
     *
     * @return mixed
     */
    private function extract($method, array $arguments)
    {
        try {
            $serializedArguments = serialize($arguments);
        } catch (\Exception $exception) {
            // If arguments are not serializable, skip the cache
            return \call_user_func_array([$this->propertyInfoExtractor, $method], $arguments);
        }

        // Calling rawurlencode escapes special characters not allowed in PSR-6's keys
        $key = rawurlencode($method.'.'.$serializedArguments);

        if (\array_key_exists($key, $this->arrayCache)) {
            return $this->arrayCache[$key];
        }

        $item = $this->cacheItemPool->getItem($key);

        if ($item->isHit()) {
            return $this->arrayCache[$key] = $item->get();
        }

        $value = \call_user_func_array([$this->propertyInfoExtractor, $method], $arguments);
        $item->set($value);
        $this->cacheItemPool->save($item);

        return $this->arrayCache[$key] = $value;
    }
}
