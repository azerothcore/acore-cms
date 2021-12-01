<?php

namespace ACore\Manager\AcoreConnector;

class AcoreRepository extends \Doctrine\ORM\EntityRepository {

    /**
     * Helper function for direct queries
     * @param string $query
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function query($query, $params = array(), $types = array()) {
        return $this->getEntityManager()->getConnection()->executeQuery($query, $params, $types);
    }

    /**
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDbConn() {
        return $this->getEntityManager()->getConnection();
    }

    public function getEM() {
        return $this->getEntityManager();
    }

}
