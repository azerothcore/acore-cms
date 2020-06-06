<?php

namespace ACore\Account\Repository;

use \ACore\System\Utils\Repository;

class AccountBannedRepository extends Repository {

    /**
     * API Alias
     * 
     * @param int $id
     * @return \ACore\Account\Entity\AccountBannedEntity
     */
    public function findOneById($id) {
        return parent::find($id);
    }

    public function isActiveById($id) {
        return parent::findOneBy(array("id" => $id, "active" => 1));
    }

}
