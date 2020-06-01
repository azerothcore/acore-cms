<?php

namespace ACore\WorldDb;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreWorldDb extends Bundle {

    public function getContainerExtension() {
        return new DependencyInjection\WorldDbExtension();
    }

}
