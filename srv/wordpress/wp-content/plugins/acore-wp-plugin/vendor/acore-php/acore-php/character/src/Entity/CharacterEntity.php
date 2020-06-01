<?php

namespace ACore\Character\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity(repositoryClass="ACore\Character\Repository\CharacterRepository")
 * @ORM\Table(name="characters")
 */
class CharacterEntity {

    /**
     * @var int
     *
     * @ORM\Column(name="guid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $guid;

    /**
     * @var int
     *
     * @ORM\Column(name="account", type="integer")
     */
    protected $account;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    public function getGuid() {
        return $this->guid;
    }

    public function setGuid($guid) {
        $this->guid = $guid;
        return $this;
    }

    public function getAccount() {
        return $this->account;
    }

    public function setAccount($account) {
        $this->account = $account;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

}
