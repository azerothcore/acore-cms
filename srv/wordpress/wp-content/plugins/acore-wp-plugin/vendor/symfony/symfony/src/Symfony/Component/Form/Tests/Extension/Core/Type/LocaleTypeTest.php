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

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Intl\Util\IntlTestHelper;

class LocaleTypeTest extends BaseTypeTest
{
    const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\LocaleType';

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this, false);

        parent::setUp();
    }

    public function testLocalesAreSelectable()
    {
        $choices = $this->factory->create(static::TESTED_TYPE)
            ->createView()->vars['choices'];

        $this->assertContainsEquals(new ChoiceView('en', 'en', 'English'), $choices);
        $this->assertContainsEquals(new ChoiceView('en_GB', 'en_GB', 'English (United Kingdom)'), $choices);
        $this->assertContainsEquals(new ChoiceView('zh_Hant_HK', 'zh_Hant_HK', 'Chinese (Traditional, Hong Kong SAR China)'), $choices);
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null)
    {
        parent::testSubmitNull($expected, $norm, '');
    }

    public function testSubmitNullUsesDefaultEmptyData($emptyData = 'en', $expectedData = 'en')
    {
        parent::testSubmitNullUsesDefaultEmptyData($emptyData, $expectedData);
    }

    public function testInvalidChoiceValuesAreDropped()
    {
        $type = new LocaleType();

        $this->assertSame([], $type->loadChoicesForValues(['foo']));
    }
}
