<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class UnstuckService
{

    use AcoreSoapTrait;

    public function teleportName($charName)
    {
        return $this->executeCommand(".tele name $charName \$home");
    }
}
