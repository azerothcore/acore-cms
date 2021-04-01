<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\FormPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @group legacy
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormPassTest extends TestCase
{
    public function testDoNothingIfFormExtensionNotLoaded()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new FormPass());

        $container->compile();

        $this->assertFalse($container->hasDefinition('form.extension'));
    }

    public function testAddTaggedTypes()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new FormPass());

        $extDefinition = new Definition('Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension');
        $extDefinition->setPublic(true);
        $extDefinition->setArguments([
            new Reference('service_container'),
            [],
            [],
            [],
        ]);

        $container->setDefinition('form.extension', $extDefinition);
        $container->register('my.type1', __CLASS__.'_Type1')->addTag('form.type')->setPublic(true);
        $container->register('my.type2', __CLASS__.'_Type2')->addTag('form.type')->setPublic(true);

        $container->compile();

        $extDefinition = $container->getDefinition('form.extension');

        $this->assertEquals([
            __CLASS__.'_Type1' => 'my.type1',
            __CLASS__.'_Type2' => 'my.type2',
        ], $extDefinition->getArgument(1));
    }

    /**
     * @dataProvider addTaggedTypeExtensionsDataProvider
     */
    public function testAddTaggedTypeExtensions(array $extensions, array $expectedRegisteredExtensions)
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new FormPass());

        $extDefinition = new Definition('Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension', [
            new Reference('service_container'),
            [],
            [],
            [],
        ]);
        $extDefinition->setPublic(true);

        $container->setDefinition('form.extension', $extDefinition);

        foreach ($extensions as $serviceId => $tag) {
            $container->register($serviceId, 'stdClass')->addTag('form.type_extension', $tag);
        }

        $container->compile();

        $extDefinition = $container->getDefinition('form.extension');
        $this->assertSame($expectedRegisteredExtensions, $extDefinition->getArgument(2));
    }

    /**
     * @return array
     */
    public function addTaggedTypeExtensionsDataProvider()
    {
        return [
            [
                [
                    'my.type_extension1' => ['extended_type' => 'type1'],
                    'my.type_extension2' => ['extended_type' => 'type1'],
                    'my.type_extension3' => ['extended_type' => 'type2'],
                ],
                [
                    'type1' => ['my.type_extension1', 'my.type_extension2'],
                    'type2' => ['my.type_extension3'],
                ],
            ],
            [
                [
                    'my.type_extension1' => ['extended_type' => 'type1', 'priority' => 1],
                    'my.type_extension2' => ['extended_type' => 'type1', 'priority' => 2],
                    'my.type_extension3' => ['extended_type' => 'type1', 'priority' => -1],
                    'my.type_extension4' => ['extended_type' => 'type2', 'priority' => 2],
                    'my.type_extension5' => ['extended_type' => 'type2', 'priority' => 1],
                    'my.type_extension6' => ['extended_type' => 'type2', 'priority' => 1],
                ],
                [
                    'type1' => ['my.type_extension2', 'my.type_extension1', 'my.type_extension3'],
                    'type2' => ['my.type_extension4', 'my.type_extension5', 'my.type_extension6'],
                ],
            ],
        ];
    }

    public function testAddTaggedFormTypeExtensionWithoutExtendedTypeAttribute()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('extended-type attribute, none was configured for the "my.type_extension" service');
        $container = new ContainerBuilder();
        $container->addCompilerPass(new FormPass());

        $extDefinition = new Definition('Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension', [
            new Reference('service_container'),
            [],
            [],
            [],
        ]);
        $extDefinition->setPublic(true);

        $container->setDefinition('form.extension', $extDefinition);
        $container->register('my.type_extension', 'stdClass')
            ->addTag('form.type_extension');

        $container->compile();
    }

    public function testAddTaggedGuessers()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new FormPass());

        $extDefinition = new Definition('Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension');
        $extDefinition->setPublic(true);
        $extDefinition->setArguments([
            new Reference('service_container'),
            [],
            [],
            [],
        ]);

        $definition1 = new Definition('stdClass');
        $definition1->addTag('form.type_guesser');
        $definition2 = new Definition('stdClass');
        $definition2->addTag('form.type_guesser');

        $container->setDefinition('form.extension', $extDefinition);
        $container->setDefinition('my.guesser1', $definition1);
        $container->setDefinition('my.guesser2', $definition2);

        $container->compile();

        $extDefinition = $container->getDefinition('form.extension');

        $this->assertSame([
            'my.guesser1',
            'my.guesser2',
        ], $extDefinition->getArgument(3));
    }

    /**
     * @dataProvider privateTaggedServicesProvider
     */
    public function testPrivateTaggedServices($id, $tagName)
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new FormPass());

        $extDefinition = new Definition('Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension');
        $extDefinition->setArguments([
            new Reference('service_container'),
            [],
            [],
            [],
        ]);

        $container->setDefinition('form.extension', $extDefinition);
        $container->register($id, 'stdClass')->setPublic(false)->addTag($tagName, ['extended_type' => 'Foo']);

        $container->compile();
        $this->assertTrue($container->getDefinition($id)->isPublic());
    }

    public function privateTaggedServicesProvider()
    {
        return [
            ['my.type', 'form.type'],
            ['my.type_extension', 'form.type_extension'],
            ['my.guesser', 'form.type_guesser'],
        ];
    }
}
