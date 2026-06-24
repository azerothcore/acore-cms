<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class SmartstoneService {

    use AcoreSoapTrait;

    public function addVanity($charName, $category, $vanityID) {
        return $this->executeCommand(".smartstone unlock service $charName $category $vanityID true");
    }

    /**
     * Unlock a service for the entire account (account-wide).
     * Only ACTION_TYPE_COSTUME (2) and ACTION_TYPE_PERK (9) are accepted server-side;
     * other service types will be rejected by the mod's HandleSmartStoneUnlockAccountCommand.
     *
     * $accountName is expected to come from a WordPress user_login (sanitize_user
     * restricts these to alphanumerics, dot, hyphen, underscore, at - no spaces),
     * but we still cast the numeric args at the boundary as belt-and-braces.
     */
    public function addAccountVanity($accountName, $category, $vanityID) {
        $category = (int) $category;
        $vanityID = (int) $vanityID;
        return $this->executeCommand(".smartstone unlock account $accountName $category $vanityID true");
    }

}