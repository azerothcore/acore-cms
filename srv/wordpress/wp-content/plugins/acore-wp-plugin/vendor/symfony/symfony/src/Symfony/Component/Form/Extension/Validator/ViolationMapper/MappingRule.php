<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\Validator\ViolationMapper;

use Symfony\Component\Form\Exception\ErrorMappingException;
use Symfony\Component\Form\FormInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MappingRule
{
    private $origin;
    private $propertyPath;
    private $targetPath;

    /**
     * @param string $propertyPath
     * @param string $targetPath
     */
    public function __construct(FormInterface $origin, $propertyPath, $targetPath)
    {
        $this->origin = $origin;
        $this->propertyPath = $propertyPath;
        $this->targetPath = $targetPath;
    }

    /**
     * @return FormInterface
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Matches a property path against the rule path.
     *
     * If the rule matches, the form mapped by the rule is returned.
     * Otherwise this method returns false.
     *
     * @param string $propertyPath The property path to match against the rule
     *
     * @return FormInterface|null The mapped form or null
     */
    public function match($propertyPath)
    {
        return $propertyPath === $this->propertyPath ? $this->getTarget() : null;
    }

    /**
     * Matches a property path against a prefix of the rule path.
     *
     * @param string $propertyPath The property path to match against the rule
     *
     * @return bool Whether the property path is a prefix of the rule or not
     */
    public function isPrefix($propertyPath)
    {
        $length = \strlen($propertyPath);
        $prefix = substr($this->propertyPath, 0, $length);
        $next = isset($this->propertyPath[$length]) ? $this->propertyPath[$length] : null;

        return $prefix === $propertyPath && ('[' === $next || '.' === $next);
    }

    /**
     * @return FormInterface
     *
     * @throws ErrorMappingException
     */
    public function getTarget()
    {
        $childNames = explode('.', $this->targetPath);
        $target = $this->origin;

        foreach ($childNames as $childName) {
            if (!$target->has($childName)) {
                throw new ErrorMappingException(sprintf('The child "%s" of "%s" mapped by the rule "%s" in "%s" does not exist.', $childName, $target->getName(), $this->targetPath, $this->origin->getName()));
            }
            $target = $target->get($childName);
        }

        return $target;
    }
}
