<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Validator\Constraints;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormPerformanceTestCase;
use Symfony\Component\Validator\Validation;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormValidatorPerformanceTest extends FormPerformanceTestCase
{
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * findClickedButton() used to have an exponential number of calls.
     *
     * @group benchmark
     */
    public function testValidationPerformance()
    {
        $this->setMaxRunningTime(1);

        $builder = $this->factory->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType');

        for ($i = 0; $i < 40; ++$i) {
            $builder->add($i, 'Symfony\Component\Form\Extension\Core\Type\FormType');

            $builder->get($i)
                ->add('a')
                ->add('b')
                ->add('c');
        }

        $form = $builder->getForm();

        $form->submit(null);
    }
}
