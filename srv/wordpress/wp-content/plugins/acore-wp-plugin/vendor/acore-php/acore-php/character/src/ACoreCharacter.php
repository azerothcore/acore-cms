<?php

namespace ACore\Character;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreCharacter extends Bundle {

    public function getContainerExtension()
    {
        return new DependencyInjection\CharacterExtension();
    }

}
