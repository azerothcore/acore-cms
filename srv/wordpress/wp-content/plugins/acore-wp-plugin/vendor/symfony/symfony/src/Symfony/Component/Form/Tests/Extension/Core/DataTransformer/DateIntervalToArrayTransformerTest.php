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

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateIntervalToArrayTransformer;

/**
 * @author Steffen Roßkamp <steffen.rosskamp@gimmickmedia.de>
 */
class DateIntervalToArrayTransformerTest extends DateIntervalTestCase
{
    public function testTransform()
    {
        $transformer = new DateIntervalToArrayTransformer();
        $input = new \DateInterval('P1Y2M3DT4H5M6S');
        $output = [
            'years' => '1',
            'months' => '2',
            'days' => '3',
            'hours' => '4',
            'minutes' => '5',
            'seconds' => '6',
            'invert' => false,
        ];
        $this->assertSame($output, $transformer->transform($input));
    }

    public function testTransformEmpty()
    {
        $transformer = new DateIntervalToArrayTransformer();
        $output = [
            'years' => '',
            'months' => '',
            'days' => '',
            'hours' => '',
            'minutes' => '',
            'seconds' => '',
            'invert' => false,
        ];
        $this->assertSame($output, $transformer->transform(null));
    }

    public function testTransformEmptyWithFields()
    {
        $transformer = new DateIntervalToArrayTransformer(['years', 'weeks', 'minutes', 'seconds']);
        $output = [
            'years' => '',
            'weeks' => '',
            'minutes' => '',
            'seconds' => '',
        ];
        $this->assertSame($output, $transformer->transform(null));
    }

