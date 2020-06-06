<?php

namespace ACore\Character\Services;

use \ACore\Soap\Utils\SoapTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class CharacterSoapMgr {

    use ContainerAwareTrait;
    use SoapTrait;

    public function changeName($charName, $newName = NULL) {
        return $this->executeCommand(".character rename $charName $newName");
    }

    public function changeFaction($charName) {
        return $this->executeCommand(".character changefaction $charName");
    }

    public function changeRace($charName) {
        return $this->executeCommand(".character changerace $charName");
    }

}
