<?php

namespace ACore\AuthDb\Utils;

use ACore\Database\Services\DoctrineDbMgr;

trait AuthDbTrait {

    /**
     *
     * @var DoctrineDbMgr 
     */
    protected $authDb;

    protected $authEm = array();

    /**
     * 
     * @return DoctrineDbMgr
     */
    public function getAuthDb() {
        return $this->authDb;
    }

    public function setAuthDb(DoctrineDbMgr $authDb) {
        $this->authDb = $authDb;
        $this->authDb->configureEntities(array(realpath(__DIR__ . "/../Entity/")));
    }

    /**
     * 
     * @return DoctrineDbMgr
     */
    public function createAuthEm($alias, $params) {
        $this->authEm[$alias] = $this->authDb->createEm($params);
    }

    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    public function getAuthEm($alias) {        
        return $this->authEm[$alias];
    }

}
