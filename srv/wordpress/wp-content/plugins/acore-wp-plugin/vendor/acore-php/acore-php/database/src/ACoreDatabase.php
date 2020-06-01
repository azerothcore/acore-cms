<?php

namespace ACore\Database;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreDatabase extends Bundle {

    public function getContainerExtension() {
        return new DependencyInjection\DatabaseExtension();
    }

}