    public function testTransformWithFields()
    {
        $transformer = new DateIntervalToArrayTransformer(['years', 'minutes', 'seconds']);
        $input = new \DateInterval('P1Y2M3DT4H5M6S');
        $output = [
            'years' => '1',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $this->assertSame($output, $transformer->transform($input));
    }

    public function testTransformWithWeek()
    {
        $transformer = new DateIntervalToArrayTransformer(['weeks', 'minutes', 'seconds']);
        $input = new \DateInterval('P1Y2M3WT4H5M6S');
        $output = [
            'weeks' => '3',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $input = $transformer->transform($input);
        ksort($input);
        ksort($output);
        $this->assertSame($output, $input);
    }

    public function testTransformWithZeroWeek()
    {
        $transformer = new DateIntervalToArrayTransformer(['weeks', 'minutes', 'seconds']);
        $input = new \DateInterval('P1Y2M0WT4H5M6S');
        $output = [
            'weeks' => '0',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $input = $transformer->transform($input);
        ksort($input);
        ksort($output);
        $this->assertSame($output, $input);
    }

    public function testTransformDaysToWeeks()
    {
        $transformer = new DateIntervalToArrayTransformer(['weeks', 'minutes', 'seconds']);
        $input = new \DateInterval('P1Y2M23DT4H5M6S');
        $output = [
            'weeks' => '3',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $input = $transformer->transform($input);
        ksort($input);
        ksort($output);
        $this->assertSame($output, $input);
    }

    public function testTransformDaysNotOverflowingToWeeks()
    {
        $transformer = new DateIntervalToArrayTransformer(['days', 'minutes', 'seconds']);
        $input = new \DateInterval('P1Y2M23DT4H5M6S');
        $output = [
            'days' => '23',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $this->assertSame($output, $transformer->transform($input));
    }

    public function testTransformWithInvert()
    {
        $transformer = new DateIntervalToArrayTransformer(['years', 'invert']);
        $input = new \DateInterval('P1Y');
        $input->invert = 1;
        $output = [
            'years' => '1',
            'invert' => true,
        ];
        $this->assertSame($output, $transformer->transform($input));
    }

    public function testTransformWithPadding()
    {
        $transformer = new DateIntervalToArrayTransformer(null, true);
        $input = new \DateInterval('P1Y2M3DT4H5M6S');
        $output = [
            'years' => '01',
            'months' => '02',
            'days' => '03',
            'hours' => '04',
            'minutes' => '05',
            'seconds' => '06',
            'invert' => false,
        ];
        $this->assertSame($output, $transformer->transform($input));
    }

    public function testTransformWithFieldsAndPadding()
    {
        $transformer = new DateIntervalToArrayTransformer(['years', 'minutes', 'seconds'], true);
        $input = new \DateInterval('P1Y2M3DT4H5M6S');
        $output = [
            'years' => '01',
            'minutes' => '05',
            'seconds' => '06',
        ];
        $this->assertSame($output, $transformer->transform($input));
    }

    public function testReverseTransformRequiresDateTime()
    {
        $transformer = new DateIntervalToArrayTransformer();
        $this->assertNull($transformer->reverseTransform(null));
        $this->expectException(UnexpectedTypeException::class);
        $transformer->reverseTransform('12345');
    }

    public function testReverseTransformWithUnsetFields()
    {
        $transformer = new DateIntervalToArrayTransformer();
        $input = ['years' => '1'];
        $this->expectException(TransformationFailedException::class);
        $transformer->reverseTransform($input);
    }

    public function testReverseTransformWithEmptyFields()
    {
        $transformer = new DateIntervalToArrayTransformer(['years', 'minutes', 'seconds']);
        $input = [
            'years' => '1',
            'minutes' => '',
            'seconds' => '6',
        ];
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('This amount of "minutes" is invalid');
        $transformer->reverseTransform($input);
    }

    public function testReverseTransformWithWrongInvertType()
    {
        $transformer = new DateIntervalToArrayTransformer(['invert']);
        $input = [
            'invert' => '1',
        ];
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The value of "invert" must be boolean');
        $transformer->reverseTransform($input);
    }

    public function testReverseTransform()
    {
        $transformer = new DateIntervalToArrayTransformer();
        $input = [
            'years' => '1',
            'months' => '2',
            'days' => '3',
            'hours' => '4',
            'minutes' => '5',
            'seconds' => '6',
            'invert' => false,
        ];
        $output = new \DateInterval('P01Y02M03DT04H05M06S');
        $this->assertDateIntervalEquals($output, $transformer->reverseTransform($input));
    }

    public function testReverseTransformWithWeek()
    {
        $transformer = new DateIntervalToArrayTransformer(
            ['years', 'months', 'weeks', 'hours', 'minutes', 'seconds']
        );
        $input = [
            'years' => '1',
            'months' => '2',
            'weeks' => '3',
            'hours' => '4',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $output = new \DateInterval('P1Y2M21DT4H5M6S');
        $this->assertDateIntervalEquals($output, $transformer->reverseTransform($input));
    }

    public function testReverseTransformWithFields()
    {
        $transformer = new DateIntervalToArrayTransformer(['years', 'minutes', 'seconds']);
        $input = [
            'years' => '1',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $output = new \DateInterval('P1Y0M0DT0H5M6S');
        $this->assertDateIntervalEquals($output, $transformer->reverseTransform($input));
    }

    public function testBothTransformsWithWeek()
    {
        $transformer = new DateIntervalToArrayTransformer(
            ['years', 'months', 'weeks', 'hours', 'minutes', 'seconds']
        );
        $interval = new \DateInterval('P1Y2M21DT4H5M6S');
        $array = [
            'years' => '1',
            'months' => '2',
            'weeks' => '3',
            'hours' => '4',
            'minutes' => '5',
            'seconds' => '6',
        ];
        $input = $transformer->transform($interval);
        ksort($input);
        ksort($array);
        $this->assertSame($array, $input);
        $interval = new \DateInterval('P1Y2M0DT4H5M6S');
        $input['weeks'] = '0';
        $this->assertDateIntervalEquals($interval, $transformer->reverseTransform($input));
    }
}
