<?php

namespace ACore\Manager\AcoreConnector;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class AcoreManager {

    protected $connectionParams;
    protected $config;

    /**
     *
     * @var EntityManager
     */
    protected $em;

    public function __construct() {}

    public function configureEntities($paths, $devMode = false) {
        $this->config = Setup::createAnnotationMetadataConfiguration(
            $paths,
            $devMode,
            null,
            null,
            false
        );
    }

    /**
     *
     * @param type $paths
     * @return AcoreManager
     */
    public function createEm($params) {
        $this->connectionParams = $params;

        $this->em = EntityManager::create($this->connectionParams, $this->config);

        return $this->em;
    }

    /**
     *
     * @param type $alias
     * @param type $type
     * @return EntityManager
     */
    public function getEm() {
        return $this->em;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentConnection() {
        return $this->em->getConnection();
    }

    /**
     * Shortcut for query
     * @param string $query
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function query($query, $params = array(), $types = array()) {
        return $this->getCurrentConnection()->executeQuery($query, $params, $types);
    }

    public function getVar($query, $params = array(), $types = array()) {
        $queryResult = $this->getCurrentConnection()->executeQuery($query, $params, $types);
        return $queryResult->fetchOne();
    }

    public function fetchSingleObj($class, $query, $params = array(), $types = array()) {
        $res = $this->fetchAllObj($class, $query);

        if ($res)
            return $res[0];

        return $res;
    }

    public function fetchAllObj($class, $query, $params = array(), $types = array()) {
        $queryResult = $this->getCurrentConnection()->executeQuery($query, $params, $types);

        return $queryResult->fetchAllAssociative();
    }

}
