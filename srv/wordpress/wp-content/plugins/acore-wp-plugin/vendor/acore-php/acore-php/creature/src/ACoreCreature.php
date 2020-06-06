<?php

namespace ACore\Creature;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreCreature extends Bundle {

    public function getContainerExtension()
    {
        return new DependencyInjection\CreatureExtension();
    }

}
