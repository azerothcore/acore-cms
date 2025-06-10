<?php

namespace ACore\Manager\Auth\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ACore\Manager\Auth\Entity\AccountAccessEntity
 *
 * @ORM\Entity(repositoryClass="ACore\Manager\Auth\Repository\AccountAccessRepository")
 * @ORM\Table(name="account_access")
 */
class AccountAccessEntity
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="gmlevel", type="integer")
     */
    protected $gmlevel;

    /**
     * @var integer
     *
     * @ORM\Column(name="RealmID", type="integer")
     * @ORM\Id
     */
    protected $RealmID;


    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getGmLevel()
    {
        return $this->gmlevel;
    }

    public function setGmLevel($gmlevel)
    {
        $this->gmlevel = $gmlevel;
        return $this;
    }

    public function getRealmID()
    {
        return $this->gmlevel;
    }

    public function setRealmID($RealmID)
    {
        $this->RealmID = $RealmID;
        return $this;
    }
}
