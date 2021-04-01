<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\Type;

use Symfony\Component\Intl\Util\IntlTestHelper;

class NumberTypeTest extends BaseTypeTest
{
    const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\NumberType';

    private $defaultLocale;

    protected function setUp()
    {
        parent::setUp();

        // we test against "de_DE", so we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        $this->defaultLocale = \Locale::getDefault();
        \Locale::setDefault('de_DE');
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Locale::setDefault($this->defaultLocale);
    }

    public function testDefaultFormatting()
    {
        $form = $this->factory->create(static::TESTED_TYPE);
        $form->setData('12345.67890');

        $this->assertSame('12345,679', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithGrouping()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['grouping' => true]);
        $form->setData('12345.67890');

        $this->assertSame('12.345,679', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithScale()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['scale' => 2]);
        $form->setData('12345.67890');

        $this->assertSame('12345,68', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithRounding()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['scale' => 0, 'rounding_mode' => \NumberFormatter::ROUND_UP]);
        $form->setData('12345.54321');

        $this->assertSame('12346', $form->createView()->vars['value']);
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null)
    {
        parent::testSubmitNull($expected, $norm, '');
    }

    public function testSubmitNullUsesDefaultEmptyData($emptyData = '10', $expectedData = 10.0)
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, [
            'empty_data' => $emptyData,
        ]);
        $form->submit(null);

        $this->assertSame($emptyData, $form->getViewData());
        $this->assertSame($expectedData, $form->getNormData());
        $this->assertSame($expectedData, $form->getData());
    }

    public function testSubmitNullWithEmptyDataSetToNull()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, [
            'empty_data' => null,
        ]);
        $form->submit(null);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('', $form->getViewData());
        $this->assertNull($form->getNormData());
        $this->assertNull($form->getData());
    }
}
