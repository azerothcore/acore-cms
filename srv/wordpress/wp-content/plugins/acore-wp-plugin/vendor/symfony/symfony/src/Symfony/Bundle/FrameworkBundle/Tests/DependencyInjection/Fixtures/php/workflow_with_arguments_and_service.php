<?php

use Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\FrameworkExtensionTest;

$container->loadFromExtension('framework', [
    'workflows' => [
        'my_workflow' => [
            'marking_store' => [
                'arguments' => ['a', 'b'],
                'service' => 'workflow_service',
            ],
            'supports' => [
                FrameworkExtensionTest::class,
            ],
            'places' => [
                'first',
                'last',
            ],
            'transitions' => [
                'go' => [
                    'from' => [
                        'first',
                    ],
                    'to' => [
                        'last',
                    ],
                ],
            ],
        ],
    ],
]);
