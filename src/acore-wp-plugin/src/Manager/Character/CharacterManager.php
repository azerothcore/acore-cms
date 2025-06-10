<?php

namespace ACore\Manager\Character;

use ACore\Manager\Character\Entity\CharacterEntity;
use ACore\Manager\Character\Entity\CharacterBannedEntity;
use ACore\Manager\AcoreConnector\AcoreManagerTrait;

class CharacterManager {

    use AcoreManagerTrait;

    public function __construct() {
        $this->entityPath = realpath(__DIR__ . "/../Entity/");
    }

    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @return ACore\Manager\Character\Repository\CharacterRepository
     */
    public function getCharacterRepo($em) {
        return $em->getRepository(CharacterEntity::class);
    }

    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @return ACore\Manager\Character\Repository\CharacterBannedRepository
     */
    public function getCharacterBannedRepo($em) {
        return $em->getRepository(CharacterBannedEntity::class);
    }

}
