<?php

namespace ACore\Manager\Character\Repository;

use ACore\Manager\AcoreConnector\AcoreRepository;

class CharacterBannedRepository extends AcoreRepository {

    /**
     * API Alias
     *
     * @param int $guid
     * @return ACore\Manager\Character\Entity\CharacterBannedEntity
     */
    public function findOneByGuid($guid) {
        return parent::find($guid);
    }

    public function isActiveByGuid($guid) {
        return parent::findOneBy(array("guid" => $guid, "active" => 1));
    }

}
