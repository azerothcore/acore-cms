<?php

namespace ACore\Account;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreAccount extends Bundle {

    public function getContainerExtension()
    {
        return new DependencyInjection\AccountExtension();
    }

}
