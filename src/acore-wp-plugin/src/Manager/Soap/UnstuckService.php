<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class UnstuckService
{

    use AcoreSoapTrait;

    public function unstuckByName($charName)
    {
        $this->executeCommand(".revive $charName");
        return $this->executeCommand(".unstuck $charName");
    }
}
