<?php

namespace ACore\Manager\AcoreConnector;

use ACore\Manager\AcoreConnector\AcoreManager;

trait AcoreManagerTrait {

    /**
     *
     * @var AcoreManager
     */
    protected $acoreInstance;

    protected $entityPath;

    protected $acoreEm = array();

    /**
     *
     * @return AcoreManager
     */
    public function getAcoreManager() {
        return $this->acoreInstance;
    }

    public function setAcoreManager(AcoreManager $acoreInstance) {
        $this->acoreInstance = $acoreInstance;
        $this->acoreInstance->configureEntities(array($this->entityPath));
    }

    /**
     *
     * @return AcoreManager
     */
    public function createAcoreEm($alias, $params) {
        $this->acoreEm[$alias] = $this->acoreInstance->createEm($params);
    }

    /**
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getAcoreEm($alias) {
        return $this->acoreEm[$alias];
    }

}
