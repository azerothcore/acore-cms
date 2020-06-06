<?php

namespace ACore\Account\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ACore\Account\Entity\AccountEntity
 * 
 * @ORM\Entity(repositoryClass="ACore\Account\Repository\AccountBannedRepository")
 * @ORM\Table(name="account_banned")
 */
class AccountBannedEntity {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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

    public function getId() {
        return $this->id;
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
