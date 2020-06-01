<?php

namespace ACore\System\Utils;

class Repository extends \Doctrine\ORM\EntityRepository {

    /**
     * Helper function for direct queries
     * @param type $query
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
