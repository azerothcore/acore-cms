<?php

namespace ACore\Manager\Auth\Repository;

use ACore\Manager\AcoreConnector\AcoreRepository;

class AccountBannedRepository extends AcoreRepository {

    /**
     * API Alias
     *
     * @param int $id
     * @return ACore\Manager\Auth\Entity\AccountBannedEntity
     */
    public function findOneById($id) {
        return parent::find($id);
    }

    public function isActiveById($id) {
        return parent::findOneBy(array("id" => $id, "active" => 1));
    }

}
