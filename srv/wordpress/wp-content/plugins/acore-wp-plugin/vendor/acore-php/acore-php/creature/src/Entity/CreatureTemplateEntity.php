<?php

namespace ACore\Creature\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ACore\Creature\Entity\CreatureTemplate
 * 
 * @ORM\Entity(repositoryClass="ACore\Creature\Repository\CreatureTmplRepository")
 * @ORM\Table(name="creature_template")
 */
class CreatureTemplateEntity {

    /**
     * @var int
     *
     * @ORM\Column(name="entry", type="integer")
     * @ORM\Id
     */
    protected $entry;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;
    
    /**
     * @var int
     *
     * @ORM\Column(name="maxlevel", type="integer")
     */
    protected $maxlevel;

    /**
     * Get id
     *
     * @return int
     */
    public function getEntry() {
        return $this->entry;
    }

    public function setEntry($entry) {
        $this->entry = $entry;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }
    
    public function getMaxlevel() {
        return $this->maxlevel;
    }

    public function setMaxlevel($maxlevel) {
        $this->maxlevel = $maxlevel;
        return $this;
    }



}
