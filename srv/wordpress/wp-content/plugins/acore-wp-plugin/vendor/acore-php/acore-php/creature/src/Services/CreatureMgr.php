<?php

namespace ACore\Creature\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use ACore\Creature\Entity\CreatureTemplateEntity;
use ACore\Creature\Entity\CreatureEntity;
use ACore\WorldDb\Utils\WorldDbTrait;

class CreatureMgr {

    use WorldDbTrait;
    use ContainerAwareTrait;

    /**
     * 
     * @param type $alias
     * @return \ACore\Creature\Repository\CreatureTmplRepository
     */
    public function getCreatureTmplRepo($alias) {
        return $this->getWorldEm($alias)->getRepository(CreatureTemplateEntity::class);
    }

    /**
     * 
     * @param type $alias
     * @return \ACore\Creature\Repository\CreatureRepository
     */
    public function getCreatureRepo($alias) {
        return $this->getWorldEm($alias)->getRepository(CreatureEntity::class);
    }

}
