<?php

namespace ACore\Manager\Character\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="ACore\Manager\Character\Repository\CharacterBannedRepository")
 * @ORM\Table(name="character_banned")
 */
class CharacterBannedEntity {

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
     * @ORM\Column(name="bandate", type="integer")
     */
    protected $bandate;

    /**
     * @var int
     *
     * @ORM\Column(name="unbandate", type="integer")
     */
    protected $unbandate;

    /**
     * @var string
     *
     * @ORM\Column(name="bannedby", type="string")
     */
    protected $bannedby;

    /**
     * @var string
     *
     * @ORM\Column(name="banreason", type="string")
     */
    protected $banreason;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active;

    public function getGuid() {
        return $this->guid;
    }

    public function getBandate() {
        return $this->bandate;
    }

    public function getUnbandate() {
        return $this->unbandate;
    }

    public function getBannedby() {
        return $this->bannedby;
    }

    public function getBanreason() {
        return $this->banreason;
    }

    public function isActive() {
        return $this->active;
    }

    public function setBandate($bandate) {
        $this->bandate = $bandate;
        return $this;
    }

    public function setUnbandate($unbandate) {
        $this->unbandate = $unbandate;
        return $this;
    }

    public function setBannedby($bannedby) {
        $this->bannedby = $bannedby;
        return $this;
    }

    public function setBanreason($banreason) {
        $this->banreason = $banreason;
        return $this;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }

}
