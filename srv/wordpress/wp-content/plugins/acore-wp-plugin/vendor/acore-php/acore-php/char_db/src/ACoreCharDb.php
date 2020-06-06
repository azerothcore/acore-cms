<?php

namespace ACore\CharDb;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreCharDb extends Bundle {

    public function getContainerExtension() {
        return new DependencyInjection\CharDbExtension();
    }

}
