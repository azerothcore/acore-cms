<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\ProxyManager\Tests\LazyProxy\PhpDumper;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\LazyProxy\PhpDumper\DumperInterface;

/**
 * Tests for {@see \Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper}.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class ProxyDumperTest extends TestCase
{
    /**
     * @var ProxyDumper
     */
    protected $dumper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->dumper = new ProxyDumper();
    }

    /**
     * @dataProvider getProxyCandidates
     *
     * @param bool $expected
     */
    public function testIsProxyCandidate(Definition $definition, $expected)
    {
        $this->assertSame($expected, $this->dumper->isProxyCandidate($definition));
    }

    public function testGetProxyCode()
    {
        $definition = new Definition(__CLASS__);

        $definition->setLazy(true);

        $code = $this->dumper->getProxyCode($definition);

        $this->assertStringMatchesFormat(
            '%Aclass ProxyDumperTest%aextends%w'
                .'\Symfony\Bridge\ProxyManager\Tests\LazyProxy\PhpDumper\ProxyDumperTest%a',
            $code
        );
    }

    public function testDeterministicProxyCode()
    {
        $definition = new Definition(__CLASS__);
        $definition->setLazy(true);

        $this->assertSame($this->dumper->getProxyCode($definition), $this->dumper->getProxyCode($definition));
    }

    public function testGetProxyFactoryCode()
    {
        $definition = new Definition(__CLASS__);

        $definition->setLazy(true);

        $code = $this->dumper->getProxyFactoryCode($definition, 'foo', '$this->getFoo2Service(false)');

        $this->assertStringMatchesFormat(
            '%A$wrappedInstance = $this->getFoo2Service(false);%w$proxy->setProxyInitializer(null);%A',
            $code
        );
    }

    /**
     * @dataProvider getPrivatePublicDefinitions
     */
    public function testCorrectAssigning(Definition $definition, $access)
    {
        $definition->setLazy(true);

        $code = $this->dumper->getProxyFactoryCode($definition, 'foo', '$this->getFoo2Service(false)');

        $this->assertStringMatchesFormat('%A$this->'.$access.'[\'foo\'] = %A', $code);
    }

    public function getPrivatePublicDefinitions()
    {
        return [
            [
                (new Definition(__CLASS__))
                    ->setPublic(false),
                method_exists(ContainerBuilder::class, 'addClassResource') ? 'services' : 'privates',
            ],
            [
                (new Definition(__CLASS__))
                    ->setPublic(true),
                'services',
            ],
        ];
    }

    /**
     * @group legacy
     */
    public function testLegacyGetProxyFactoryCode()
    {
        $definition = new Definition(__CLASS__);

        $definition->setLazy(true);

        $code = $this->dumper->getProxyFactoryCode($definition, 'foo');

        $this->assertStringMatchesFormat(
            '%A$wrappedInstance = $this->getFooService(false);%w$proxy->setProxyInitializer(null);%A',
            $code
        );
    }

    /**
     * @return array
     */
    public function getProxyCandidates()
    {
        $definitions = [
            [new Definition(__CLASS__), true],
            [new Definition('stdClass'), true],
            [new Definition(DumperInterface::class), true],
            [new Definition(uniqid('foo', true)), false],
            [new Definition(), false],
        ];

        array_map(
            function ($definition) {
                $definition[0]->setLazy(true);
            },
            $definitions
        );

        return $definitions;
    }
}
