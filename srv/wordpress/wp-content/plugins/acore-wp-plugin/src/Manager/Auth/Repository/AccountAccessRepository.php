<?php

namespace ACore\Manager\Auth\Repository;

use ACore\Manager\AcoreConnector\AcoreRepository;

class AccountAccessRepository extends AcoreRepository {

    /**
     * API Alias
     *
     * @param int $id
     * @return ACore\Manager\Auth\Entity\AccountAccessEntity
     */
    public function findOneById($id) {
        return parent::find($id);
    }
}
