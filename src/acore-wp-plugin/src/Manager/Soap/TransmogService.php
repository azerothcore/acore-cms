<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class TransmogService {

    use AcoreSoapTrait;

    public function addItemToPlayer($charName, $item) {
        return $this->executeCommand(".transmog add $charName $item");
    }

    public function addItemsetToPlayer($charName, $itemset) {
        return $this->executeCommand(".transmog add set $charName $itemset");
    }
}
