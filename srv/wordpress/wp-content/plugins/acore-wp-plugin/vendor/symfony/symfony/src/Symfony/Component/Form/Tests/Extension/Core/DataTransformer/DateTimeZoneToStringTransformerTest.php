<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\DataTransformer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeZoneToStringTransformer;

class DateTimeZoneToStringTransformerTest extends TestCase
{
    public function testSingle()
    {
        $transformer = new DateTimeZoneToStringTransformer();

        $this->assertNull($transformer->transform(null));
        $this->assertNull($transformer->reverseTransform(null));

        $this->assertSame('Europe/Amsterdam', $transformer->transform(new \DateTimeZone('Europe/Amsterdam')));
        $this->assertEquals(new \DateTimeZone('Europe/Amsterdam'), $transformer->reverseTransform('Europe/Amsterdam'));
    }

    public function testMultiple()
    {
        $transformer = new DateTimeZoneToStringTransformer(true);

        $this->assertNull($transformer->transform(null));
        $this->assertNull($transformer->reverseTransform(null));

        $this->assertSame(['Europe/Amsterdam'], $transformer->transform([new \DateTimeZone('Europe/Amsterdam')]));
        $this->assertEquals([new \DateTimeZone('Europe/Amsterdam')], $transformer->reverseTransform(['Europe/Amsterdam']));
    }

    public function testInvalidTimezone()
    {
        $this->expectException('Symfony\Component\Form\Exception\TransformationFailedException');
        (new DateTimeZoneToStringTransformer())->transform(1);
    }

    public function testUnknownTimezone()
    {
        $this->expectException('Symfony\Component\Form\Exception\TransformationFailedException');
        (new DateTimeZoneToStringTransformer(true))->reverseTransform(['Foo/Bar']);
    }
}
