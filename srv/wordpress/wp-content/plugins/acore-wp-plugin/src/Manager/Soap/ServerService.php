<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class ServerService {

    use AcoreSoapTrait;

    public function serverInfo() {
        return $this->executeCommand('.server info');
    }
}
