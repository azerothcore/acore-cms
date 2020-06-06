<?php

namespace ACore\AuthDb;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreAuthDb extends Bundle {

    public function getContainerExtension() {
        return new DependencyInjection\AuthDbExtension();
    }

}
