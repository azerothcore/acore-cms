<?php

namespace ACore\Account\Repository;

use \ACore\System\Utils\Repository;

class AccountRepository extends Repository {

    /**
     * Verify account and returns user info
     * 
     * @param type $username
     * @param type $password
     * @return Account
     */
    public function verifyAccount($username, $password) {
        $authDb = $this->getEntityManager();

        $enc_password = sha1(strtoupper($username) . ':' . strtoupper($password));
        
        $qb=$authDb->createQueryBuilder();
        $query=$qb->select("a")
                        ->from("ACore\Account\Entity\AccountEntity","a")
                        ->where($qb->expr()->eq("LOWER(a.username)",":username"))
                        ->andWhere("a.sha_pass_hash = :password")
                        ->setParameter("username", strtolower($username))
                        ->setParameter("password", $enc_password)
                        ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * 
     * @param type $username
     * @param type $ip
     * @param boolean $lock true|false
     */
    public function setAccountLock($username, $ip = '127.0.0.1', $lock = true) {
        $authDb = $this->getEntityManager();

        $authDb->createQueryBuilder()
                ->update("ACore\Account\Entity\AccountEntity","a")
                ->set("a.last_ip", ":last_ip")
                ->set("a.locked", ":locked")
                ->where('a.username = :username')
                ->setParameter("last_ip", $ip)
                ->setParameter("locked", $lock)
                ->setParameter("username", $username)->getQuery()->execute();
    }

    /**
     * API Alias
     * 
     * @param string $username
     * @return \ACore\Account\Entity\AccountEntity
     */
    public function findOneByUsername($username) {
        return parent::findOneByUsername($username);
    }

    /**
     * API Alias
     * 
     * @param int $id
     * @return \ACore\Account\Entity\AccountEntity
     */
    public function findOneById($id) {
        return $this->find($id);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * 
     * @return \ACore\Account\Entity\AccountEntity
     */
    public function findOneBy($criteria, $orderBy = null) {
        return parent::findOneBy($criteria, $orderBy);
    }


}
