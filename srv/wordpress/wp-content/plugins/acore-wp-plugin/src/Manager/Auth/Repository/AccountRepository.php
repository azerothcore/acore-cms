<?php

namespace ACore\Manager\Auth\Repository;

use ACore\Manager\AcoreConnector\AcoreRepository;

class AccountRepository extends AcoreRepository {

    /**
     * Verify account and returns user info
     *
     * @param string $username
     * @param string $password
     * @return Account
     */
    public function verifyAccount($username, $password) {
        $authDb = $this->getEntityManager();

        // get salt
        $qb=$authDb->createQueryBuilder();
        $query=$qb->select("a.salt")
                        ->from("ACore\Manager\Auth\Entity\AccountEntity","a")
                        ->where($qb->expr()->eq("LOWER(a.username)",":username"))
                        ->setParameter("username", strtolower($username))
                        ->getQuery();
        $salt = $query->getOneOrNullResult();

        // calculate "verifier"
        $enc_password = $this->CalculateSRP6Verifier($username, $password, $salt["salt"]);

        $qb=$authDb->createQueryBuilder();
        $query=$qb->select("a")
                        ->from("ACore\Manager\Auth\Entity\AccountEntity","a")
                        ->where($qb->expr()->eq("LOWER(a.username)",":username"))
                        ->andWhere("a.verifier = :password")
                        ->setParameter("username", strtolower($username))
                        ->setParameter("password", $enc_password)
                        ->getQuery();

        return $query->getOneOrNullResult();
    }

    private function CalculateSRP6Verifier($username, $password, $salt)
    {
        // algorithm constants
        $g = gmp_init(7);
        $N = gmp_init('894B645E89E1535BBDAD5B8B290650530801B18EBFBF5E8FAB3C82872A3E9BB7', 16);

        // calculate first hash
        $h1 = sha1(strtoupper($username . ':' . $password), TRUE);

        // calculate second hash
        $h2 = sha1($salt.$h1, TRUE);

        // convert to integer (little-endian)
        $h2 = gmp_import($h2, 1, GMP_LSW_FIRST);

        // g^h2 mod N
        $verifier = gmp_powm($g, $h2, $N);

        // convert back to a byte array (little-endian)
        $verifier = gmp_export($verifier, 1, GMP_LSW_FIRST);

        // pad to 32 bytes, remember that zeros go on the end in little-endian!
        $verifier = str_pad($verifier, 32, chr(0), STR_PAD_RIGHT);

        return $verifier;
    }

    /**
     *
     * @param string $username
     * @param string $ip
     * @param boolean $lock true|false
     */
    public function setAccountLock($username, $ip = '127.0.0.1', $lock = true) {
        $authDb = $this->getEntityManager();

        $authDb->createQueryBuilder()
                ->update("ACore\Manager\Auth\Entity\AccountEntity","a")
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
     * @return ACore\Manager\Auth\Entity\AccountEntity
     */
    public function findOneByUsername($username) {
        return parent::findOneByUsername($username);
    }

    /**
     * API Alias
     *
     * @param int $id
     * @return ACore\Manager\Auth\Entity\AccountEntity
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
     * @return ACore\Manager\Auth\Entity\AccountEntity
     */
    public function findOneBy($criteria, $orderBy = null) {
        return parent::findOneBy($criteria, $orderBy);
    }


}
