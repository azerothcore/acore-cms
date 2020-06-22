<?php

namespace ACore\Account\Repository;

use \ACore\System\Utils\Repository;

class AccountAccessRepository extends Repository {

    /**
     * API Alias
     * 
     * @param int $id
     * @return \ACore\Account\Entity\AccountAccessEntity
     */
    public function findOneById($id) {
        return parent::find($id);
    }
}
