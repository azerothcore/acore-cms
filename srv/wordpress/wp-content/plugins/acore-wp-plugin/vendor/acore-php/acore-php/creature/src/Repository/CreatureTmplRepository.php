<?php

namespace ACore\Creature\Repository;

use \ACore\System\Utils\Repository;

class CreatureTmplRepository extends Repository {
    
    public function findOneByEntry($entry) {
        return parent::find($entry);
    }
}
