<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class CharacterService {

    use AcoreSoapTrait;

    public function changeName($charName, $newName = NULL) {
        return $this->executeCommand(".character rename $charName $newName");
    }

    public function changeFaction($charName) {
        return $this->executeCommand(".character changefaction $charName");
    }

    public function changeRace($charName) {
        return $this->executeCommand(".character changerace $charName");
    }

    public function charCustomization($charName) {
        return $this->executeCommand(".character customize $charName");
    }

    public function charRestore($charGuid, $newName = NULL) {
        return $this->executeCommand(".character deleted restore $charGuid $newName");
    }

}
