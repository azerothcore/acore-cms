<?php

namespace ACore\Manager\Character\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="ACore\Manager\Character\Repository\CharacterRepository")
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

    /**
     * @var string
     *
     * @ORM\Column(name="deleteInfos_Name", type="string")
     */
    protected $deleteInfos_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="deleteInfos_Account", type="string")
     */
    protected $deleteInfosAccount;

    /**
     * @var int
     * 
     *  @ORM\Column(name="race", type="integer")
     */
    protected $race;

    /**
     * @var int
     * 
     *  @ORM\Column(name="gender", type="integer")
     */
    protected $gender;

    /**
     * @var int
     * 
     *  @ORM\Column(name="class", type="integer")
     */
    protected $class;

    /**
     * @var int
     * 
     *  @ORM\Column(name="level", type="integer")
     */
    protected $level;

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

    public function getDeletedName() {
        return $this->deleteInfos_Name;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function getRace(): int {
        return $this->race;
    }

    public function getClass(): int {
        return $this->class;
    }

    public function getGender(): int {
        return $this->gender;
    }

    public function getLevel(): int {
        return $this->level;
    }

    public function getCharIconUrl(): string {
       return  ACORE_URL_PLG . "web/assets/race/" . $this->getRace() . ($this->getGender() == 0 ? "m" : "f") . ".webp";
    }

    public function getClassIconUrl(): string {
       return ACORE_URL_PLG . "web/assets/class/" . $this->getClass() . ".webp";
    }

}
