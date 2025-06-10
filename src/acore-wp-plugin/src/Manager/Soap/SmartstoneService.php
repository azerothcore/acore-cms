<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class SmartstoneService {

    use AcoreSoapTrait;

    public function addVanity($charName, $category, $vanityID) {
        return $this->executeCommand(".smartstone unlock service $charName $category $vanityID true");
    }

}