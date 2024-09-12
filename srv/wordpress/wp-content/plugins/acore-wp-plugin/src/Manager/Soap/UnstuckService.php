<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class UnstuckService {

    use AcoreSoapTrait;

    public function teleportName($charName, $newName = NULL) {
        return $this->executeCommand(".teleport name $charName '\$home'");
    }

}