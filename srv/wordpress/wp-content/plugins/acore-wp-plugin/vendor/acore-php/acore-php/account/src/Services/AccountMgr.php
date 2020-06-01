<?php

namespace ACore\Account\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use ACore\Account\Entity\AccountEntity;
use ACore\Account\Entity\AccountBannedEntity;
use ACore\AuthDb\Utils\AuthDbTrait;

class AccountMgr {

    use AuthDbTrait;
    use ContainerAwareTrait;

    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @return \ACore\Account\Repository\AccountRepository
     */
    public function getAccountRepo($em) {
        return $em->getRepository(AccountEntity::class);
    }

    /**
     * 
     * @param type $alias
     * @return \ACore\Account\Repository\AccountBannedRepository
     */
    public function getAccountBannedRepo($alias) {
        return $this->getAuthEm($alias)->getRepository(AccountBannedEntity::class);
    }
}
