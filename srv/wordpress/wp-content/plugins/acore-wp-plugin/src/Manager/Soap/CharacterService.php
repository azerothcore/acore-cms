<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class CharacterService {

    use AcoreSoapTrait;

    public function changeName($charName, $newName = null, $orderId = null) {
        return $this->executeCommand(
            ".character rename $charName $newName",
            true,
            $orderId
        );
    }

    public function changeFaction($charName, $orderId = null) {
        return $this->executeCommand(
            ".character changefaction $charName",
            true,
            $orderId
        );
    }

    public function changeRace($charName, $orderId = null) {
        return $this->executeCommand(
            ".character changerace $charName",
            true,
            $orderId
        );
    }

    public function charCustomization($charName, $orderId = null) {
        return $this->executeCommand(
            ".character customize $charName",
            true,
            $orderId
        );
    }

    public function charRestore($charGuid, $newName = null, $orderId = null) {
        return $this->executeCommand(
            ".character deleted restore $charGuid $newName",
            true,
            $orderId
        );
    }

}
