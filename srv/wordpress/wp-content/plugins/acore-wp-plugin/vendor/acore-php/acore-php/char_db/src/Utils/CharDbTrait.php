<?php

namespace ACore\CharDb\Utils;

use ACore\Database\Services\DoctrineDbMgr;

trait CharDbTrait {

    /**
     *
     * @var DoctrineDbMgr 
     */
    protected $charDb;

    protected $charEm = array();

    /**
     * 
     * @return DoctrineDbMgr
     */
    public function getCharDb() {
        return $this->charDb;
    }

    public function setCharDb(DoctrineDbMgr $charDb) {
        $this->charDb = $charDb;
        $this->charDb->configureEntities(array(realpath(__DIR__ . "/../Entity/")));
    }

    /**
     * 
     * @return DoctrineDbMgr
     */
    public function createCharEm($alias) {
        $this->charEm[$alias] = $this->charDb->createEm($alias, "char");
    }

    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    public function getCharEm($alias) {
        if (!isset($this->charEm[$alias])) {
            $this->createCharEm($alias);
        }
        
        return $this->charEm[$alias];
    }

}
