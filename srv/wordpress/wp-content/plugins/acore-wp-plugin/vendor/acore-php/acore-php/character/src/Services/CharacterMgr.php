<?php

namespace ACore\Character\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use ACore\Character\Entity\CharacterEntity;
use ACore\Character\Entity\CharacterBannedEntity;
use ACore\CharDb\Utils\CharDbTrait;

class CharacterMgr {

    use CharDbTrait;
    use ContainerAwareTrait;

    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @return \ACore\Character\Repository\CharacterRepository
     */
    public function getCharacterRepo($em) {
        return $em->getRepository(CharacterEntity::class);
    }
    
    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @return \ACore\Character\Repository\CharacterBannedRepository
     */
    public function getCharacterBannedRepo($em) {
        return $em->getRepository(CharacterBannedEntity::class);
    }

}
