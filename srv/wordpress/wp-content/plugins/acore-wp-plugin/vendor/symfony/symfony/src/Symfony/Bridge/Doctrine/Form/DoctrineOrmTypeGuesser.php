<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Form;

use Doctrine\Common\Persistence\ManagerRegistry as LegacyManagerRegistry;
use Doctrine\Common\Persistence\Mapping\MappingException as LegacyCommonMappingException;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException as LegacyMappingException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

class DoctrineOrmTypeGuesser implements FormTypeGuesserInterface
{
    protected $registry;

    private $cache = [];

    private static $useDeprecatedConstants;

    /**
     * @param ManagerRegistry|LegacyManagerRegistry $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;

        if (null === self::$useDeprecatedConstants) {
            self::$useDeprecatedConstants = !class_exists(Types::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        if (!$ret = $this->getMetadata($class)) {
            return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TextType', [], Guess::LOW_CONFIDENCE);
        }

        list($metadata, $name) = $ret;

        if ($metadata->hasAssociation($property)) {
            $multiple = $metadata->isCollectionValuedAssociation($property);
            $mapping = $metadata->getAssociationMapping($property);

            return new TypeGuess('Symfony\Bridge\Doctrine\Form\Type\EntityType', ['em' => $name, 'class' => $mapping['targetEntity'], 'multiple' => $multiple], Guess::HIGH_CONFIDENCE);
        }

        switch ($metadata->getTypeOfField($property)) {
            case self::$useDeprecatedConstants ? Type::TARRAY : 'array':
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\CollectionType', [], Guess::MEDIUM_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::BOOLEAN : Types::BOOLEAN:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\CheckboxType', [], Guess::HIGH_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::DATETIME : Types::DATETIME_MUTABLE:
            // no break
            case self::$useDeprecatedConstants ? Type::DATETIMETZ : Types::DATETIMETZ_MUTABLE:
            // no break
            case 'vardatetime':
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\DateTimeType', [], Guess::HIGH_CONFIDENCE);
            case 'dateinterval':
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\DateIntervalType', [], Guess::HIGH_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::DATE : Types::DATE_MUTABLE:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\DateType', [], Guess::HIGH_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::TIME : Types::TIME_MUTABLE:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TimeType', [], Guess::HIGH_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::DECIMAL : Types::DECIMAL:
            // no break
            case self::$useDeprecatedConstants ? Type::FLOAT : Types::FLOAT:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\NumberType', [], Guess::MEDIUM_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::INTEGER : Types::INTEGER:
            // no break
            case self::$useDeprecatedConstants ? Type::BIGINT : Types::BIGINT:
            // no break
            case self::$useDeprecatedConstants ? Type::SMALLINT : Types::SMALLINT:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\IntegerType', [], Guess::MEDIUM_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::STRING : Types::STRING:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TextType', [], Guess::MEDIUM_CONFIDENCE);
            case self::$useDeprecatedConstants ? Type::TEXT : Types::TEXT:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TextareaType', [], Guess::MEDIUM_CONFIDENCE);
            default:
                return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TextType', [], Guess::LOW_CONFIDENCE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
        $classMetadatas = $this->getMetadata($class);

        if (!$classMetadatas) {
            return null;
        }

        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $classMetadatas[0];

        // Check whether the field exists and is nullable or not
        if (isset($classMetadata->fieldMappings[$property])) {
            if (!$classMetadata->isNullable($property) && (self::$useDeprecatedConstants ? Type::BOOLEAN : Types::BOOLEAN) !== $classMetadata->getTypeOfField($property)) {
                return new ValueGuess(true, Guess::HIGH_CONFIDENCE);
            }

            return new ValueGuess(false, Guess::MEDIUM_CONFIDENCE);
        }

        // Check whether the association exists, is a to-one association and its
        // join column is nullable or not
        if ($classMetadata->isAssociationWithSingleJoinColumn($property)) {
            $mapping = $classMetadata->getAssociationMapping($property);

            if (!isset($mapping['joinColumns'][0]['nullable'])) {
                // The "nullable" option defaults to true, in that case the
                // field should not be required.
                return new ValueGuess(false, Guess::HIGH_CONFIDENCE);
            }

            return new ValueGuess(!$mapping['joinColumns'][0]['nullable'], Guess::HIGH_CONFIDENCE);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && isset($ret[0]->fieldMappings[$property]) && !$ret[0]->hasAssociation($property)) {
            $mapping = $ret[0]->getFieldMapping($property);

            if (isset($mapping['length'])) {
                return new ValueGuess($mapping['length'], Guess::HIGH_CONFIDENCE);
            }

            if (\in_array($ret[0]->getTypeOfField($property), self::$useDeprecatedConstants ? [Type::DECIMAL, Type::FLOAT] : [Types::DECIMAL, Types::FLOAT])) {
                return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && isset($ret[0]->fieldMappings[$property]) && !$ret[0]->hasAssociation($property)) {
            if (\in_array($ret[0]->getTypeOfField($property), self::$useDeprecatedConstants ? [Type::DECIMAL, Type::FLOAT] : [Types::DECIMAL, Types::FLOAT])) {
                return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }

        return null;
    }

    protected function getMetadata($class)
    {
        // normalize class name
        $class = ClassUtils::getRealClass(ltrim($class, '\\'));

        if (\array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        $this->cache[$class] = null;
        foreach ($this->registry->getManagers() as $name => $em) {
            try {
                return $this->cache[$class] = [$em->getClassMetadata($class), $name];
            } catch (MappingException $e) {
                // not an entity or mapped super class
            } catch (LegacyCommonMappingException $e) {
                // not an entity or mapped super class
            } catch (LegacyMappingException $e) {
                // not an entity or mapped super class, using Doctrine ORM 2.2
            }
        }

        return null;
    }
}
