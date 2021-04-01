<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Tests\ExpressionLanguage;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ExpressionLanguage\DoctrineParserCache;

/**
 * @group legacy
 */
class DoctrineParserCacheTest extends TestCase
{
    public function testFetch()
    {
        $doctrineCacheMock = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $parserCache = new DoctrineParserCache($doctrineCacheMock);

        $doctrineCacheMock->expects($this->once())
            ->method('fetch')
            ->willReturn('bar');

        $result = $parserCache->fetch('foo');

        $this->assertEquals('bar', $result);
    }

    public function testFetchUnexisting()
    {
        $doctrineCacheMock = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $parserCache = new DoctrineParserCache($doctrineCacheMock);

        $doctrineCacheMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->assertNull($parserCache->fetch(''));
    }

    public function testSave()
    {
        $doctrineCacheMock = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $parserCache = new DoctrineParserCache($doctrineCacheMock);

        $expression = $this->getMockBuilder('Symfony\Component\ExpressionLanguage\ParsedExpression')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineCacheMock->expects($this->once())
            ->method('save')
            ->with('foo', $expression);

        $parserCache->save('foo', $expression);
    }
}
