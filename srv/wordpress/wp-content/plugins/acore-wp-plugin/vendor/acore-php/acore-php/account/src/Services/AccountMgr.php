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
     * @param \Doctrine\ORM\EntityManager $em
     * @return \ACore\Account\Repository\AccountBannedRepository
     */
    public function getAccountBannedRepo($em) {
        return $em->getRepository(AccountBannedEntity::class);
    }
}
