<?php

namespace ACore\WorldDb\Utils;

use ACore\Database\Services\DoctrineDbMgr;

trait WorldDbTrait {

    /**
     *
     * @var DoctrineDbMgr 
     */
    protected $worldDb;

    protected $worldEm = array();

    /**
     * 
     * @return DoctrineDbMgr
     */
    public function getWorldDb() {
        return $this->worldDb;
    }

    public function setWorldDb(DoctrineDbMgr $worldDb) {
        $this->worldDb = $worldDb;
        $this->worldDb->configureEntities(array(realpath(__DIR__ . "/../Entity/")));
    }

    /**
     * 
     * @return DoctrineDbMgr
     */
    public function createWorldEm($alias) {
        $this->worldEm[$alias] = $this->worldDb->createEm($alias, "world");
    }

    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    public function getWorldEm($alias) {
        if (!isset($this->worldEm[$alias])) {
            $this->createWorldEm($alias);
        }
        
        return $this->worldEm[$alias];
    }

}
