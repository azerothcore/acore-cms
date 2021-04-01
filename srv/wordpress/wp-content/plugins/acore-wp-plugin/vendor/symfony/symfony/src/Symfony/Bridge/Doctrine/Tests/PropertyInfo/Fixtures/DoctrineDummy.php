<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * @Entity
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineDummy
{
    /**
     * @Id
     * @Column(type="smallint")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="DoctrineRelation")
     */
    public $foo;

    /**
     * @ManyToMany(targetEntity="DoctrineRelation")
     */
    public $bar;

    /**
     * @ManyToMany(targetEntity="DoctrineRelation", indexBy="rguid")
     */
    protected $indexedRguid;

    /**
     * @ManyToMany(targetEntity="DoctrineRelation", indexBy="rguid_column")
     */
    protected $indexedBar;

    /**
     * @OneToMany(targetEntity="DoctrineRelation", mappedBy="foo", indexBy="foo")
     */
    protected $indexedFoo;

    /**
     * @OneToMany(targetEntity="DoctrineRelation", mappedBy="baz", indexBy="baz_id")
     */
    protected $indexedBaz;

    /**
     * @Column(type="guid")
     */
    protected $guid;

    /**
     * @Column(type="time")
     */
    private $time;

    /**
     * @Column(type="time_immutable")
     */
    private $timeImmutable;

    /**
     * @Column(type="dateinterval")
     */
    private $dateInterval;

    /**
     * @Column(type="json_array")
     */
    private $jsonArray;

    /**
     * @Column(type="simple_array")
     */
    private $simpleArray;

    /**
     * @Column(type="float")
     */
    private $float;

    /**
     * @Column(type="decimal", precision=10, scale=2)
     */
    private $decimal;

    /**
     * @Column(type="boolean")
     */
    private $bool;

    /**
     * @Column(type="binary")
     */
    private $binary;

    /**
     * @Column(type="custom_foo")
     */
    private $customFoo;

    /**
     * @Column(type="bigint")
     */
    private $bigint;

    public $notMapped;

    /**
     * @OneToMany(targetEntity="DoctrineRelation", mappedBy="dt", indexBy="dt")
     */
    protected $indexedByDt;

    /**
     * @OneToMany(targetEntity="DoctrineRelation", mappedBy="customType", indexBy="customType")
     */
    private $indexedByCustomType;

    /**
     * @OneToMany(targetEntity="DoctrineRelation", mappedBy="buzField", indexBy="buzField")
     */
    protected $indexedBuz;
}
