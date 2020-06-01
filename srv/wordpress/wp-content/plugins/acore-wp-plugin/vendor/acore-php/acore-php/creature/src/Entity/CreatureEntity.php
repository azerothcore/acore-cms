<?php

namespace ACore\Creature\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ACore\Creature\Entity\CreatureTemplate
 * 
 * @ORM\Entity(repositoryClass="ACore\Creature\Repository\CreatureRepository")
 * @ORM\Table(name="creature")
 */
class CreatureEntity {

    /**
     * @var int
     *
     * @ORM\Column(name="guid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $guid;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     *
     */
    public $id;

    public function getGuid() {
        return $this->guid;
    }

    public function getId() {
        return $this->id;
    }

}
