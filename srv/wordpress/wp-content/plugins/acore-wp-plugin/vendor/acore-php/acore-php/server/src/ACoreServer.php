<?php

namespace ACore\Server;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreServer extends Bundle {

    public function getContainerExtension() {
        return new DependencyInjection\ServerExtension();
    }

}
