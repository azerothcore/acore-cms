<?php

namespace ACore\Framework;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreFramework extends Bundle {

    public static function getBundles() {
        return array(
            // itself
            new ACoreFramework(),
            // and all other ACore official modules
            new \ACore\Soap\ACoreSoap(),
            new \ACore\Database\ACoreDatabase(),
            new \ACore\WorldDb\ACoreWorldDb(),
            new \ACore\AuthDb\ACoreAuthDb(),
            new \ACore\CharDb\ACoreCharDb(),
            new \ACore\Creature\ACoreCreature(),
            new \ACore\Account\ACoreAccount(),
            new \ACore\GameMail\ACoreGameMail(),
            new \ACore\Character\ACoreCharacter(),
            new \ACore\Server\ACoreServer(),
        );
    }

}
