<?php

namespace ACore\Database\Services;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DoctrineDbMgr {

    use ContainerAwareTrait;

    protected $connectionParams;
    protected $config;

    /**
     *
     * @var EntityManager
     */
    protected $em;

    public function __construct() {
        
    }

    public function configureEntities($paths, $devMode = false) {
        $this->config = Setup::createAnnotationMetadataConfiguration(
                        $paths, $devMode, null, null, false
        );

        $this->config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
    }

    /**
     * 
     * @param type $paths
     * @return DoctrineDbMgr
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
    public function Conn() {
        return $this->em->getConnection();
    }

    /**
     * Shortcut for query
     * @param type $query
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function query($query, $params = array(), $types = array()) {
        return $this->Conn()->executeQuery($query, $params, $types);
    }

    public function getVar($query, $params = array(), $types = array()) {
        return $this->query($query, $params, $types)->fetchColumn();
    }

    public function fetchSingleObj($class, $query, $params = array(), $types = array()) {
        $res = $this->fetchAllObj($class, $query);

        if ($res)
            return $res[0];

        return $res;
    }

    public function fetchAllObj($class, $query, $params = array(), $types = array()) {
        $stmt = $this->query($query, $params, $types);

        return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class);
    }

}
