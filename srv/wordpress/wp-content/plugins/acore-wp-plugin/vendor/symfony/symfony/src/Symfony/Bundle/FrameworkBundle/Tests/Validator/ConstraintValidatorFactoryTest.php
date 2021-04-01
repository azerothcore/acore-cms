<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Blank as BlankConstraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @group legacy
 */
class ConstraintValidatorFactoryTest extends TestCase
{
    public function testGetInstanceCreatesValidator()
    {
        $factory = new ConstraintValidatorFactory(new Container());
        $this->assertInstanceOf(DummyConstraintValidator::class, $factory->getInstance(new DummyConstraint()));
    }

    public function testGetInstanceReturnsExistingValidator()
    {
        $factory = new ConstraintValidatorFactory(new Container());
        $v1 = $factory->getInstance(new BlankConstraint());
        $v2 = $factory->getInstance(new BlankConstraint());
        $this->assertSame($v1, $v2);
    }

    public function testGetInstanceReturnsService()
    {
        $validator = new DummyConstraintValidator();
        $container = new Container();
        $container->set(DummyConstraintValidator::class, $validator);

        $factory = new ConstraintValidatorFactory($container);

        $this->assertSame($validator, $factory->getInstance(new DummyConstraint()));
    }

    public function testGetInstanceReturnsServiceWithAlias()
    {
        $validator = new DummyConstraintValidator();

        $container = new Container();
        $container->set('validator_constraint_service', $validator);

        $factory = new ConstraintValidatorFactory($container, ['validator_constraint_alias' => 'validator_constraint_service']);
        $this->assertSame($validator, $factory->getInstance(new ConstraintAliasStub()));
    }

    public function testGetInstanceInvalidValidatorClass()
    {
        $this->expectException('Symfony\Component\Validator\Exception\ValidatorException');
        $constraint = $this->getMockBuilder('Symfony\\Component\\Validator\\Constraint')->getMock();
        $constraint
            ->expects($this->exactly(2))
            ->method('validatedBy')
            ->willReturn('Fully\\Qualified\\ConstraintValidator\\Class\\Name');

        $factory = new ConstraintValidatorFactory(new Container());
        $factory->getInstance($constraint);
    }
}

class ConstraintAliasStub extends Constraint
{
    public function validatedBy()
    {
        return 'validator_constraint_alias';
    }
}

class DummyConstraint extends Constraint
{
    public function validatedBy()
    {
        return DummyConstraintValidator::class;
    }
}

class DummyConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
    }
}
