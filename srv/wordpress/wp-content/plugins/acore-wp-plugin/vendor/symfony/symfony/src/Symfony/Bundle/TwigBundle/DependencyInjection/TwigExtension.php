<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Loader\LoaderInterface;

/**
 * TwigExtension.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jeremy Mikola <jmikola@gmail.com>
 */
class TwigExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('twig.xml');

        $container->getDefinition('twig.profile')->setPrivate(true);
        $container->getDefinition('twig.translation.extractor')->setPrivate(true);
        $container->getDefinition('workflow.twig_extension')->setPrivate(true);
        $container->getDefinition('twig.exception_listener')->setPrivate(true);

        if ($container->has('fragment.handler')) {
            $container->getDefinition('twig.runtime.httpkernel')->setPrivate(true);
        }

        if (class_exists('Symfony\Component\Form\Form')) {
            $loader->load('form.xml');
            $container->getDefinition('twig.form.renderer')->setPrivate(true);
        }

        if (interface_exists('Symfony\Component\Templating\EngineInterface')) {
            $loader->load('templating.xml');
        }

        if (class_exists(Application::class)) {
            $loader->load('console.xml');
        }

        if (!interface_exists('Symfony\Component\Translation\TranslatorInterface')) {
            $container->removeDefinition('twig.translation.extractor');
        }

        foreach ($configs as $key => $config) {
            if (isset($config['globals'])) {
                foreach ($config['globals'] as $name => $value) {
                    if (\is_array($value) && isset($value['key'])) {
                        $configs[$key]['globals'][$name] = [
                            'key' => $name,
                            'value' => $value,
                        ];
                    }
                }
            }
        }

        $configuration = $this->getConfiguration($configs, $container);

        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('twig.exception_listener.controller', $config['exception_controller']);

        $container->setParameter('twig.form.resources', $config['form_themes']);
        $container->setParameter('twig.default_path', $config['default_path']);

        $envConfiguratorDefinition = $container->getDefinition('twig.configurator.environment');
        $envConfiguratorDefinition->replaceArgument(0, $config['date']['format']);
        $envConfiguratorDefinition->replaceArgument(1, $config['date']['interval_format']);
        $envConfiguratorDefinition->replaceArgument(2, $config['date']['timezone']);
        $envConfiguratorDefinition->replaceArgument(3, $config['number_format']['decimals']);
        $envConfiguratorDefinition->replaceArgument(4, $config['number_format']['decimal_point']);
        $envConfiguratorDefinition->replaceArgument(5, $config['number_format']['thousands_separator']);

        $twigFilesystemLoaderDefinition = $container->getDefinition('twig.loader.native_filesystem');

        // register user-configured paths
        foreach ($config['paths'] as $path => $namespace) {
            if (!$namespace) {
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path]);
            } else {
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $namespace]);
            }
        }

        // paths are modified in ExtensionPass if forms are enabled
        $container->getDefinition('twig.cache_warmer')->replaceArgument(2, $config['paths']);
        $container->getDefinition('twig.template_iterator')->replaceArgument(2, $config['paths']);

        $bundleHierarchy = $this->getBundleHierarchy($container, $config);

        foreach ($bundleHierarchy as $name => $bundle) {
            $namespace = $this->normalizeBundleName($name);

            foreach ($bundle['children'] as $child) {
                foreach ($bundleHierarchy[$child]['paths'] as $path) {
                    $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $namespace]);
                }
            }

            foreach ($bundle['paths'] as $path) {
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $namespace]);
            }

            // add exclusive namespace for root bundles only
            // to override a bundle template that also extends itself
            if (\count($bundle['paths']) > 0 && 0 === \count($bundle['parents'])) {
                // the last path must be the bundle views directory
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [end($bundle['paths']), '!'.$namespace]);
            }
        }

        if (file_exists($dir = $container->getParameter('kernel.root_dir').'/Resources/views')) {
            $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$dir]);
        }
        $container->addResource(new FileExistenceResource($dir));

        if (file_exists($dir = $container->getParameterBag()->resolveValue($config['default_path']))) {
            $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$dir]);
        }
        $container->addResource(new FileExistenceResource($dir));

        if (!empty($config['globals'])) {
            $def = $container->getDefinition('twig');
            foreach ($config['globals'] as $key => $global) {
                if (isset($global['type']) && 'service' === $global['type']) {
                    $def->addMethodCall('addGlobal', [$key, new Reference($global['id'])]);
                } else {
                    $def->addMethodCall('addGlobal', [$key, $global['value']]);
                }
            }
        }

        if (isset($config['autoescape_service']) && isset($config['autoescape_service_method'])) {
            $config['autoescape'] = [new Reference($config['autoescape_service']), $config['autoescape_service_method']];
        }

        $container->getDefinition('twig')->replaceArgument(1, array_intersect_key($config, [
            'debug' => true,
            'charset' => true,
            'base_template_class' => true,
            'strict_variables' => true,
            'autoescape' => true,
            'cache' => true,
            'auto_reload' => true,
            'optimizations' => true,
        ]));

        $container->registerForAutoconfiguration(\Twig_ExtensionInterface::class)->addTag('twig.extension');
        $container->registerForAutoconfiguration(\Twig_LoaderInterface::class)->addTag('twig.loader');
        $container->registerForAutoconfiguration(ExtensionInterface::class)->addTag('twig.extension');
        $container->registerForAutoconfiguration(LoaderInterface::class)->addTag('twig.loader');
        $container->registerForAutoconfiguration(RuntimeExtensionInterface::class)->addTag('twig.runtime');

        if (false === $config['cache']) {
            $container->removeDefinition('twig.cache_warmer');
            $container->removeDefinition('twig.template_cache_warmer');
        }

        if (\PHP_VERSION_ID < 70000) {
            $this->addClassesToCompile([
                'Twig_Environment',
                'Twig_Extension',
                'Twig_Extension_Core',
                'Twig_Extension_Escaper',
                'Twig_Extension_Optimizer',
                'Twig_LoaderInterface',
                'Twig_Markup',
                'Twig_Template',
            ]);
        }
    }

    private function getBundleHierarchy(ContainerBuilder $container, array $config)
    {
        $bundleHierarchy = [];

        foreach ($container->getParameter('kernel.bundles_metadata') as $name => $bundle) {
            if (!\array_key_exists($name, $bundleHierarchy)) {
                $bundleHierarchy[$name] = [
                    'paths' => [],
                    'parents' => [],
                    'children' => [],
                ];
            }

            if (file_exists($dir = $container->getParameter('kernel.root_dir').'/Resources/'.$name.'/views')) {
                $bundleHierarchy[$name]['paths'][] = $dir;
            }
            $container->addResource(new FileExistenceResource($dir));

            if (file_exists($dir = $container->getParameterBag()->resolveValue($config['default_path']).'/bundles/'.$name)) {
                $bundleHierarchy[$name]['paths'][] = $dir;
            }
            $container->addResource(new FileExistenceResource($dir));

            if (file_exists($dir = $bundle['path'].'/Resources/views')) {
                $bundleHierarchy[$name]['paths'][] = $dir;
            }
            $container->addResource(new FileExistenceResource($dir));

            if (!isset($bundle['parent']) || null === $bundle['parent']) {
                continue;
            }

            $bundleHierarchy[$name]['parents'][] = $bundle['parent'];

            if (!\array_key_exists($bundle['parent'], $bundleHierarchy)) {
                $bundleHierarchy[$bundle['parent']] = [
                    'paths' => [],
                    'parents' => [],
                    'children' => [],
                ];
            }

            $bundleHierarchy[$bundle['parent']]['children'] = array_merge($bundleHierarchy[$name]['children'], [$name], $bundleHierarchy[$bundle['parent']]['children']);

            foreach ($bundleHierarchy[$bundle['parent']]['parents'] as $parent) {
                $bundleHierarchy[$name]['parents'][] = $parent;
                $bundleHierarchy[$parent]['children'] = array_merge($bundleHierarchy[$name]['children'], [$name], $bundleHierarchy[$parent]['children']);
            }

            foreach ($bundleHierarchy[$name]['children'] as $child) {
                $bundleHierarchy[$child]['parents'] = array_merge($bundleHierarchy[$child]['parents'], $bundleHierarchy[$name]['parents']);
            }
        }

        return $bundleHierarchy;
    }

    private function normalizeBundleName($name)
    {
        if ('Bundle' === substr($name, -6)) {
            $name = substr($name, 0, -6);
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://symfony.com/schema/dic/twig';
    }
}
