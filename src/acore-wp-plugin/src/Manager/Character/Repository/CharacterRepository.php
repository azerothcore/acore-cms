<?php

namespace ACore\Manager\Character\Repository;

use ACore\Manager\AcoreConnector\AcoreRepository;

class CharacterRepository extends AcoreRepository {

    public function countAccChars($accountId) {
        return count($this->findByAccount($accountId));
    }

    /**
     * API Alias
     *
     * @param int $guid
     * @return ACore\Manager\Character\Entity\CharacterEntity
     */
    public function findOneByGuid($guid) {
        return parent::find($guid);
    }

    /**
     * API Alias
     *
     * @param int $accountId
     * @return array()
     */
    public function findByAccount($accountId) {
        return parent::findByAccount($accountId);
    }

    /**
     * API Alias
     *
     * @param int $accountId
     * @return array()
     */
    public function findByDeleteInfos_Account($accountId) {
        return parent::findByDeleteInfos_Account($accountId);
    }

    /**
     * API Alias
     *
     * @param string $name
     * @return array()
     */
    public function findByName($name) {
        return parent::findByName($name);
    }

    /**
     * API Alias
     *
     * @param string $name
     * @return ACore\Manager\Character\Entity\CharacterEntity
     */
    public function findOneByName($name) {
        return parent::findOneByName($name);
    }

}
