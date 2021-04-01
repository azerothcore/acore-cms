<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Extension;

use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * WorkflowExtension.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class WorkflowExtension extends AbstractExtension
{
    private $workflowRegistry;

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('workflow_can', [$this, 'canTransition']),
            new TwigFunction('workflow_transitions', [$this, 'getEnabledTransitions']),
            new TwigFunction('workflow_has_marked_place', [$this, 'hasMarkedPlace']),
            new TwigFunction('workflow_marked_places', [$this, 'getMarkedPlaces']),
        ];
    }

    /**
     * Returns true if the transition is enabled.
     *
     * @param object $subject        A subject
     * @param string $transitionName A transition
     * @param string $name           A workflow name
     *
     * @return bool true if the transition is enabled
     */
    public function canTransition($subject, $transitionName, $name = null)
    {
        return $this->workflowRegistry->get($subject, $name)->can($subject, $transitionName);
    }

    /**
     * Returns all enabled transitions.
     *
     * @param object $subject A subject
     * @param string $name    A workflow name
     *
     * @return Transition[] All enabled transitions
     */
    public function getEnabledTransitions($subject, $name = null)
    {
        return $this->workflowRegistry->get($subject, $name)->getEnabledTransitions($subject);
    }

    /**
     * Returns true if the place is marked.
     *
     * @param object $subject   A subject
     * @param string $placeName A place name
     * @param string $name      A workflow name
     *
     * @return bool true if the transition is enabled
     */
    public function hasMarkedPlace($subject, $placeName, $name = null)
    {
        return $this->workflowRegistry->get($subject, $name)->getMarking($subject)->has($placeName);
    }

    /**
     * Returns marked places.
     *
     * @param object $subject        A subject
     * @param bool   $placesNameOnly If true, returns only places name. If false returns the raw representation
     * @param string $name           A workflow name
     *
     * @return string[]|int[]
     */
    public function getMarkedPlaces($subject, $placesNameOnly = true, $name = null)
    {
        $places = $this->workflowRegistry->get($subject, $name)->getMarking($subject)->getPlaces();

        if ($placesNameOnly) {
            return array_keys($places);
        }

        return $places;
    }

    public function getName()
    {
        return 'workflow';
    }
}
