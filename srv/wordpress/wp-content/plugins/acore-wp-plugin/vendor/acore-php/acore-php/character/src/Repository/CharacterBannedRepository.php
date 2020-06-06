<?php

namespace ACore\Character\Repository;

use \ACore\System\Utils\Repository;

class CharacterBannedRepository extends Repository {

    /**
     * API Alias
     * 
     * @param int $guid
     * @return \ACore\Character\Entity\CharacterBannedEntity
     */
    public function findOneByGuid($guid) {
        return parent::find($guid);
    }

    public function isActiveByGuid($guid) {
        return parent::findOneBy(array("guid" => $guid, "active" => 1));
    }

}
