<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Workflow\EventListener;

use Symfony\Component\Workflow\Transition;

class GuardExpression
{
    private $transition;

    private $expression;

    /**
     * @param string $expression
     */
    public function __construct(Transition $transition, $expression)
    {
        $this->transition = $transition;
        $this->expression = $expression;
    }

    public function getTransition()
    {
        return $this->transition;
    }

    public function getExpression()
    {
        return $this->expression;
    }
}
