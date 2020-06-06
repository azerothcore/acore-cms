<?php

namespace ACore\Server\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use \ACore\Soap\Utils\SoapTrait;

class ServerSoapMgr {

    use ContainerAwareTrait;
    use SoapTrait;

    public function serverInfo() {
        return $this->executeCommand('.server info');
    }

}
