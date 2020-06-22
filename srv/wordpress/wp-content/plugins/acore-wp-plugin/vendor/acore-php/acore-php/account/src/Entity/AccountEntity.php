<?php

namespace ACore\Account\Entity;

use ACore\Account\Entity\AccountAccessEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ACore\Account\Entity\AccountEntity
 * 
 * @ORM\Entity(repositoryClass="ACore\Account\Repository\AccountRepository")
 * @ORM\Table(name="account")
 */
class AccountEntity {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string")
     */
    protected $username;
    
    /**
     * @var string
     *
     * @ORM\Column(name="sha_pass_hash", type="string")
     */
    protected $sha_pass_hash;

    /**
     * @var boolean
     *
     * @ORM\Column(name="locked", type="boolean")
     */
    protected $locked;
    
    /**
     * @var string
     *
     * @ORM\Column(name="last_ip", type="string")
     */
    protected $last_ip;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string")
     */
    protected $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="expansion", type="integer")
     */
    protected $expansion;


    /**
     *
     * @ORM\OneToMany(targetEntity="\ACore\Account\Entity\AccountAccessEntity", mappedBy="id")
     */
    private $access;

    
    public function getId() {
        return $this->id;
    }

    /* public function setId($id) {
      $this->id = $id;
      return $this;
      } */

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }
    
    public function getShaPassHash() {
        return $this->sha_pass_hash;
    }

    public function setShaPassHash($sha_pass_hash) {
        $this->sha_pass_hash = $sha_pass_hash;
        return $this;
    }

    public function isLocked() {
        return $this->locked == 0 ? false : true;
    }

    public function setLocked($locked) {
        $this->locked = $locked;
        return $this;
    }
    
    public function getLastIp() {
        return $this->last_ip;
    }

    public function setLastIp($last_ip) {
        $this->last_ip = $last_ip;
        return $this;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function getExpansion() {
        return $this->expansion;
    }

    public function setExpansion($expansion) {
        $this->expansion = $expansion;
        return $this;
    }

    public function getAccess() {
        return $this->access;
    }


}
