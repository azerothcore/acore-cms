<?php

namespace ACore\Server\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use \ACore\Soap\Utils\SoapTrait;

class ServerMgr {

    use ContainerAwareTrait;
    use SoapTrait;

    public function serverInfo() {
        return $this->executeCommand('.server info');
    }

}
